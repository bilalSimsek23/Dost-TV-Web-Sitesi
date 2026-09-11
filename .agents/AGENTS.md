# DOST TV Yönetim Paneli — UX & Tasarım Prensipleri

Bu projedeki tüm geliştirmeler teknik bilgisi olmayan bir editörün veya televizyon çalışanının rahatça kullanabileceği, modern, sade ve profesyonel bir yönetim paneli oluşturmayı hedefler.

## 🎯 Öncelik Sırası
1. **KULLANICI DENEYİMİ (UX)**
2. **YÖNETİM KOLAYLIĞI**
3. **İŞ AKIŞI**
4. **LARAVEL KODU (Altyapı)**

---

## 🧭 Genel Tasarım Felsefesi
- **İlk Kullanım Kolaylığı**: "Bu ekranı ilk kez kullanan biri hiçbir eğitim almadan işlemini tamamlayabilir mi?" Sorusuna evet yanıtı verilmeli.
- **İş Akışı Odaklılık**: Ekranlar veritabanı veya Eloquent model mantığıyla değil, televizyon yayıncılığı iş akışına göre tasarlanır.
- **Tek Ekrandan Yönetim**: Kullanıcı aynı iş için farklı sayfalara gitmek zorunda kalmamalı, en çok kullanılan işlem merkezde yer almalıdır.

---

## 📂 Sol Menü Yapısı (İş Alanı Odaklı)
Model ve teknik isimler (Resource, Pivot, RelationManager) menüde kesinlikle gösterilmez.

- 📊 **Dashboard**
- 📺 **İçerik Yönetimi** (`Programlar`, `Bölümler`, `Kategoriler`, `Bannerlar`, `Sayfalar`, `Duyurular`)
- 📡 **Yayın Yönetimi** (`Yayın Akışı`, `Canlı TV`, `Canlı FM`)
- 🌐 **Site Düzeni** (`Header / Üst Alan`, `Footer`, `Görünüm`)
- 👤 **Kullanıcılar**
- ⚙ **Sistem**

---

## 📝 Formlar & Listeler
- **Sekmeli Form Yapısı**: Uzun tek sayfa formlar yerine `Genel Bilgiler`, `Görünüm`, `SEO`, `Gelişmiş Ayarlar` sekmeleri kullanılır.
- **Liste / Kart / Ağaç Görünümü**: Kullanıcı ihtiyacına göre görünüm modları arasında geçiş yapabilmelidir.

---

## 🚫 Kullanılmayacak Teknik Terimler
Kullanıcı arayüzünde şu terimler kesinlikle yer almayacak, kullanıcı dostu Türkçe karşılıkları kullanılacaktır:
- `Resource`, `Relation`, `Pivot`, `Model`, `Slug`, `Migration`, `Seeder`, `Cache`, `Parent ID`, `Foreign Key`, `Database`, `CRUD`.

---

## 🎨 DOST TV CMS — Kalıcı Admin UI Tasarım Standardı

### A. BÜYÜK DEKORATİF İKON YASAĞI (BOŞLUĞU DOLDURMAK İÇİN BÜYÜK İKON KESİNLİKLE YASAKTIR)
Hiçbir admin ekranında:
- CMS'de boşluğu doldurmak için büyük ikon kullanımı tamamen yasaktır!
- Büyük SVG
- Büyük Heroicon
- Dekoratif illüstrasyon
- 100 px üzeri ikon
- Ekranın %20’sinden fazlasını kaplayan sembol

kullanılmayacaktır. İkonlar yalnız işlevsel amaçla kullanılacak ve normalde 16–24 px, istisnai olarak en fazla 32 px olacaktır.

### B. KOMPAKT EKRAN KURALI
Admin ekranları kompakt olmalıdır:
- Gereksiz boş alan oluşturulmayacak.
- Aynı bilgi birden fazla yerde tekrar edilmeyecek.
- Bilgi kutuları en fazla 3–5 satır olacak.
- Kullanıcı işini **Aç → Düzenle → Kaydet → Çık** akışında tamamlayabilmeli.
- Ekranın büyük bölümünü kaplayan açıklama veya dekorasyon kullanılmayacak.

### C. TEKNİK BİLGİLERİN GİZLENMESİ
Editöre gereksiz teknik detay gösterilmemelidir. Varsayılan olarak gösterilmeyecek:
- Route isimleri
- Model isimleri
- Tablo isimleri
- JSON içerikleri
- Uzun localhost URL’leri
- Teknik servis durum metinleri
- Gereksiz sistem açıklamaları

*(Örnek: `http://127.0.0.1:8000/canli-tv` yerine **"Canlı TV Sayfası"** kullanılacaktır).*

### D. PUBLIC PREVIEW STANDARD (TEK KAYNAK ÖNİZLEME KURALI)
Admin önizlemeleri ziyaretçinin public sitede göreceği gerçek Blade component’ini render eder. Sahte HTML, dummy veri ve ikinci tasarım yasaktır. Public tasarım değiştiğinde admin önizleme otomatik değişmelidir.

**Bileşen Eşleşmeleri**:
- **Header**: `resources/views/components/site/header.blade.php`
- **Footer**: `resources/views/components/site/footer.blade.php`
- **Duyuru**: `resources/views/components/site/announcement-card.blade.php`
- **Banner**: `resources/views/components/site/hero-banner.blade.php`
- **Program Kartı**: `resources/views/components/site/program-card.blade.php`
- **Sayfa**: `resources/views/components/site/page-card.blade.php`

**Zorunlu Kurallar**:
1. **Tek Kaynak HTML**: Aynı HTML iki kez yazılamaz. Admin önizleme ve public görünüm tek Blade bileşenine dayanır.
2. **Preview Modu & Tasarım Sadakati**: Bileşen `:preview="true"` alabilir ancak grid, tipografi, renkler, spacing ve responsive yapısı public görünümle birebir aynı kalır.
3. **Canlı Form State**: Önizleme mümkünse kaydedilmemiş güncel form verisini (metinler, Livewire geçici görsel URL'si vb.) kullanır; sahte veri üretilmez.
4. **Teknik & Admin Bilgisi Gizliliği**: Önizlemede model/tablo isimleri, route'lar, ID'ler, admin durum rozetleri ("Aktif", "Pasif", "Taslak"), teknik tarihler veya "Public Önizleme" uyarı yazıları gösterilmez.
5. **Doğal Kapsayıcı**: Genişlik %100, overflow güvenli ve bileşen doğal yüksekliğinde gösterilir; dev ikonlar veya yapay boşluklar eklenmez.

### E. PLACEHOLDER KURALI
Placeholder görseller:
- Küçük
- Sade
- Nötr
- Maksimum 48 px ikonlu

olmalıdır. Tam ekran veya yüzlerce piksel büyüklüğünde placeholder kullanılmayacaktır.

### F. TASARIM VE CMS SINIRI
- **CMS**: İçeriği değiştirir, görünürlüğü değiştirir, davranış seçeneklerini yönetir, metinleri ve bağlantıları yönetir.
- **CMS KESİNLİKLE**: Yeni tasarım üretmez, Claude tarafından hazırlanan düzeni bozmaz, renk paletini keyfi değiştirmez, boşluk/grid/tipografi/responsive yapıyı yeniden tasarlamaz.

### G. FORM DÜZENİ
Formlar:
- Mantıklı sekmelere ayrılmalı.
- Bir sekmede gereksiz bilgi tekrarı olmamalı.
- Yardım metinleri kısa olmalı.
- Toggle ve select etiketleri anlaşılır olmalı.
- Teknik olmayan editör odaklı hazırlanmalı.

### H. LİSTE EKRANI
Liste ekranları:
- Küçük görsel önizlemesi (3:4 dikey oran vb.)
- Başlık
- Durum
- Tarih
- Temel işlemler

### I. SÜRDÜRÜLEBİLİR VERİ STANDARDINI KORUMA (TEK VERİ KAYNAĞI)
Gelecekte değişebilecek, editör tarafından yönetilecek veya birden fazla yerde kullanılan bilgiler kod içine sabit yazılamaz. Tek bir veri kaynağından yönetilir. Tasarım, güvenlik ve uygulama mimarisi ise kodda kalır.

### J. PERFORMANCE FIRST (PERFORMANS ODAKLI GELİŞTİRME PRENSİBİ)
Her geliştirmede ve kod değişikliğinde şu sorular sorulur ve uygulanır:
- **Sorgu Optimizasyonu**: Bu değişiklik yeni veritabanı sorgusu ekliyor mu? (N+1 engellenmeli).
- **Veri Tekrarı**: Aynı veri tekrar tekrar mı çekiliyor?
- **Önbellekleme (Cache)**: Cache kullanılmalı mı? (Sık erişilen veriler için `SiteCache` tercih edilmeli).
- **Sayfa Hızı**: Public sayfa yavaşlıyor mu?
- **Lazy Load**: Görsellerde ve ağır elemanlarda `loading="lazy"` kullanılabilir mi?
- **Bileşen Yeniden Kullanılabilirliği**: Component tekrar kullanılabilir mi?

---

## 🏛️ DOST TV BUILDER — SABİT KURALLAR v1

Bu kurallar tasarımdan bağımsızdır. Yeni tasarım, panel veya component değişikliklerinde KORUNACAKTIR.

1. **TASARIM ≠ İŞ MANTIĞI**: Renk, kart, şerit, boşluk, slider, rozet, tipografi değişebilir. Veri kaynağı, navigasyon, aktif yayın ve publish mantığı değişmez.
2. **HOMEPAGE BUILDER**: Preview = `draft_sections`, Public = `published_sections`. Publish yapılmadan public değişmez. Header / Hero / Footer sabit ve korunur. Gereksiz DB/migration yapılmaz.
3. **YAYIN AKIŞI**: Mevcut gerçek schedule altyapısını kullan. `BroadcastScheduleResolver` korunur. Ana sayfada bugünün tüm TV yayınları erişilebilir olmalı. Görünür öğe sayısı içerik limiti değildir. Aktif yayın saate göre hesaplanır. "ŞİMDİ" sadece aktif yayında; "SIRADAKİ" sadece hemen sonraki yayında. Açılışta aktif yayın varsa ona odaklan; yoksa en yakın sıradaki yayına git. Geçmiş yayınlar saklanır.
4. **YAYIN AKIŞI LİNKLERİ**:
   - AKTİF + CANLI: → `route('live.tv')`
   - DİĞER BAĞLI YAYIN: → `route('programs.show', $item->program)`
   - PROGRAM İLİŞKİSİ YOK: → Link oluşturma.
   - Episode/video detayına yönlendirme YOK. Programı isim veya slug tahminiyle eşleştirme YOK. Mevcut Program relation / `program_id` kullan.
5. **TÜM AKIŞ**: Yeni route oluşturma. Header'daki mevcut Yayın Akışı sayfasına bağlan (`route('schedule.index')`).
6. **PROGRAM / KATEGORİ / VİDEO RAFLARI**: Grid ve Yatay birbirinden ayrı davranır. Yatayda içerikler alta düşmez, sağa doğru tek hizada devam eder. Satır/sütun değerleri içerik limiti değildir. "Tümü" seçildiğinde bütün uygun içerik erişilebilir kalır. Mobil/tablet responsive otomatik çalışır.
7. **GENEL KURAL**: Çalışan özelliği gereksiz refactor etme. Yeni tasarım yaparken sabit iş kurallarını değiştirme. Mevcut route/service/relation varsa yeniden üretme.

---

## 🛡️ DOST TV — FRONTEND BUILD GÜVENLİK KURALI

Tailwind, CSS, arbitrary utility, z-index, responsive class veya yeni frontend class değişikliği yapılan her görevde:

1. `npm run build` çalıştır.
2. `public/build/manifest.json` güncellendi mi doğrula.
3. Yeni CSS asset hash'ini raporla.
4. Değiştirilen kritik class/utility'nin derlenmiş CSS asset içinde gerçekten bulunduğunu doğrula.
5. Hard refresh / production asset davranışını kontrol et.
6. Kaynak kod ile `public/build` asset farklıysa görevi tamamlanmış sayma.

**Zorunlu Alanlar:**
- Popup / Modal
- z-index
- Slider
- Responsive layout
- Custom Tailwind arbitrary class
- Yeni utility class
- CSS import / değişiklikleri

Mümkünse kritik modal/popup katmanlarında build bağımsız güvenli fallback (ör. inline style) bırak.

**Görev Sonu Raporlama:**
- Build çalıştı mı?
- Yeni asset adı
- Kritik class derlendi mi?
- Hard refresh sonucu

---

## 🎯 Kabul Kriteri
"DOST TV yönetim paneli, WordPress kadar kolay, Shopify kadar düzenli ve televizyon yayıncılığına özel bir CMS hissi vermelidir."


