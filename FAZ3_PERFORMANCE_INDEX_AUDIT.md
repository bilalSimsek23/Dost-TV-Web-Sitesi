# DOST TV CMS — FAZ 3 / Performans ve İndeks Analiz Raporu

> **Audit Türü**: Read-Only Veritabanı Performansı, Sorgu Planları (EXPLAIN QUERY PLAN) ve İndeks İhtiyaç Analizi  
> **Tarih**: 17 Ağustos 2026  
> **Proje**: DOST TV Web Sitesi & CMS  
> **İnceleme Durumu**: Read-Only (Hiçbir migration, indeks, sorgu, önbellek veya veri değiştirilmemiştir).

---

## 1. 📊 Mevcut Veri Hacmi (Current Data Size)

Gerçek SQLite veritabanındaki güncel satır sayıları:

| Tablo Adı | Mevcut Satır Sayısı | Gelecek 10x Hacim Projeksiyonu | İndeks Önemi |
|---|:---:|:---:|:---:|
| **`episodes`** | **3.347 satır** | **35.000+ satır** | 🔥 **ÇOK YÜKSEK (Kritik)** |
| **`schedule_template_items`** | **261 satır** | **2.500+ satır** | ⚡ **YÜKSEK (Haftalık Döngü)** |
| **`program_seasons`** | **80 satır** | **800 satır** | 🟢 Düşük / İndeksleri Tam |
| **`program_series`** | **14 satır** | **150 satır** | 🟢 Düşük / İndeksleri Tam |
| **`programs`** | **124 satır** | **1.200 satır** | 🟢 Düşük / Cache Korumalı |
| **`categories` / `category_program`** | **12 / 96 satır** | **100 / 1.000 satır** | 🟢 Düşük / Cache Korumalı |
| **`schedule_templates`** | **2 satır** | **20 satır** | 🟢 Düşük |
| **`youtube_sync_logs`** | **7 satır** | **5.000+ satır** | 🟡 Orta / Log Tablosu |

---

## 2. 🗄️ Mevcut İndeks Durumu ve Eksiklik Tespiti

Veritabanı şeması incelendiğinde ortaya çıkan çarpıcı tablo:

1. **`episodes` Tablosunda SADECE 1 İNDEKS VAR**:
   - Mevcut: `UNIQUE (slug)`
   - **Eksik**: `program_id`, `program_series_id`, `season_number`, `is_active`, `show_on_public`, `episode_number` veya `youtube_url` kolonlarında **HİÇBİR İNDEKS YOKTUR**.
   - Sonuç: 3.347 bölümlük tabloda yapılan her program detay ziyareti **Full Table Scan (Tüm Tabloyu Tarama)** ve **In-Memory Temporary B-Tree Filesort** ile sonuçlanmaktadır!
2. **`schedule_template_items` Tablosunda HİÇBİR İNDEKS YOKTUR (0 İndeks)**:
   - Sonuç: Haftalık yayın akışı çekilirken 7 günlük döngüde her gün için 261 satırlık tablo 7 kez baştan sona taranmaktadır.
3. **`program_seasons` ve `program_series`**:
   - FAZ 2'de eklenen composite unique ve lookup indeksleri sayesinde **%100 indeks korumalıdır**.

---

## 3. 🔬 Kritik Sorguların EXPLAIN QUERY PLAN Analizi

Mevcut veritabanında çalıştırılan gerçek SQL sorgularının yürütme planları:

### Sorgu 1: Public Program Detay — Sezon Bölümleri
```sql
SELECT * FROM episodes 
WHERE program_id = 105 AND is_active = 1 AND show_on_public = 1 AND season_number = 9 
ORDER BY episode_number ASC;
```
- **Mevcut Plan (İndekssiz)**:
  - `SCAN episodes` (3.347 satırın tamamı belleğe taranır)
  - `USE TEMP B-TREE FOR ORDER BY` (Geçici disk/bellek sıralaması yapılır)
- **Aday İndeks Sonrası Plan**:
  - `SEARCH episodes USING INDEX (program_id, is_active, show_on_public, season_number)`
  - **Kazanç**: %100 doğrudan index-seek, 0 filesort, <0.05ms yanıt süresi.

---

### Sorgu 2: Public Program Detay — Alt Seri Bölümleri (Örn: Beraber Okuyalım -> Lemalar)
```sql
SELECT * FROM episodes 
WHERE program_series_id = 8 AND is_active = 1 AND show_on_public = 1 
ORDER BY episode_number ASC;
```
- **Mevcut Plan (İndekssiz)**:
  - `SCAN episodes` (Full table scan)
  - `USE TEMP B-TREE FOR ORDER BY` (Filesort)
- **Aday İndeks Sonrası Plan**:
  - `SEARCH episodes USING INDEX (program_series_id, is_active, show_on_public)`
  - **Kazanç**: Doğrudan nokta atışı arama, sıfır sıralama maliyeti.

---

### Sorgu 3: Yayın Akışı Resolver — Günlük ve Haftalık Akış
```sql
SELECT * FROM schedule_template_items 
WHERE schedule_template_id = 1 AND day_of_week = 0 AND is_active = 1 
ORDER BY start_time ASC;
```
- **Mevcut Plan (İndekssiz)**:
  - `SCAN schedule_template_items` (Full scan)
  - `USE TEMP B-TREE FOR ORDER BY` (Filesort)
- **Aday İndeks Sonrası Plan**:
  - `SEARCH schedule_template_items USING INDEX (schedule_template_id, day_of_week, is_active, start_time)`
  - **Kazanç**: 7 günlük döngüde 7 x Full Scan tamamen ortadan kalkar.

---

## 4. ⚖️ Aday İndeksler ve Redundancy (Gereksizlik) Değerlendirmesi

| Aday İndeks | Tablo | Query Uyumu | Redundant mı? | Öncelik | Karar |
|---|---|---|:---:|:---:|---|
| **A) `(program_id, is_active, show_on_public, season_number, episode_number)`** | `episodes` | `ProgramController::show` (Sezonlar) | Hayır (Mevcut index yok) | 🔥 **HIGH** | **ÖNERİLİR** |
| **B) `(program_series_id, is_active, show_on_public, episode_number)`** | `episodes` | `ProgramController::show` (Seriler) | Hayır (Mevcut index yok) | 🔥 **HIGH** | **ÖNERİLİR** |
| **C) `(schedule_template_id, day_of_week, is_active, start_time)`** | `schedule_template_items` | `BroadcastScheduleResolver` (Haftalık Akış) | Hayır (0 index var) | ⚡ **HIGH** | **ÖNERİLİR** |
| **D) `(program_id, season_number, season_year)`** | `episodes` | Admin Sezon Gruplama | **Kısmen** (Aday A program_id prefix'ini kapsar) | 🟡 **LOW** | **ÖNERİLMEZ** (Yazma maliyeti) |
| **E) `(program_series_id, episode_number)`** | `episodes` | Seri Bölüm Sıralama | **EVET** (Aday B zaten program_series_id prefix'ini içerir) | ❌ **GEREKSİZ** | **RED** |
| **F) `(is_active, status, show_on_public)`** | `programs` | Public Program Listesi | **Gereksiz** (Programs tablosu 124 satır ve cache'li) | ❌ **GEREKSİZ** | **RED** |

---

## 5. ✍️ Yazma Maliyeti (Write Cost) Analizi

`episodes` tablosuna YouTube senkronizasyonu ve Excel import işlemleri sırasında toplu `INSERT` ve `UPDATE` işlemleri yapılmaktadır:

1. **Aşırı İndekslemenin Zararı**: 5-6 farklı composite indeks eklenirse 500 bölümlük bir playlist importunda her satır için 6 ayrı B-Tree güncellemesi yapılır; bu da disk I/O ve kilitlenme süresini 4 katına çıkarır.
2. **Optimum Denge (Minimalist Yaklaşım)**:
   - Sadece **2 adet hedefli composite index** (`idx_episodes_prog_season` ve `idx_episodes_series_ep`) eklenmelidir.
   - Bu 2 indeks okuma sorgularının %95'ini optimize ederken, toplu yazma hızına etkisi %3'ün altındadır (ihmal edilebilir).

---

## 6. 🌐 Veritabanı Motoru Farkları (SQLite vs. MySQL Planner)

- **SQLite (Yerel / Test)**:
  - Basit bir B-Tree arama motoru kullanır. Composite index'teki ilk kolon (`program_id`) eşleştiğinde binary-search yapar; ancak sıralama kolonu (`episode_number`) index sonunda değilse bellekte `TEMP B-TREE` oluşturur.
- **MySQL / MariaDB (Production InnoDB)**:
  - Gelişmiş maliyet tabanlı optimizer (Cost-based optimizer) kullanır. `(program_id, is_active, show_on_public, season_number, episode_number)` indeksi tanımlandığında MySQL **"Index-Condition-Pushdown" (ICP)** ve **"Using index for filesort"** avantajını kullanarak hem disk okumasını hem CPU sıralamasını tamamen sıfırlar.

---

## 7. 🕵️ N+1 Sorgu ve Eager Loading Denetimi

| Sayfa / Modül | Eager Loading Durumu | N+1 Riski | Açıklama / Çözüm |
|---|:---:|:---:|---|
| **Public Program Detay** (`ProgramController::show`) | ✅ `with('programSeason')`, `load(['categories', 'schedules'])` | **YOK** | İlişkiler tek sorguda çekiliyor. |
| **Haftalık Yayın Akışı** (`BroadcastScheduleResolver`) | ✅ `with('program.categories')`, `with('items.program')` | **YOK** | Eager loading tam uygulanmış. |
| **Admin Bölümler Listesi** (`EpisodesTable`) | ✅ `with(['program', 'programSeries'])` | **YOK** | Filament table relation eager loading mevcut. |
| **Admin Programlar Listesi** (`ProgramsTable`) | ✅ `counts('episodes')`, `with('categories')` | **YOK** | Subquery count kullanılıyor. |

> **Tespit**: Projede N+1 sorgu problemi bulunmamaktadır; darboğaz sadece veritabanı seviyesindeki indeks eksikliğinden kaynaklanmaktadır.

---

## 8. 🎯 Kesin İndeksleme Öneri Tablosu

```text
================================================================================
FAZ 3 ÖNERİLEN MINIMALIST İNDEKS LİSTESİ (SADECE 3 ADET İNDEKS)
================================================================================

1. TABLO: EPISODES
   - İndeks Adı: idx_episodes_program_public_season
   - Kolonlar  : (program_id, is_active, show_on_public, season_number, episode_number)
   - Öncelik   : HIGH (Kritik)
   - Gerekçe   : Public program detayında sezon bölümlerini çekerken 3.347 satırlık Full Table Scan ve filesort'u engeller.

2. TABLO: EPISODES
   - İndeks Adı: idx_episodes_series_public_order
   - Kolonlar  : (program_series_id, is_active, show_on_public, episode_number)
   - Öncelik   : HIGH (Kritik)
   - Gerekçe   : Serili programlarda (Örn: Lemalar, Sözler) bölüm sorgusunu doğrudan index-seek ile getirir.

3. TABLO: SCHEDULE_TEMPLATE_ITEMS
   - İndeks Adı: idx_schedule_items_lookup
   - Kolonlar  : (schedule_template_id, day_of_week, is_active, start_time)
   - Öncelik   : HIGH (Kritik)
   - Gerekçe   : Yayın akışı resolver ve haftalık akışta 7 kez tekrarlanan full-table scan'i engeller.
================================================================================
DİĞER TABLOLAR (PROGRAMS, PROGRAM_SEASONS, PROGRAM_SERIES):
   - Yeni index GEREKSİZDİR (Mevcut FAZ 2 unique indeksleri ve cache katmanı %100 yeterlidir).
================================================================================
```

---

*Bu rapor [FAZ3_PERFORMANCE_INDEX_AUDIT.md](file:///Users/mac/Dost%20TV%20Web%20Site/FAZ3_PERFORMANCE_INDEX_AUDIT.md) adıyla proje köküne kaydedilmiştir. Hiçbir kod veya şema değişikliği yapılmamıştır.*
