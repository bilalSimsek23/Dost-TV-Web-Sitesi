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
- 📺 **İçerik Yönetimi** (`Programlar`, `Bölümler`, `Kategoriler`, `Bannerlar`, `Sayfalar`)
- 📡 **Yayın Yönetimi** (`Yayın Akışı`, `Canlı TV`, `Canlı FM`)
- 🌐 **Site Yönetimi** (`TOP HEADER`, `Footer`, `Tema`, `Fontlar`, `Site Ayarları`)
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

## 🎯 Kabul Kriteri
"DOST TV yönetim paneli, WordPress kadar kolay, Shopify kadar düzenli ve televizyon yayıncılığına özel bir CMS hissi vermelidir."
