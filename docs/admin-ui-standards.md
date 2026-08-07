# DOST TV CMS — Kalıcı Admin UI Tasarım Standardı

Bu doküman, DOST TV CMS yönetim panelinde geliştirilecek tüm ekranların uyması gereken zorunlu kullanıcı deneyimi (UX) ve tasarım kurallarını tanımlar.

---

## 🏛️ 1. BÜYÜK DEKORATİF İKON YASAĞI (BOŞLUĞU DOLDURMAK İÇİN BÜYÜK İKON KESİNLİKLE YASAKTIR)
Hiçbir admin ekranında:
- **CMS'de "boşluğu doldurmak için büyük ikon" kullanımı tamamen yasaktır.**
- Büyük SVG
- Büyük Heroicon
- Dekoratif illüstrasyon
- 100 px üzeri ikon
- Ekranın %20’sinden fazlasını kaplayan sembol

kullanılmayacaktır. İkonlar yalnız işlevsel amaçla kullanılacak ve normalde 16–24 px, istisnai olarak en fazla 32 px olacaktır.

---

## 📦 2. KOMPAKT EKRAN KURALI
Admin ekranları kompakt olmalıdır:
- Gereksiz boş alan oluşturulmayacak.
- Aynı bilgi birden fazla yerde tekrar edilmeyecek.
- Bilgi kutuları en fazla 3–5 satır olacak.
- Kullanıcı işini **Aç → Düzenle → Kaydet → Çık** akışında tamamlayabilmeli.
- Ekranın büyük bölümünü kaplayan açıklama veya dekorasyon kullanılmayacak.

---

## 🔒 3. TEKNİK BİLGİLERİN GİZLENMESİ
Editöre gereksiz teknik detay gösterilmemelidir. Varsayılan olarak gösterilmeyecek:
- Route isimleri
- Model isimleri
- Tablo isimleri
- JSON içerikleri
- Uzun localhost URL’leri
- Teknik servis durum metinleri
- Gereksiz sistem açıklamaları

*(Örnek: `http://127.0.0.1:8000/canli-tv` yerine **"Canlı TV Sayfası"** kullanılacaktır).*

---

## 👁️ 4. PUBLIC PREVIEW STANDARD (TEK KAYNAK ÖNİZLEME KURALI)

Admin önizlemeleri ziyaretçinin public sitede göreceği gerçek Blade component’ini render eder. Sahte HTML, dummy veri ve ikinci tasarım yasaktır. Public tasarım değiştiğinde admin önizleme otomatik değişmelidir.

**Bileşen Eşleşmeleri**:
- **Header Preview**: `resources/views/components/site/header.blade.php`
- **Footer Preview**: `resources/views/components/site/footer.blade.php`
- **Announcement Preview**: `resources/views/components/site/announcement-card.blade.php`
- **Banner Preview**: `resources/views/components/site/hero-banner.blade.php`
- **Program Kartı Preview**: `resources/views/components/site/program-card.blade.php`
- **Sayfa Preview**: `resources/views/components/site/page-card.blade.php`

**Kurallar**:
1. **Tek Kaynak HTML**: Aynı HTML iki kez yazılamaz.
2. **Tasarım Sadakati**: `:preview="true"` modu grid, renk, tipografi veya düzeni değiştiremez; yalnızca bağlantı/tıklama aksiyonlarını kontrol eder.
3. **Form State**: Kaydedilmemiş güncel veriler ve geçici görsel URL'leri anlık yansıtılır.
4. **Teknik Bilgilerin Gizliliği**: Model/tablo adları, ID, admin durum rozetleri ("Pasif", "Aktif"), teknik tarihler veya açıklama metinleri önizleme modunda yer almaz.
5. **Doğal Kapsayıcı**: Genişlik %100, overflow güvenli ve doğal yüksekliğinde gösterilir.

---

## 🖼️ 5. PLACEHOLDER KURALI
Placeholder görseller:
- Küçük
- Sade
- Nötr
- Maksimum 48 px ikonlu

olmalıdır. Tam ekran veya yüzlerce piksel büyüklüğünde placeholder kullanılmayacaktır.

---

## 🎨 6. TASARIM VE CMS SINIRI
- **CMS**: İçeriği değiştirir, görünürlüğü değiştirir, davranış seçeneklerini yönetir, metinleri ve bağlantıları yönetir.
- **CMS KESİNLİKLE**: Yeni tasarım üretmez, Claude tarafından hazırlanan düzeni bozmaz, renk paletini keyfi değiştirmez, boşluk/grid/tipografi/responsive yapıyı yeniden tasarlamaz.

---

## 📝 7. FORM DÜZENİ & LİSTE EKRANLARI
- **Formlar**: Mantıklı sekmelere ayrılmalı, kısa yardım metinleri ve net toggle etiketleri sunmalı.
- **Listeler**: Küçük görsel önizlemesi, başlık, durum, tarih ve temel işlemleri göstermeli; teknik ID veya JSON sütunları barındırmamalı.

---

## ♻️ 8. SÜRDÜRÜLEBİLİR VERİ STANDARDINI KORUMA (TEK VERİ KAYNAĞI)
Gelecekte değişebilecek, editör tarafından yönetilecek veya birden fazla yerde kullanılan bilgiler kod içine sabit yazılamaz. Tek bir veri kaynağından yönetilir. Tasarım, güvenlik ve uygulama mimarisi ise kodda kalır.

---

## ⚡ 9. PERFORMANCE FIRST (PERFORMANS ODAKLI GELİŞTİRME PRENSİBİ)
Her geliştirmede ve kod değişikliğinde şu sorular sorulur ve uygulanır:
- Bu değişiklik yeni veritabanı sorgusu ekliyor mu? (N+1 sorguları engellenmelidir).
- Aynı veriyi tekrar tekrar mı çekiyor? (Tek veri kaynağından / cache'den okunmalıdır).
- Cache kullanılmalı mı? (Statik/az değişen veriler önbelleğe alınmalıdır).
- Public sayfa yavaşlıyor mu?
- Lazy load kullanılabilir mi? (`loading="lazy"`).
- Component tekrar kullanılabilir mi?
