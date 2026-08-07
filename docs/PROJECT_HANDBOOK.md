# DOST TV CMS — Proje El Kitabı & Sürdürülebilir Veri Standardı

## 📌 Sürdürülebilir Veri Standardı (Tek Veri Kaynağı)

Gelecekte değişebilecek, editör tarafından yönetilecek veya birden fazla yerde kullanılan bilgiler kod içine sabit yazılamaz. Tek bir veri kaynağından yönetilir. Tasarım, güvenlik ve uygulama mimarisi ise kodda kalır.

---

### 🏛️ Tek Veri Kaynağı Haritası

| Bilgi / Veri | Veri Kaynağı | Açıklama |
| :--- | :--- | :--- |
| **Site Adı** | `SiteSetting.site_name` | Tüm sayfalarda ve header/footer'da ortak kullanılır. |
| **Logo** | `SiteSetting.logo` | Header, footer ve kamuya açık önizlemelerde tek kaynaktır. |
| **Favicon** | `SiteSetting.favicon` | Tüm HTML head etiketlerinde dinamik render edilir. |
| **Telefon** | `SiteSetting.phone` | `tel:` formatında otomatik link üretilir. |
| **E-posta** | `SiteSetting.email` | `mailto:` formatında otomatik link üretilir. |
| **Adres** | `SiteSetting.address` | Kurumsal iletişim alanlarında ortak kullanılır. |
| **Sosyal Medya** | `SiteSetting.*_url` | Boş olan bağlantı otomatik gizlenir (`target="_blank"`). |
| **Canlı TV** | `route('live.tv')` + `SiteSetting.live_tv_*` | Gerçek Laravel URL üreticisi kullanılır. |
| **Canlı Radyo** | `route('live.radio')` + `SiteSetting.live_fm_*` | Gerçek Laravel URL üreticisi kullanılır. |
| **Header Menüsü** | `Menu` (`header_primary`) | Dinamik hiyerarşik menü altyapısı. |
| **Footer Bağlantıları** | `Page` (`corporate` & `show_in_footer`) | Kurumsal sayfalar otomatik taranır. |
| **Telif Yılı** | `SiteSetting.copyright_text` + `{year}` | Otomatik güncel yıla (`now()->year`) dönüşür. |
| **Font & Tema** | `ThemeSetting` & `FontFamily` | Seçilen aktif marka teması dinamik uygulanır. |

---

### 🚫 Dinamik Olmaması Gereken Teknik Bileşenler

Aşağıdaki bileşenler kodda ve mimari seviyede kalmalıdır:
- HTML yapısı, Tailwind grid sınıfları ve responsive breakpoint'ler.
- Security kuralları ve Laravel Policy sınıfları.
- Route tanımları (`routes/web.php`).
- Eloquent Model ilişkileri ve veritabanı migration sınıfları.
- Gizli `.env` anahtarları (veritabanı şifreleri, SMTP şifreleri, APP_KEY).

---

## ⚡ PERFORMANCE FIRST (Performans Odaklı Geliştirme Prensipleri)

Her yeni özellikte ve kod geliştirmesinde aşağıdaki 6 performans denetim sorusu sorulmalıdır:

1. **Sorgu Optimizasyonu (N+1)**: Bu değişiklik veritabanına ek sorgu yükü getiriyor mu? (`with()` eager loading kullanılmalı).
2. **Veri Tekrarı**: Aynı veri döngülerde veya bileşenlerde tekrar tekrar mı sorgulanıyor?
3. **Önbellekleme (Cache)**: Sık erişilen ancak nadir değişen içerikler önbelleğe alındı mı? (`SiteCache`).
4. **Sayfa Hızı (Response Time)**: Ziyaretçinin public sayfa yükleme süresi olumsuz etkileniyor mu?
5. **Görsel & Medya (Lazy Load)**: Ekran dışındaki tüm görsellerde `loading="lazy"` kullanılıyor mu?
6. **Bileşen Tekrar Kullanılabilirliği**: Blade & Livewire component'leri modüler ve performanslı yazıldı mı?

> **Model & Veri Yapısı Notu**: SiteSetting genel site ayarları için kullanılabilir; modüle özel karmaşık veriler büyüdüğünde ayrı model değerlendirilmelidir.

---

## 🚀 Geliştirme Sunucusu & Yükleme Limitleri (Development Server)

Duyuru ve görsel yüklemelerini test etmek için geliştirme sunucusu şu komutla başlatılır:

```bash
composer serve
```

**Aktif Limitler**:
- Uygulama Limiti: **10 MB** (`maxSize(10240)`)
- PHP `upload_max_filesize`: **12 MB**
- PHP `post_max_size`: **16 MB**
- PHP `memory_limit`: **256 MB**

> **Önemli Not**: Varsayılan `php artisan serve` komutunun sistemdeki varsayılan 2 MB limitine dönebileceğini unutmayınız. Geliştirme ve test aşamasında `composer serve` tercih edilmelidir.
