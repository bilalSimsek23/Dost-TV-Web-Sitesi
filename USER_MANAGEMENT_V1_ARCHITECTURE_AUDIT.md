# DOST TV CMS — Kullanıcı Yönetimi V1 Uygulama Öncesi Read-Only Mimari Analiz Raporu

> **Audit Türü**: Read-Only Mimari Analiz, Yetkilendirme Haritası, Gap Analizi ve Uygulama Planı  
> **Tarih**: 17 Ağustos 2026  
> **Proje**: DOST TV Web Sitesi & CMS (Laravel 12 + Filament v4)  
> **İnceleme Durumu**: Read-Only (Hiçbir kod, migration, veritabanı kaydı veya config değiştirilmemiştir).

---

## 1. 👤 Mevcut `User` Modeli ve `users` Tablosu Durumu

### Veritabanı Kolonları (`users` Tablosu)
- `id` (bigint, PK)
- `name` (string)
- `email` (string, UNIQUE)
- `email_verified_at` (timestamp, nullable)
- `password` (string, hashed)
- `remember_token` (string, nullable)
- `created_at`, `updated_at` (timestamps)
- `role` (string, default: 'editor')
- `is_active` (boolean, default: 1)
- `avatar_url` (string, nullable)
- `phone` (string, nullable)
- `last_login_at` (datetime, nullable)
- `last_login_ip` (string, nullable)
- `deleted_at` (timestamp, nullable - SoftDeletes)

### Model Özellikleri (`app/Models/User.php`)
- **Traits**: `HasFactory`, `Notifiable`, `SoftDeletes`
- **Interface**: `implements FilamentUser`
- **Mevcut Roller**:
  ```php
  public const ROLES = [
      'super_admin' => 'Süper Admin',
      'administrator' => 'Yönetici',
      'designer' => 'Tasarımcı',
      'editor' => 'Editör',
      'content_manager' => 'İçerik Yöneticisi',
  ];
  ```
- **Panel Erişim Kontrolü (`canAccessPanel`)**:
  ```php
  public function canAccessPanel(Panel $panel): bool
  {
      return array_key_exists($this->role, self::ROLES)
          && (bool) $this->is_active
          && ! $this->trashed();
  }
  ```
  *(Pasif veya arşivlenmiş kullanıcıların panele girişi model seviyesinde engellenmiştir).*

---

## 2. 🖥️ Filament Kullanıcı Yönetimi Mevcut Durumu

### Dosya Haritası:
- Kaynak: `app/Filament/Resources/Users/UserResource.php`
- Form Şeması: `app/Filament/Resources/Users/Schemas/UserForm.php`
- Tablo Şeması: `app/Filament/Resources/Users/Tables/UsersTable.php`
- Policy: `app/Policies/UserPolicy.php`

### Form Alanları ve Mevcut Davranış:
1. **Profil Fotoğrafı (`avatar_url`)**: FileUpload bileşeni ile mevcut (V1 hedefinde kaldırılacak).
2. **Ad Soyad (`name`)**: Zorunlu metin alanı.
3. **E-posta (`email`)**: Zorunlu ve tekil e-posta.
4. **Telefon (`phone`)**: Opsiyonel metin (`05xx xxx xx xx` placeholder; standart maske/format yok).
5. **Rol (`role`)**: `Select` bileşeni. Administrator'ın Super Admin rolünü değiştirmesini engelleyen kısıtlama mevcut.
6. **Hesap Durumu (`is_active`)**: `Toggle` bileşeni. Kendi kendini pasife alma ve son aktif Süper Admin'i pasife alma engellenmiş durumda.
7. **Şifre (`password`)**: Form üzerinde elle giriliyor (Davet sistemi henüz yok). Minimum 8 karakter kuralı var.

---

## 3. 🔐 Yetkilendirme (Authorization) Sistemi Analizi

### Genel Durum:
- **Spatie Permission paketi KULLANILMAMAKTADIR**.
- Yetkilendirme tamamen **yerel Laravel Policy'leri, `User::hasRole` / `hasAnyRole` metotları ve Filament `canAccess()` hook'ları** üzerinden yürütülmektedir.

### Mevcut Yetki Kapsamı:
| Modül / Sayfa | Yetkili Roller | Uygulama Yeri |
|---|---|---|
| **Kullanıcı Yönetimi (`UserResource`)** | `super_admin`, `administrator` | `UserPolicy` |
| **Site Ayarları (`SiteSettings`)** | Tüm panel kullanıcıları (Açık!) | Eksik `canAccess` |
| **Tema Ayarları (`ThemeSettings`)** | `super_admin`, `administrator`, `designer` | `ThemeSettings::canAccess` |
| **Top Header (`TopHeader`)** | `super_admin`, `administrator`, `designer`, `editor` | `TopHeader::canAccess` |
| **Sayfa Düzeni (`SiteLayout/*`)** | `super_admin`, `administrator`, `designer` | `canAccess` |
| **Program/Bölüm/Yayın Akışı** | Tüm panel kullanıcıları (`editor` dahil) | `canAccessPanel` |

---

## 4. 🔑 Auth, Session ve Şifre Altyapısı

### 1. Şifremi Unuttum (Forgot Password)
- `AdminPanelProvider` üzerinde `->passwordReset()` tanımlı değildir; panel girişinde şu an "Şifremi Unuttum" linki görünmemektedir.
- Laravel'in standart `password_reset_tokens` tablosu mevcuttur.

### 2. E-Posta Altyapısı
- `config/mail.php` hazır; `MAIL_MAILER` yerelde `log` driver kullanır, production'da SMTP/SES/Postmark ile çalışabilir.

### 3. Kullanıcı Davet Sistemi
- Mevcut sistemde davet mekanizması yoktur. Kullanıcı oluşturulurken şifre zorunlu tutulmaktadır.

### 4. 72 Saatlik Davet Token Mimarisi
- `password_reset_tokens` tablosu 60 dakikalık standart şifre sıfırlama içindir.
- 72 saatlik davet tokenları, daveti tekrar gönderme, daveti iptal etme ve davet eden kullanıcıyı kaydetme için **özel ve hafif bir `user_invitations` tablosu** çok daha temiz ve güvenlidir.

### 5. Pasif Kullanıcı Session Sonlandırma
- Projede `config/session.php` içinde `'driver' => 'database'` tanımlıdır ve `sessions` tablosunda `user_id` tutulmaktadır.
- Bir kullanıcı pasife alındığında:
  ```php
  DB::table('sessions')->where('user_id', $user->id)->delete();
  ```
  çağrıldığında kullanıcının tüm tarayıcılardaki oturumları **anında sonlandırılır**.

---

## 5. 📜 Audit / İşlem Geçmişi Altyapısı

- Projede şu an herhangi bir Activity/Audit log paketi veya tablosu **bulunmamaktadır**.
- DOST TV için teknik JSON yığını yerine editör dostu Türkçe cümleler üreten minimalist bir tablo yapısı önerilmektedir:

### Önerilen Minimalist `audit_logs` Tablosu:
```sql
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NULL,
    user_name VARCHAR(255) NOT NULL, -- Kullanıcı silinse bile adı korunur
    action VARCHAR(50) NOT NULL,     -- created, updated, deleted, archived, published, imported, synced
    module VARCHAR(50) NOT NULL,     -- programs, episodes, seasons, series, schedules, users
    record_id BIGINT NULL,
    record_title VARCHAR(255) NOT NULL, -- Örn: "Akla Kapı 14. Bölüm"
    description VARCHAR(500) NOT NULL,  -- Örn: "Yasemin, Akla Kapı 14. Bölüm'ü ekledi."
    changes JSON NULL,               -- Değişen alanlar (eski/yeni)
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL,
    INDEX idx_audit_user_date (user_id, created_at),
    INDEX idx_audit_created_at (created_at)
);
```
- **6 Aylık Saklama**: `audit:prune` konsol komutu ile `created_at < now()->subMonths(6)` otomatik temizlenir.

---

## 6. 🗺️ İçerik Modülleri ve İşlem Haritası

| Modül | İşlem | İlgili Sınıf / Action | Audit Noktası |
|---|---|---|---|
| **Programlar** | Ekle / Güncelle / Sil | `ProgramResource` (`CreateProgram`, `EditProgram`, `ProgramsTable`) | Model Observer / Filament Hook |
| **Programlar** | Arşivle / Yayına Al | `ProgramsTable` (`archive`, `togglePublic`, `toggleFeatured`) | Filament Action |
| **Bölümler** | Ekle / Güncelle / Sil | `EpisodeResource`, `EpisodesRelationManager` | Model Observer / Filament Action |
| **Sezon / Seri** | Oluştur / Düzenle | `YoutubePlaylistImportPage`, `ProgramSeriesManagementPage` | Service / Action |
| **Yayın Akışı** | Düzenle / Saat Değiştir | `ScheduleCalendarPage`, `ScheduleTemplateResource` | Livewire / Filament Action |
| **Yayın Akışı** | Excel İçe Aktar | `ScheduleExcelImportService::import` | Service Action |
| **YouTube Sync** | Senkronizasyon | `YouTubePlaylistSyncService::syncProgramPlaylist` | Service Action |
| **Kullanıcılar** | Ekle / Pasife Al / Sil | `UserResource` (`toggleActive`, `archive`, `CreateUser`) | Filament Action |

---

## 7. 🎯 Hedef Yetki Seviyeleri (3 Çekirdek Rol) ve Gap Analizi

```text
================================================================================
HEDEF YETKİ SEVİYELERİ MATRİSİ
================================================================================

1. SUPER ADMIN:
   - Tüm içerik ve yayın operasyonları (Program, Bölüm, Sezon, Seri, Akış, Excel, YouTube)
   - Kullanıcı Yönetimi (Tam yetki)
   - Roller Yönetimi (Tam yetki)
   - Genel İşlem Geçmişi (Tüm kullanıcıları görme ve filtreleme)
   - Sistem ve Görünüm Ayarları (Site Ayarları, Tema, Header/Footer düzeni)

2. YÖNETİCİ (ADMINISTRATOR):
   - Tüm içerik ve yayın operasyonları (Program, Bölüm, Sezon, Seri, Akış, Excel, YouTube)
   - Kullanıcı Yönetimi (Editör ve Yöneticileri yönetebilir, Süper Admin'e dokunamaz)
   - Genel İşlem Geçmişi (Tüm hareketleri görebilir)
   - ⛔ Roller Yönetimine ERİŞEMEZ
   - ⛔ Sistem ve Görünüm Ayarlarına ERİŞEMEZ
   - ⛔ Bir kullanıcıyı Süper Admin yapamaz

3. EDİTÖR:
   - Tüm içerik ve yayın operasyonları (Program, Bölüm, Sezon, Seri, Akış, Excel, YouTube)
   - Kendi Profilini Yönetme (Ad, Telefon, Şifre)
   - "Benim İşlemlerim" (Sadece kendi hareket geçmişini profilinde görme)
   - ⛔ Kullanıcı Yönetimine ERİŞEMEZ
   - ⛔ Roller Yönetimine ERİŞEMEZ
   - ⛔ Genel İşlem Geçmişine ERİŞEMEZ
   - ⛔ Sistem ve Görünüm Ayarlarına ERİŞEMEZ
================================================================================
```

---

## 8. 🎭 Roller V1 Modeli Değerlendirmesi

- **Çekirdek Seviyeler (Değiştirilemez)**: `super_admin`, `administrator`, `editor`.
- **Özel Roller (`roles` Tablosu)**:
  - `name`: "İçerik Editörü", "Yayın Masası", "Genel Yayın Yönetmeni" vb.
  - `base_role`: `administrator` veya `editor` *(Güvenlik Kuralı: Özel roller arayüzden `super_admin` yetki seviyesinde oluşturulamaz!)*.
  - `description`: İsteğe bağlı açıklama.
  - `is_active`: Aktif/Pasif durumu.

---

## 9. 📋 Kullanıcı V1 Gap Analizi Tablosu

| Özellik / Hedef | Mevcut Durum | Gap | Gerekli Değişiklik |
|---|:---:|---|---|
| **Profil Fotoğrafı Kaldırma** | VAR (`avatar_url`) | Hedefte yok | Form ve tablodan kaldırılacak |
| **Telefon Formatı (+90 standardı)** | VAR (Serbest metin) | Maskeleme yok | `+90` sabit ön ek ve 10 haneli format eklenecek |
| **72 Saatlik Davet Sistemi** | YOK | Şifre elle giriliyor | `user_invitations` tablosu + Mailable + Davet ekranı |
| **Daveti İptal / Tekrar Gönder** | YOK | Yok | Tablo action'ları eklenecek |
| **Pasif Kullanıcıya Şifre Linki** | YOK | Sadece toggle var | Aktifleştirmede şifre belirleme maili tetiklenecek |
| **Minimum Şifre (5 Karakter)** | VAR (8 Karakter) | 8 yerine 5 isteniyor | Validation `min:5` olarak güncellenecek |
| **Kendi Profilinde Düzenleme** | KISMEN | Özel profil sayfası yok | Custom Profile sayfası (`Ad, Telefon, Şifre`) |
| **Benim İşlemlerim Sekmesi** | YOK | Yok | Profil sayfasına filtrelenmiş Audit tablosu eklenecek |

---

## 10. 🚀 Önerilen Fazlı Uygulama Planı

```text
FAZ U1: Role & Authorization Temel Modeli
- roles tablosu ve Role modeli
- 3 çekirdek yetki (super_admin, administrator, editor) policy güncellemeleri
- Sistem ayarları (SiteSettings, ThemeSettings) erişim kilitleri

FAZ U2: User Formu & Şema Sadeleştirmesi
- Profil fotoğrafının kaldırılması
- +90 telefon maskesi ve DB standardizasyonu
- min:5 şifre kuralı

FAZ U3: Davet (Invitation) ve Auth Altyapısı
- user_invitations tablosu ve InvitationService
- 72 saatlik davet e-postası ve şifre belirleme ekranı
- Davet iptal ve tekrar gönderme action'ları
- Pasifleşmede anında session sonlandırma

FAZ U4: Audit (İşlem Geçmişi) Altyapısı
- audit_logs tablosu ve AuditLogService
- 6 aylık saklama ve otomatik temizleme komutu

FAZ U5: Modül Audit Entegrasyonları
- Program, Bölüm, Sezon, Seri, Yayın Akışı, Excel Import ve YouTube Sync hareketlerinin kaydedilmesi

FAZ U6: Profil & "Benim İşlemlerim"
- Kullanıcı kendi profil ekranı (Ad, Telefon, Şifre)
- Editör için "Benim İşlemlerim" geçmiş sekmesi

FAZ U7: Güvenlik Sıkılaştırma & Kapsamlı Testler
- Role/User/Audit testlerinin yazılması ve %100 yeşil doğrulama
```

---

## 11. 🔒 Uygulamaya Başlamadan Önce Çözülmesi Gereken Kritik Kararlar

1. **Davet Sistemi ve Mail Ortamı**:
   - Yerel geliştirmede davet mailleri `storage/logs/laravel.log` dosyasına mı yazılsın, yoksa yerel bir test mail servisi mi kullanılacak? *(Tavsiye: Yerelde `log` mailer ile link terminal/logda anında test edilebilir).*
2. **Kullanıcı Tablosundaki Mevcut `role` Kolonu**:
   - `users.role` kolonu doğrudan korunup `roles` tablosuna `role_id` üzerinden mi bağlanmalı, yoksa `role` string alanı base_role olarak kalmaya devam mı etmeli? *(Tavsiye: `role_id` eklenip geriye dönük uyumluluk için `role` kolonu base_role fallback olarak korunmalıdır).*
3. **Mevcut 1 Adet Admin Hesabının Durumu**:
   - `ID: 1` (`admin@dosttv.com`) `super_admin` olarak sistemin ana çekirdek yöneticisi olarak kalacaktır.

---

*Bu rapor [USER_MANAGEMENT_V1_ARCHITECTURE_AUDIT.md](file:///Users/mac/Dost%20TV%20Web%20Site/USER_MANAGEMENT_V1_ARCHITECTURE_AUDIT.md) adıyla proje köküne kaydedilmiştir. Hiçbir kod veya şema değişikliği yapılmamıştır.*
