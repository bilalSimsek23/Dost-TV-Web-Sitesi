# DOST TV CMS — FAZ 2 / ProgramSeason & ProgramSeries Veri Bütünlüğü Analiz Raporu

> **Audit Türü**: Read-Only Veri Bütünlüğü, Composite Unique & Index İncelemesi  
> **Tarih**: 17 Ağustos 2026  
> **Proje**: DOST TV Web Sitesi & CMS (Laravel 12 / SQLite & MySQL Uyumu)  
> **İnceleme Durumu**: Read-Only (Hiçbir veri, migration, index veya constraint değiştirilmemiştir).

---

## 1. 🛡️ Read-Only Güvencesi ve Kapsam

Bu analiz, mevcut SQLite veritabanı (`database/database.sqlite`), migration geçmişi ve Eloquent modelleri üzerinde **salt-okunur (read-only)** olarak gerçekleştirilmiştir:
- ❌ Hiçbir migration çalıştırılmamıştır.
- ❌ Hiçbir tablo şeması veya index eklenmemiştir.
- ❌ Hiçbir veritabanı satırı güncellenmemiş veya silinmemiştir.
- ❌ Duplicate merge veya veri temizliği yapılmamıştır.

### Genel Tablo Satır İstatistikleri (Mevcut Veritabanı):
- **Programs**: 154 kayıt
- **ProgramSeason**: 80 kayıt
- **ProgramSeries**: 14 kayıt
- **Episode**: 3.347 kayıt

---

## 2. 🗄️ `program_seasons` Tablosu Analizi

### A. Mevcut Kolon Yapısı ve Tipleri:
| Kolon Adı | Veri Tipi | Nullable | Açıklama |
|---|---|:---:|---|
| `id` | `bigint unsigned` | Hayır | Primary Key (Otomatik Artan) |
| `program_id` | `foreignId` | Hayır | `programs.id` (onDelete: cascade) |
| `season_number` | `unsignedSmallInteger` | **Evet** | Sezon numarası (`1`, `2`, `3` ...) |
| `season_year` | `string(20)` | **Evet** | Sezon yılı (`2017`, `2022-2023` vb.) |
| `youtube_playlist_url` | `string(500)` | **Evet** | YouTube Playlist bağlantısı |
| `youtube_playlist_title` | `string` | **Evet** | YouTube'dan çekilen playlist başlığı |
| `last_youtube_sync_at` | `timestamp` | **Evet** | Son başarılı senkronizasyon zamanı |
| `created_at` / `updated_at` | `timestamp` | **Evet** | Zaman damgaları |

*Mevcut İndeks*: `$table->index(['program_id', 'season_number', 'season_year'], 'prog_season_idx');` (Normal / Non-Unique).

### B. Kombinasyon ve Duplicate Analizi (80 Kayıt Üzerinde):
1. **Kombinasyon A (`program_id + season_number`)**:
   - Duplicate Kayıt Sayısı: **0**
2. **Kombinasyon B (`program_id + season_number + season_year`)**:
   - Duplicate Kayıt Sayısı: **0**
3. **Kombinasyon C (`program_id + COALESCE(season_number, 0) + COALESCE(season_year, 'NULL')`)**:
   - Duplicate Kayıt Sayısı: **0**

> **Önemli Tespit**: Mevcut veritabanında aynı program altında hiçbir duplicate sezon kaydı bulunmamaktadır. Veri tamamen temizdir.

---

## 3. ⚠️ NULL `season_year` ve SQL UNIQUE Davranışı

Veritabanında `program_seasons` tablosunda **12 adet** `season_year = NULL` olan kayıt, `episodes` tablosunda ise **974 adet** `season_year = NULL` olan kayıt mevcuttur.

### ANSI SQL ve Veritabanı Motoru Davranışları:
1. **Standart Davranış (SQLite, MySQL 8.x, PostgreSQL)**:
   - SQL standardına göre `NULL != NULL` kabul edilir.
   - Bu nedenle `UNIQUE (program_id, season_number, season_year)` kısıtı eklendiğinde:
     - `(program_id: 10, season_number: 1, season_year: NULL)`
     - `(program_id: 10, season_number: 1, season_year: NULL)`
   - Veritabanı motoru 2. satırı **reddetmez** ve mükerrer kayda izin verir!
2. **Motor Bazlı Çözüm Yolları**:
   - **SQLite (v3.31+)**: Kısmi (Partial) Unique Index kullanılır:
     ```sql
     CREATE UNIQUE INDEX prog_season_null_year_unique ON program_seasons (program_id, season_number) WHERE season_year IS NULL;
     CREATE UNIQUE INDEX prog_season_with_year_unique ON program_seasons (program_id, season_number, season_year) WHERE season_year IS NOT NULL;
     ```
   - **MySQL 8.0.13+**: Sanal üretilmiş kolon (Generated Column) veya Fonksiyonel Index:
     ```sql
     ALTER TABLE program_seasons ADD COLUMN season_year_normalized VARCHAR(20) GENERATED ALWAYS AS (COALESCE(season_year, '')) STORED;
     ALTER TABLE program_seasons ADD UNIQUE KEY prog_season_unique (program_id, season_number, season_year_normalized);
     ```
   - **PostgreSQL 15+**: `UNIQUE NULLS NOT DISTINCT (program_id, season_number, season_year)`

---

## 4. 📅 `season_year` Değer Dağılımı

Mevcut veritabanındaki 80 `program_seasons` ve 3.347 `episodes` kaydındaki yıl dağılımı:

| Format Türü | `program_seasons` Adet | `episodes` Adet | Örnekler |
|---|:---:|:---:|---|
| **NULL (Sezonsuz / Yılsız)** | 12 (%15.0) | 974 (%29.1) | `NULL` |
| **Tek Yıl (`YYYY`)** | 53 (%66.25) | 1.546 (%46.2) | `2008`, `2013`, `2017`, `2018`, `2019`, `2020`, `2021`, `2022`, `2023`, `2024`, `2025`, `2026` |
| **Yıl Aralığı (`YYYY-YYYY`)** | 15 (%18.75) | 827 (%24.7) | `2021-2024`, `2022-2023`, `2023-2024`, `2023-2025`, `2024-2025`, `2024-2026`, `2025-2026` |
| **Hatalı / Geçersiz Format** | **0** | **0** | Bulunmadı |

---

## 5. 📚 `program_series` Tablosu Analizi

### Mevcut Kolon Yapısı:
| Kolon Adı | Veri Tipi | Nullable | Açıklama |
|---|---|:---:|---|
| `id` | `bigint unsigned` | Hayır | Primary Key |
| `program_id` | `foreignId` | Hayır | `programs.id` (onDelete: cascade) |
| `program_season_id` | `foreignId` | **Evet** | `program_seasons.id` (onDelete: set null) |
| `name` | `string` | Hayır | Seri adı (`Lemalar`, `Sözler`, `1-10. Söz`) |
| `slug` | `string` | **Evet** | URL dostu slug (`lemalar`, `sozler`) |
| `youtube_playlist_url` | `string(500)` | **Evet** | Seriye özel YouTube Playlist URL |
| `youtube_playlist_title` | `string` | **Evet** | YouTube playlist başlığı |
| `last_youtube_sync_at` | `timestamp` | **Evet** | Son başarılı sync tarihi |
| `sort_order` | `integer` | Hayır | Sıralama önceliği (default: 0) |
| `created_at` / `updated_at` | `timestamp` | **Evet** | Zaman damgaları |

*Mevcut İndeksler*:
- `prog_series_lookup_idx` on `(program_id, program_season_id, name)` (Normal / Non-Unique)
- `prog_series_sort_idx` on `(program_id, sort_order)` (Normal / Non-Unique)

---

## 6. 🔍 `program_series` Duplicate Analizi

Toplam 14 `program_series` kaydı incelenmiştir:
1. **Kombinasyon A (`program_id + name`)**: **0 duplicate**
2. **Kombinasyon B (`program_id + program_season_id + name`)**: **0 duplicate**
3. **Kombinasyon C (`program_season_id + name`)**: **0 duplicate**
4. **Kombinasyon D (`slug`)**: **0 duplicate**

### İş Mantığı Değerlendirmesi:
- **Aynı isim farklı programda bulunabilir mi?**: **EVET, kesinlikle bulunabilmelidir**. Örneğin "Özel Bölümler", "Sözler", "Kısa Kesitler" gibi seri isimleri farklı televizyon programları altında bağımsız olarak yer alabilir.
- **Slug kapsamı**: `slug` global unique olmamalı; `program_id` kapsamlı (`program_id, slug`) olmalıdır.
- **Gerçek Kimlik (Identity)**: `(program_id, name)` bir program altındaki seriler için en doğal ve güvenli kimliktir.

---

## 7. 🔗 `episodes` Tablosu ile Çapraz Kontrol (Cross-Check)

| Kontrol Kriteri | Sonuç | Durum |
|---|:---:|---|
| Var olmayan `program_series_id`'ye işaret eden bölüm | **0** | ✅ Tam Bütünlük |
| Bölümün `program_id`'si ile bağlı olduğu serinin `program_id`'si farklı olan kayıt | **0** | ✅ Çapraz Kirlilik Yok |
| `season_number`'ı olan ama `program_seasons` kaydı olmayan bölüm | **0** | ✅ Birebir Eşleşme |
| Hiç bölümü olmayan `program_seasons` kaydı (Boş Sezon) | **0** | ✅ Yetim Kayıt Yok |
| Hiç bölümü olmayan `program_series` kaydı (Boş Seri) | **0** | ✅ Yetim Kayıt Yok |

---

## 8. 📻 Playlist URL Kullanım Analizi

| Kontrol | Adet | Değerlendirme |
|---|:---:|---|
| Birden fazla `ProgramSeason`'da kullanılan aynı Playlist URL | **0** | Mükerrer URL yok |
| Birden fazla `ProgramSeries`'te kullanılan aynı Playlist URL | **0** | Mükerrer URL yok |

> **Öneri**: `youtube_playlist_url` üzerine veritabanı seviyesinde `UNIQUE` kısıtı **konulmamalıdır**. Televizyon yayıncılığında ortak özel yayınlar veya arşiv tekrarlarında aynı playlist'in iki farklı program/sezona bağlanması gerekebilir; bu durum uygulama seviyesinde yönetilmelidir.

---

## 9. 🔍 Özel İnceleme: Hikmet Arayışları (ID: 105)

Hikmet Arayışları, **9 Sezon ve 275 Bölümden** oluşan, her sezonu farklı bir yıla/yıl aralığına ve bağımsız YouTube playlist'ine sahip çok sezonlu referans programdır:

| Sezon ID | Sezon No | Sezon Yılı | YouTube Playlist ID | Bölüm Sayısı |
|:---:|:---:|:---:|---|:---:|
| 4 | Sezon 1 | `2017` | `PLCUJEKDliLdDtENZ9Pe7G6F1HppLsfOxF` | 29 |
| 5 | Sezon 2 | `2018` | `PLCUJEKDliLdDVp9U_oyV4vqJgx5oRY0gR` | 17 |
| 6 | Sezon 3 | `2019` | `PLCUJEKDliLdDoHP3-ecmCPMdeziG-bEnZ` | 13 |
| 7 | Sezon 4 | `2020` | `PLCUJEKDliLdBSJyUf4-roDYGIxa5yEvky` | 17 |
| 8 | Sezon 5 | `2021` | `PLCUJEKDliLdBXZoLo-JKYp84FNl9wG9yj` | 24 |
| 9 | Sezon 6 | `2022-2023` | `PLCUJEKDliLdDDwNQSvFKVXjBPQLgkK68J` | 47 |
| 10 | Sezon 7 | `2023-2024` | `PLCUJEKDliLdBw5pE-zWBflOKmBz0e7waG` | 45 |
| 11 | Sezon 8 | `2025` | `PLCUJEKDliLdB7BD77H7gkI0b_psKUGcb5` | 42 |
| 12 | Sezon 9 | `2026` | `PLCUJEKDliLdD1Po8o9Xff6LLtWbVZ9DFK` | 27 |

- Duplicate Sezon: **0**
- Uyuşmazlık: **0**

---

## 10. 🔍 Özel İnceleme: Beraber Okuyalım (ID: 106)

Beraber Okuyalım, **6 Alt Seri ve 694 Bölümden** oluşan, her alt serisi bağımsız YouTube playlist'ine sahip çok serili referans programdır:

| Seri ID | Seri Adı | Slug | Bağlı Sezon ID | YouTube Playlist ID | Bölüm Sayısı |
|:---:|---|---|:---:|---|:---:|
| 1 | **Sözler** | `sozler` | 15 (Sezon 1) | `PLKCCm5tjpAnix2rbBtNfqdXNpC56anPXG` | 245 |
| 4 | **1-10. Söz** | `1-10-soz` | 18 (Sezon 2) | `PLKCCm5tjpAnhuJ9--4G2594NAsl647IO5` | 40 |
| 5 | **11-20. Söz** | `11-20-soz` | 19 (Sezon 3) | `PLKCCm5tjpAngcRV9XSUZYVAvQ4Ev2T_Ah` | 61 |
| 6 | **21-30. Söz** | `21-30-soz` | 20 (Sezon 4) | `PLKCCm5tjpAnjWxSPLTHJulUAXbdRSfxKh` | 136 |
| 7 | **31-33. Söz** | `31-33-soz` | 21 (Sezon 5) | `PLKCCm5tjpAnhz4mXubtuqxZi7ak5E7wcU` | 69 |
| 8 | **Lemalar** | `lemalar` | 22 (Sezon 6) | `PLKCCm5tjpAngeAZhMP4tsQVl-NoyJkUjL` | 143 |

- Duplicate Seri Adı: **0**
- Yetim Bölüm: **0**

---

## 11. 💡 İş Kuralı ve Kimlik (Identity) Yanıtları

1. **`ProgramSeason` için güvenli identity nedir?**
   - **Cevap**: `(program_id, season_number, season_year)` üçlüsüdür.
2. **`season_year` NULL ise duplicate nasıl engellenmeli?**
   - **Cevap**: SQL standardında `NULL != NULL` olduğu için düz composite unique yetersiz kalır. SQLite için Partial Unique Index (`WHERE season_year IS NULL` / `WHERE season_year IS NOT NULL`), MySQL için ise sanal kolon (`COALESCE(season_year, '')`) üzerine composite unique index oluşturulmalıdır.
3. **`ProgramSeries` için güvenli identity nedir?**
   - **Cevap**: `(program_id, name)` çiftidir. Aynı program altında aynı isimde iki seri olamaz.
4. **Series aynı isimle farklı season'larda bulunabilir mi?**
   - **Cevap**: Televizyon yayıncılığında "Lemalar" veya "Sözler" serisi programın ana parçasıdır. Sezon değişse bile aynı programda 2 tane "Lemalar" serisi açılmamalıdır. Bu nedenle program seviyesinde `(program_id, name)` tekil olmalıdır.
5. **Series aynı isimle farklı programlarda bulunabilir mi?**
   - **Cevap**: **Evet**. Başka bir program da "Özel Bölümler" veya "Sözler" serisine sahip olabilir.
6. **Slug global unique mi olmalı, program scope'lu mu?**
   - **Cevap**: **Program scope'lu olmalıdır** (`program_id, slug`). URL yapısı `/programlar/{program:slug}?seri={series:slug}` şeklinde program bağlamında çalıştığı için global tekillik gerekmez.

---

## 12. ⚡ Performans ve İndeksleme Önerileri

Gerçek Controller (`ProgramController`), Resolver ve Filament sorguları incelenerek hazırlanan hedef indeks planı:

1. **`episodes` Tablosu İçin**:
   - `INDEX episodes_prog_active_season_idx (program_id, is_active, show_on_public, season_number)`
     - *Kullanım*: Program detay sayfasında sezona göre 500+ bölüm listelenirken filesort'u engeller.
   - `INDEX episodes_series_active_idx (program_series_id, is_active, show_on_public, episode_number)`
     - *Kullanım*: Program detay sayfasında seriye göre bölümleri çekerken doğrudan index-scan sağlar.
2. **`program_seasons` Tablosu İçin**:
   - Mevcut `prog_season_idx (program_id, season_number, season_year)` mevcuttur; Faz 2 migration'ında composite unique kuralı ile güçlendirilebilir.
3. **`program_series` Tablosu İçin**:
   - `INDEX prog_series_prog_name_idx (program_id, name)`
     - *Kullanım*: YouTube sync ve import sırasında `findSeries` / `findOrCreateSeries` aramalarını hızlandırır.

---

## 13. 🚥 Risk Sınıflandırması

| Seviye | Konu | Neden | Etki |
|---|---|---|---|
| **INFO** | Temiz Veri Durumu | Mevcut veritabanında 0 duplicate sezon ve 0 duplicate seri tespit edildi | İleride composite unique migration'ı yazılırken veri temizliği gerekmeyecektir. |
| **MEDIUM** | NULL `season_year` Davranışı | SQLite ve MySQL'de standart UNIQUE kısıtının NULL değerleri duplicate saymaması | Migration hazırlanırken partial index veya virtual column desteği gerektirir. |
| **LOW** | Eksik Composite Query İndeksleri | 500+ bölümlü dizilerde index-scan yerine filesort yapılması | Yüksek trafik altında küçük gecikmelere sebep olabilir. |

---

## 14. 📋 Sonuç ve Migration Ön Hazırlık Planı

```text
============================================================
FAZ 2 HAZIRLIK TABLOSU (MIGRATION ÖNCESİ ÖZET)
============================================================

PROGRAM_SEASONS
- Önerilen Unique        : (program_id, season_number, season_year) [NULL-safe partial/functional index]
- Önerilen Normal Index  : Mevcut prog_season_idx korunacak
- Önce Temizlenecek Veri : 0 adet (Veri tamamen temiz)
- Migration Riski        : DÜŞÜK (Mevcut veriyle %100 uyumlu)

PROGRAM_SERIES
- Önerilen Unique        : (program_id, name)
- Önerilen Normal Index  : (program_id, sort_order) [Mevcut]
- Önce Temizlenecek Veri : 0 adet (Veri tamamen temiz)
- Migration Riski        : DÜŞÜK (Mevcut veriyle %100 uyumlu)

EPISODES
- Önerilen İndeksler     : 
    1. (program_id, is_active, show_on_public, season_number)
    2. (program_series_id, is_active, show_on_public, episode_number)
- Integrity Sorunları    : 0 adet (Yetim bölüm, yabancı seri veya uyuşmazlık bulunamadı)
============================================================
```

> **Özet Sonuç**: DOST TV veritabanındaki Sezon, Seri ve Bölüm ilişkileri **tamamen tutarlı, yetim kayıtsız ve 0 duplicate ile kusursuz durumdadır**. İleride Faz 2 migration'ı oluşturulmak istendiğinde hiçbir veri kaybı riski veya ön temizlik ihtiyacı olmadan güvenle uygulanabilecektir.
