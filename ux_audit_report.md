# Dost TV — UX Audit Raporu

**Hazırlayan:** Kıdemli Ürün Tasarımcısı / UX Mimarı perspektifiyle kod ve mimari incelemesi
**Tarih:** 27 Temmuz 2026
**Kapsam:** Laravel 12 + Filament v4 admin paneli, Blade + Alpine.js public frontend (Livewire yalnızca Filament admin panelinde kullanılıyor — public sitede Livewire bileşeni yok)
**Yöntem:** Statik kod incelemesi (routes, controller'lar, modeller, migration'lar, Blade view'leri, Filament kaynakları, seeder içerikleri). Çalışan bir tarayıcı oturumu veya gerçek trafik verisi incelenmemiştir; performans ve ölçüm gerektiren bulgular bu sınırla birlikte değerlendirilmelidir.

---

## Önemli bir düzeltme

Görev tanımında "Blade + Livewire frontend yapısı" ifadesi geçiyor, ancak kod incelemesinde public sitede **hiçbir Livewire bileşeni bulunmuyor**. Frontend tamamen klasik Blade + Alpine.js (banner carousel, dropdown) ile kurulmuş; kategori filtreleme gibi etkileşimler tam sayfa yenileme (query string) ile çalışıyor. Livewire yalnızca `/admin` altındaki Filament paneli için kullanılıyor. Bu, raporun birçok bölümünde ("etkileşim modeli", "performans") doğrudan etkili bir mimari gerçektir ve ileride Livewire eklenmesi ihtimaline karşı ayrı bir öneri maddesi olarak ele alınmıştır.

---

## 1. Genel Kullanıcı Deneyimi

**Sitenin temel amacı:** Dost TV, uydu üzerinden yayın yapan, değer temelli/dini-manevi içerik ağırlıklı bir Türkçe tematik televizyon kanalının dijital yüzü. Site dört temel işlevi bir araya getiriyor: (1) canlı TV izleme, (2) canlı radyo (Dost FM) dinleme, (3) program/bölüm arşivine erişim, (4) kurumsal/kimlik bilgileri (yayıncı künye, yayın ilkeleri, iletişim, bağış hesapları).

**Hedef kullanıcı grupları:**
- Kanalı zaten TV'den/uydudan tanıyan, siteye doğrudan "canlı izlemek" için gelen sadık izleyiciler.
- Belirli bir programı veya sohbeti tekrar izlemek isteyen arşiv kullanıcıları.
- Kurumsal/regülasyon amaçlı bilgi arayanlar (RTÜK, medya izleme kuruluşları, ortaklar) — yayıncı künye bilgisi sayfasının varlığı bunu doğruluyor.
- Dost Vakfı'na bağış yapmak isteyen kullanıcılar.
- Mobil ağırlıklı, çoğunlukla orta-düşük teknik yetkinlikte, olasılıkla yaş ortalaması yüksek bir kitle (içerik tonundan çıkarım).

**Kullanıcıların siteye gelme nedenleri:** "Şu an ne yayında?", "Dost TV'yi nasıl canlı izlerim?", belirli bir sohbet/programın tekrarını arama, kurumsal doğrulama, bağış bilgisi.

**Güçlü yönler:**
- Bilgi mimarisi kavramsal olarak doğru dört ana eksen etrafında kurulmuş: Programlar, Yayın Akışı, Canlı TV, Canlı Radyo.
- Görsel tasarım dili (koyu tema, rose/amber gradyanları) tutarlı ve modern; "canlı" hissi veren pulse-animasyonlu göstergeler iyi bir mikro-etkileşim.
- Admin panel (Filament) içerik yönetimini teknik olmayan bir kullanıcının da yapabileceği şekilde sadeleştirilmiş (otomatik slug üretimi, görsel yükleme, ilişki seçimi).
- Kurumsal içerikler (yayıncı künye, yayın ilkeleri vb.) gerçek ve düzgün yapılandırılmış — bu tür sitelerde sık atlanan bir gereklilik.

**Zayıf yönler:**
- **Mobilde ana navigasyon tamamen yok** (bkz. Bölüm 3 ve 10) — bu tek başına genel deneyimi ciddi şekilde düşüren bir yapısal kusur.
- Bölümler (episodes) bağımsız bir sayfaya/URL'e sahip değil; tüm video arşivi deneyimi tek bir sayfa içine sıkıştırılmış JS anahtarlama mantığına dayanıyor.
- Ana sayfa yalnızca "öne çıkan programlar"ı gösteriyor; taze/yeni eklenen içerik keşfi, "şimdi ne oynuyor" vurgusu ve bağış/destek CTA'sı eksik.
- Canlı TV ve Canlı Radyo sayfaları birbirinden ve yayın akışından bağlamsal olarak kopuk; kullanıcı "şu an ne izliyorum/dinliyorum" sorusuna sayfa üzerinde cevap bulamıyor.

**Kafa karıştırabilecek noktalar:**
- Program detay sayfasında hangi videonun (fragman mı, ilk bölüm mü) otomatik yüklendiği kullanıcı için belirsiz; oynatıcı üstünde "şu an oynatılan: ..." etiketi yok.
- YouTube gömülü videolar ile kendi sunucusundan servis edilen (upload) videolar arasında kullanıcıya hiçbir görsel/işlevsel ayrım sunulmuyor (biri YouTube arayüzünü taşıyor, diğeri sade HTML5 player) — tutarsız bir izleme deneyimi.
- "Kurumsal" menüsü yalnızca üç sayfayı kapsıyor, ama "Dost Vakfı Hesap No" ve "İletişim" ayrı, üst seviye linkler olarak duruyor — gruplama mantığı kullanıcıya net değil.

---

## 2. Kullanıcı Yolculukları

### 2.1 Canlı TV izlemek isteyen kullanıcı
- **Giriş noktası:** Header'daki sabit "Canlı İzle" butonu veya `/canli-tv`.
- **Adımlar:** Header butonuna tıkla → `live.tv` sayfası yüklenir → HLS ise `<video>` etiketi `autoplay muted` ile başlar, iframe ise embed otomatik yüklenir.
- **Olası sorunlar:** `live_tv_url` boşsa kullanıcıya yalnızca düz metin ("Admin panelden ekleyin") gösteriliyor — son kullanıcı için anlamsız bir teknik mesaj. HLS akışı koparsa hiçbir hata/yeniden bağlanma mekanizması yok, video sonsuza dek "yükleniyor" gibi donuk kalabilir.
- **Gereksiz adımlar:** Yok — akış aslında en kısa yolculuklardan biri (1 tık).
- **İyileştirme önerileri:** Hata durumunda kullanıcı dostu mesaj + otomatik yeniden deneme; sayfada "şimdi yayında" program adı ve "sırada" bilgisi; paylaşım butonu.
- **İdeal akış:** Header → Canlı TV sayfası → oynatıcı otomatik başlar → altında "Şimdi Yayında: {program}" + yayın akışına link.

### 2.2 Canlı radyo dinlemek isteyen kullanıcı
- **Giriş noktası:** Header "Dost FM Canlı" linki veya ana sayfa "Canlı Radyo Dinle" butonu.
- **Adımlar:** Linke tıkla → `live.radio` sayfası → `<audio controls preload="none">` üzerinde manuel oynat tuşuna basılır.
- **Olası sorunlar:** Kullanıcı başka bir sayfaya geçtiği an ses kesiliyor (kalıcı mini-player yok) — radyo deneyiminin doğası gereği "arka planda dinleme" beklentisi tamamen karşılanmıyor. "Şimdi çalıyor" (now playing) bilgisi hiç yok.
- **Gereksiz adımlar:** Yok, ama sayfadan ayrılan kullanıcı dinlemeye devam etmek için tekrar sayfaya dönüp tekrar oynat tuşuna basmak zorunda — bu radyo ürünleri için ciddi bir sürtünme.
- **İyileştirme önerileri:** Site genelinde kalıcı (sticky) mini radyo çubuğu; ICY metadata varsa "şimdi çalıyor" gösterimi.
- **İdeal akış:** Herhangi bir sayfadan tek tıkla radyoyu başlat → gezinirken alt çubukta çalmaya devam etsin.

### 2.3 Belirli bir programı bulmak isteyen kullanıcı
- **Giriş noktası:** Header "Programlar" linki (yalnızca masaüstü!) veya ana sayfa "Tümünü Gör".
- **Adımlar:** `/programlar` → kategori filtresi (opsiyonel) → grid'den programa tıkla.
- **Olası sorunlar:** **Arama kutusu yok.** Program sayısı arttıkça kategori filtresi + 12'lik sayfalama tek başına yetersiz kalır. Kategori etiketi olmayan programlar yalnızca "Tümü" altında bulunabiliyor. Mobilde bu sayfaya header üzerinden hiç ulaşılamıyor.
- **Gereksiz adımlar:** Kategori bilmeyen kullanıcı sayfalar arasında gezinmek zorunda kalabilir.
- **İyileştirme önerileri:** İsimle canlı arama (autocomplete), "popüler/yeni" sıralama seçenekleri.
- **İdeal akış:** Programlar sayfası → arama/filtre → program kartı → detay sayfası, en fazla 2 tıkla.

### 2.4 Eski bir bölümü izlemek isteyen kullanıcı
- **Giriş noktası:** Program detay sayfasındaki "Video Arşivi" grid'i.
- **Adımlar:** Programı bul → detay sayfasına gir → bölüm kartına tıkla → JS oynatıcıyı günceller, sayfa en üste kaydırılır.
- **Olası sorunlar:** Bölümlerin **kendi URL'i yok** — kullanıcı belirli bir bölümü arkadaşına linkleyemez, tarayıcı geçmişinde geri gidip belirli bir bölüme dönemez, sayfayı yenilerse seçtiği bölüm kaybolur (varsayılan videoya döner). Bölüm süresi hiçbir yerde gösterilmiyor (veritabanında `duration` alanı yok).
- **Gereksiz adımlar:** Programı bulmak için önce programlar listesine gitmek gerekiyor; doğrudan "tüm bölümler" arşivi/arama yok.
- **İyileştirme önerileri:** Her bölüme `/programlar/{program}/{episode}` gibi kendi slug'lı rotası; sayfa yüklendiğinde query/hash ile belirli bölümün otomatik yüklenmesi; süre bilgisi eklenmesi.
- **İdeal akış:** Arama/arşiv → bölüm sonucu → kendi URL'i olan bölüm sayfası → izle/paylaş.

### 2.5 Bugünkü yayın akışını görmek isteyen kullanıcı
- **Giriş noktası:** Ana sayfadaki "Bugünün Yayın Akışı" widget'ı veya header "Yayın Akışı" linki.
- **Adımlar:** Widget'ta bugünün programları saat sırasıyla listelenir; "Tüm yayın akışını görüntüle" ile haftalık sayfaya geçilebilir.
- **Olası sorunlar:** Ana sayfadaki widget da, `/yayin-akisi` sayfası da **"şu an yayında" satırını vurgulamıyor** — kullanıcı saatleri kafasında karşılaştırmak zorunda. Haftalık sayfada gün gün akordiyon değil, tüm 7 gün alt alta render ediliyor; mobilde uzun bir kaydırma listesi oluşuyor, "bugün"e hızlı atlama yok.
- **Gereksiz adımlar:** Haftalık sayfada doğru güne ulaşmak için manuel kaydırma gerekiyor.
- **İyileştirme önerileri:** "Şimdi" satırını canlı saat karşılaştırmasıyla vurgulamak; gün sekmeleri/dropdown; "bugün" gününe otomatik odaklanma.
- **İdeal akış:** Sayfa açılır açılmaz "bugün" sekmesi aktif, şu an oynayan program vurgulu, tek tıkla diğer günlere geçiş.

### 2.6 Kurumsal bilgi arayan kullanıcı
- **Giriş noktası:** Header "Kurumsal" dropdown (yalnızca masaüstü, yalnızca hover) veya footer linkleri.
- **Adımlar:** Dropdown'ı hover et → sayfayı seç → `pages.show` düz metin sayfası.
- **Olası sorunlar:** Dropdown yalnızca `:hover` ile açılıyor; klavye/dokunmatik kullanıcılar (tablet, bazı masaüstü kullanıcıları Tab ile gezinirken) bu menüye **erişemiyor**. Mobilde header nav'ı hiç render edilmediği için kurumsal sayfalara yalnızca footer'dan (varsa) ulaşılabiliyor. Uzun metinlerde içindekiler/breadcrumb yok.
- **Gereksiz adımlar:** Yok, ama erişim kanalı kırılgan.
- **İyileştirme önerileri:** Dropdown'ı `:focus-within` ve tıklamayla da açılır hale getirmek; mobil menüde aynı grup yapısını korumak.
- **İdeal akış:** Herhangi bir cihazdan "Kurumsal" menüsüne 1 tıkla eriş → sayfa listesi → seç.

### 2.7 İletişim veya bağış bilgisine ulaşmak isteyen kullanıcı
- **Giriş noktası:** Header "İletişim" linki (masaüstü) veya footer.
- **Adımlar:** İletişim sayfası → adres/telefon düz metin; Bağış için ayrı "Dost Vakfı Hesap No" sayfasına gitmek gerekiyor.
- **Olası sorunlar:** Telefon numarası `tel:` linki değil (mobilde tek tıkla arama yok). IBAN'lar düz metin, kopyala butonu yok — bağış yapmak isteyen kullanıcı için gereksiz sürtünme. Bağış CTA'sı sitenin hiçbir öne çıkan noktasında (ana sayfa, footer üst kısmı) yok, yalnızca menüde gizli bir alt sayfa.
- **Gereksiz adımlar:** Manuel IBAN kopyalama/yazma.
- **İyileştirme önerileri:** `tel:` ve `mailto:` linkleri, IBAN'lar için kopyala butonu, ana sayfada görünür bir "Bağış Yap" CTA'sı.
- **İdeal akış:** Herhangi bir sayfadan görünür bir "Bağış" veya "İletişim" CTA'sı → tek tıkla arama/kopyalama.

---

## 3. Bilgi Mimarisi

- **Ana menü yapısı:** Kurumsal (dropdown, 3 sayfa) → Programlar → Yayın Akışı → Dost TV Canlı → Dost FM Canlı → Dost Vakfı Hesap No → İletişim. Mantıksal gruplama kısmi: "Dost Vakfı Hesap No" ve "İletişim" de aslında kurumsal/destekleyici içerik olduğu halde dropdown dışında, üst düzey link olarak duruyor — tutarsız bir hiyerarşi.
- **Sayfa hiyerarşisi:** Üç seviyeli: Ana Sayfa → Kategori/Liste sayfaları (Programlar, Yayın Akışı, Canlı TV/Radyo) → Detay sayfaları (Program detayı, statik sayfa). Bölümler bu hiyerarşide **hiç bağımsız bir seviye oluşturmuyor** — üçüncü seviyenin altında olması gereken "bölüm" varlığı, ikinci seviyenin (program detay) bir alt-bileşeni olarak JS ile yönetiliyor.
- **Programlar ve bölümler ilişkisi:** Veri modeli doğru (Program `hasMany` Episode, `hasMany` Schedule, `belongsToMany` Category), ancak bu ilişki URL/routing seviyesinde yansıtılmıyor. Bölümlerin kendi kimliği (route) olmaması bilgi mimarisindeki en büyük yapısal eksik.
- **Video arşivinin erişilebilirliği:** Arşive yalnızca "önce programı bul, sonra bölümlere bak" yoluyla ulaşılabiliyor; genel bir "Video Arşivi" / "Tüm Bölümler" sayfası yok. Arama yok.
- **Kurumsal sayfaların gruplanması:** Veritabanında `show_in_menu` + `sort_order` alanları var ama alt kategori/grup alanı yok; bu yüzden header'daki "hangi sayfa dropdown'da, hangisi üst seviyede" ayrımı kod içinde slug'lara göre hardcode edilmiş (`whereIn(['yayinci-kunye-bilgisi', ...])`). Yeni bir kurumsal sayfa eklenirse otomatik olarak yanlış yere (üst seviye, dropdown dışı) düşer.
- **Footer yapısı:** Canlı TV, Canlı Radyo, Yayın Akışı + tüm `menuPages` listeleniyor. **"Programlar" linki footer'da da yok** — bu, mobil kullanıcılar için (header nav'ı olmadığından) programlar sayfasına ulaşmanın **hiçbir yolu olmadığı** anlamına geliyor.
- **Mobil menü yapısı:** **Yok.** `nav` bloğu `hidden ... lg:flex` sınıfıyla `lg` breakpoint altında tamamen gizleniyor ve yerine hiçbir hamburger/mobil menü mekanizması konulmamış. Bu, bilgi mimarisinin mobilde fiilen çöktüğü anlamına gelir.
- **Üç tıklama kuralı analizi:**
  - Masaüstünde: Ana sayfa → Programlar → Program detay (2 tık) ✅; Ana sayfa → Yayın Akışı (1 tık) ✅; Ana sayfa → Kurumsal → Sayfa (2 tık) ✅.
  - Bir bölüme ulaşmak: Ana sayfa → Programlar → Program → Bölüm seç (3 tık) — sınırda ama JS ile aynı sayfada olduğu için "yeni sayfa" sayılmıyor, gerçek bir "ulaşım" değil sadece story değişimi.
  - **Mobilde:** Canlı TV dışında (header CTA hep görünür) hiçbir içeriğe menüden ulaşılamıyor — kural fiilen geçersiz.

---

## 4. Ana Sayfa UX Analizi

- **İlk ekran:** Header + banner carousel (varsa) + hero metin/CTA ekran yüksekliğine yakın bir alan kaplıyor. Banner yoksa doğrudan hero bölümüne düşülüyor — makul bir fallback.
- **Manşet alanı:** `Banner` modelinden gelen otomatik döngülü (6 sn) carousel; sadece `is_active` ve `sort_order`. Duraklatma/hover kontrolü yok, nokta göstergelerinde `aria-label` yok.
- **Canlı yayın görünürlüğü:** Header'daki kalıcı "Canlı İzle" pulse-butonu güçlü bir görünürlük sağlıyor; ama ana sayfa gövdesinde canlı yayının **kendisi gömülü değil** (mini player yok), kullanıcı yine ayrı sayfaya gitmek zorunda.
- **Şimdi yayında bilgisi:** "Bugünün Yayın Akışı" widget'ı var ama şu an oynayan satırı vurgulanmıyor (bkz. 2.5).
- **Öne çıkan programlar:** Var, `sort_order`'a göre ilk 8 program — editoryal olarak yönetilebilir ama "en yeni" veya "en çok izlenen" gibi dinamik bir sinyal yok.
- **Son eklenen videolar:** **Hiç yok.** Ana sayfada hiçbir "yeni bölüm" veya "son eklenenler" bölümü render edilmiyor — bu, düzenli içerik ekleyen bir yayın kuruluşu için önemli bir keşif kaybı.
- **Yayın akışı:** Sadece bugünün özeti; haftalık görünüme link var.
- **Radyo erişimi:** Hero CTA'sında "Canlı Radyo Dinle" butonu var; ayrıca gömülü mini oynatıcı yok.
- **Kurumsal içerikler:** Ana sayfa gövdesinde kurumsal içerik hiç yer almıyor, yalnızca footer'da linkler.
- **Bağış/destek alanları:** Ana sayfada hiç yok.
- **Footer:** Sade ama eksik (Programlar linki yok, sosyal medya ikonları yok).
- **İçerik önceliklendirmesi:** Sıra: Banner → Hero+Bugünkü Akış → Öne Çıkan Programlar. Canlı yayın ve radyo yalnızca metin CTA'sı düzeyinde; bağış ve kurumsal kimlik hiç yok.
- **Görsel hiyerarşi:** Tipografi/renk kontrastı tutarlı; CTA butonları (rose-600) net ayrışıyor.
- **Kullanıcı aksiyonları:** "Canlı TV İzle", "Canlı Radyo Dinle", "Tümünü Gör", program kartına tıklama — aksiyonlar az sayıda ve net, ancak "Bağış Yap" gibi kurumsal açıdan önemli bir aksiyon eksik.

**Ana sayfada eksik olan bölümler (özet liste):**
1. Son eklenen bölümler / "Yeni Yayınlananlar"
2. Şimdi yayında / şimdi çalıyor vurgusu (TV ve radyo için)
3. Bağış/destek CTA'sı
4. Sosyal medya bağlantıları
5. Kurumsal kimlik özeti (kanal hakkında kısa tanıtım + "devamını oku")
6. Gömülü canlı TV mini-player veya önizleme

---

## 5. Canlı TV Deneyimi

- **Oynatıcının konumu:** Sayfanın üstünde, `max-w-5xl` içinde ortalanmış, 16:9 — doğru bir yerleşim.
- **Yayının başlatılması:** HLS modunda `autoplay muted` native attribute ile; iframe modunda tarayıcı/embed sağlayıcısına bağlı.
- **Otomatik oynatma davranışı:** `muted` ile birlikte olduğundan modern tarayıcı politikalarıyla uyumlu — doğru bir teknik tercih.
- **Ses kontrolü:** Native `<video controls>` üzerinden; sessize alınmış başlangıç sonrası kullanıcı sesi manuel açmalı, bu konuda hiçbir görsel ipucu ("Sesi açmak için tıklayın") yok.
- **Tam ekran:** Native tarayıcı kontrolüne bırakılmış, özel bir tam ekran butonu yok — küçük ekranlarda native kontrol çubuğu bazı tarayıcılarda gizli/küçük kalabilir.
- **Hata durumu:** **Hiç yönetilmiyor.** `resources/js/live-tv.js` içinde `hls.on(Hls.Events.ERROR, ...)` gibi bir dinleyici yok; akış koparsa kullanıcı sonsuz bir donuk ekranla baş başa kalır.
- **Yüklenme durumu:** Yükleniyor/spinner göstergesi yok; `<video>` kendi native "buffering" göstergesini kullanıyor, marka diline uygun bir loading state yok.
- **Mobil kullanım:** `aspect-video` ile responsive; ancak mobil tarayıcılarda `autoplay muted` bazı durumlarda gecikmeli başlayabilir, kullanıcıya bunu bildiren bir mesaj yok.
- **Şimdi yayında bilgisi:** Yok.
- **Sıradaki program:** Yok.
- **Yayın akışına geçiş:** Sayfada `/yayin-akisi`'ne dair hiçbir link yok — kullanıcı ancak header/footer üzerinden dolaylı gidebiliyor.
- **Program detayına geçiş:** Aynı şekilde yok; canlı TV sayfası tamamen izole.
- **Paylaşım:** Yok (URL kopyalama/sosyal paylaşım butonu bulunmuyor).
- **Erişilebilirlik:** `<video>` üzerinde altyazı/track elementi yok; canlı durumu ekran okuyucuya duyuran bir `aria-live` bölgesi yok (yalnızca görsel pulse animasyonu).

---

## 6. Canlı Radyo Deneyimi

- **Oynatıcı kullanımı:** Basit, ortalanmış kart içinde `<audio controls preload="none">` — `preload="none"` tercihi veri tasarrufu açısından doğru, ancak kullanıcı sayfaya girer girmez "otomatik başlamıyor" hissi yaratabilir (TV sayfasının autoplay davranışıyla tutarsız).
- **Mobil cihazlarda oynatma:** Native `<audio>` kontrolleri mobil tarayıcılarda çalışır, ancak medya oturumu (Media Session API) entegrasyonu yok — kilit ekranında/bildirim çubuğunda "Dost FM" başlığı ve kontrol butonları görünmez.
- **Arka planda dinleme beklentisi:** **Karşılanmıyor.** Sayfadan ayrılınca ses tamamen kesiliyor; kalıcı/sticky bir player olmadığından radyo, TV'den farklı bir kullanım deseni (uzun süreli arka plan dinleme) gerektirdiği halde bu senaryo desteklenmiyor.
- **Ses seviyesi:** Native kontrol üzerinden, ekstra bir sorun yok.
- **Yayın durumu:** "Canlı" olduğuna dair statik bir ikon var ama TV sayfasındaki gibi pulse/canlı animasyonu yok — iki canlı yayın sayfası arasında görsel dil tutarsızlığı.
- **Şimdi çalıyor bilgisi:** Hiç yok (ICY metadata parse edilmiyor).
- **Program akışı:** Radyo sayfasında yayın akışına hiç referans yok.
- **Hata mesajları:** `radio_stream_url` boşsa düz metin gösteriliyor; akış URL'i var ama sunucu yayın vermiyorsa (audio `error` event) hiçbir kullanıcı mesajı yok.
- **Kullanıcının tekrar yayına bağlanması:** Native `<audio>` bazı tarayıcılarda otomatik yeniden bağlanmayı dener, ama bunu garanti eden/bildiren bir UX yok.
- **TV ve radyo deneyiminin birbirinden ayrılması:** Görsel olarak ayrışıyorlar (TV: video+pulse; Radyo: ikon+kart) ama işlevsel olarak ikisi de "izole sayfa" modelinde, aralarında geçiş/karşılaştırma yok. Header'da her ikisi de eşit ağırlıkta link olarak duruyor — bu doğru bir eşitlik, ancak sayfa içi çapraz linkleme (TV sayfasından "Radyo dinlemek ister misiniz?" gibi) yok.

---

## 7. Programlar ve Video Arşivi

- **Program listeleme deneyimi:** `/programlar` içinde grid (2/3/4 kolon responsive), kategori filtresi query string ile, `paginate(12)`. Temiz ama arama/sıralama seçeneği yok.
- **Program detay sayfası:** Oynatıcı + başlık + kategori rozetleri + açıklama + yayın saatleri kutusu + bölüm grid'i. İyi bir tek-sayfa yapısı, ama SEO ve deep-link açısından zayıf (bkz. Bölüm 3, 12).
- **Bölüm listeleri:** Program içinde grid; `sort_order` birincil, `aired_at` ikincil sıralama — iki farklı sinyalin karışması editoryal tutarsızlığa yol açabilir (örn. eski bir bölümün sort_order'ı yanlışlıkla düşükse en üstte görünebilir).
- **Arama:** Ne program listesinde ne bölüm listesinde arama kutusu var.
- **Filtreleme:** Yalnızca kategoriye göre (programlar için); bölümler için hiç filtre yok (tarihe göre, en yeniye göre vs.).
- **Kategoriler:** `Category` modeli basit ve doğru kurulmuş (`belongsToMany`), admin panelde "yeni kategori oluştur" akışı (createOptionForm) mevcut — iyi bir yönetilebilirlik detayı.
- **Sıralama:** Kullanıcıya sunulan tek sıralama editoryal `sort_order`; kullanıcı bunu değiştiremiyor (A-Z, en yeni vb. seçenek yok).
- **Sayfalama:** Laravel'in varsayılan `links()` pagination bileşeni kullanılıyor. Laravel 12'nin varsayılan Tailwind pagination view'i açık temaya göre tasarlanmıştır (`bg-white`, `text-gray-700` gibi sınıflar); bu sitenin `bg-slate-950` koyu temasıyla **görsel olarak çakışma riski yüksektir** — özel bir pagination view yayınlanmamışsa sayfalama alanı beyaz bir kutu olarak koyu tema içinde "yamalı" görünebilir.
- **Video kartları:** Kapak görseli yoksa baş harften oluşan placeholder — makul bir fallback, ama tüm programlar için görsel eksikse grid tekdüze/monoton görünür.
- **Video kapakları:** `width`/`height` attribute'u yok → görsel yüklenmeden önce yer ayrılmıyor, layout shift (CLS) riski.
- **Video süreleri:** **Hiçbir yerde gösterilmiyor** — veri modelinde `duration` alanı hiç tanımlanmamış.
- **İçerik açıklamaları:** Program açıklaması var, `Str::limit` ile meta description'a besleniyor (iyi bir SEO detayı); bölüm açıklamaları veritabanında var ama **hiçbir view'de render edilmiyor** — admin `description` girer ama son kullanıcı hiç göremez.
- **Benzer programlar:** Yok.
- **Sonraki bölüm önerileri:** Yok, otomatik oynatma/otomatik sıradaki yok.
- **YouTube ve HLS/upload kaynaklarının tutarlılığı:** Fragman ve YouTube bölümleri iframe içinde YouTube'un kendi arayüzüyle (reklam, öneri, YouTube branding dahil) oynatılırken, yüklenen (upload) videolar sade native `<video>` ile oynatılıyor. Kullanıcı bu iki deneyim arasında geçiş yaptığında (bir bölümden diğerine) arayüz tamamen değişiyor — tutarsız ve markasız bir video izleme deneyimi.

---

## 8. Yayın Akışı

- **Günlük görünüm:** Ana sayfa widget'ında var (yalnızca bugün).
- **Haftalık görünüm:** `/yayin-akisi` sayfasında 7 gün alt alta kart olarak render ediliyor.
- **Şimdi yayında göstergesi:** Yok — ne günlük ne haftalık görünümde "şu an" vurgusu yok.
- **Geçmiş programlar:** Ayrı bir "geçmiş" kavramı yok, tüm haftanın sabit programı gösteriliyor (statik şablon, geçmiş yayın "arşivlenmiş" olarak ayrıca tutulmuyor — zaten haftalık akış bir şablon, VOD değil; bu doğru bir model ama kullanıcıya "bu hafta akışı" olduğu netleştirilmiyor).
- **Yaklaşan programlar:** Aynı sayfada, ama vurgusuz.
- **Gün seçimi:** Yok — sekme/dropdown olmadan yedi kart art arda; mobilde çok uzun bir sayfa.
- **Mobil kullanılabilirlik:** Kartlar responsive ama günler arası hızlı geçiş mekanizması olmadığından, "Perşembe" gününü görmek isteyen bir kullanıcı sayfayı elle kaydırmak zorunda.
- **Program detayına geçiş:** Program adına tıklayınca detay sayfasına gidiyor — bu doğru ve iyi çalışan bir bağlantı.
- **Canlı yayına geçiş:** Sayfada canlı TV/radyoya link yok.
- **Saat ve tarih okunabilirliği:** `H:i` formatında saat gösterimi net; `end_time` opsiyonel olduğundan bazı satırlarda yalnızca başlangıç saati görünüyor — bitiş belirsizse kullanıcı programın ne kadar süreceğini bilemiyor.

---

## 9. Kurumsal Sayfalar

- **Sayfa içeriklerinin okunabilirliği:** `prose prose-invert` ile Tailwind Typography kullanılmış — koyu temada okunabilir, iyi bir teknik tercih.
- **Başlık yapısı:** Sayfa içeriği RichEditor'dan geldiği için `h2` seviyesinde alt başlıklar var (seed verisinde görülüyor); `h1` sayfa başlığıyla çakışmıyor — hiyerarşi doğru.
- **Breadcrumb kullanımı:** **Hiç yok** — kurumsal sayfalarda kullanıcı "neredeyim, nasıl geri dönerim" sorusuna yalnızca tarayıcı geri tuşuyla cevap bulabiliyor.
- **Uzun metinlerin sunumu:** Yayın ilkeleri gibi çok başlıklı uzun sayfalarda içindekiler tablosu/kısayol linki yok; kullanıcı tüm sayfayı kaydırmak zorunda.
- **İletişim bilgileri:** Adres ve telefon düz metin; telefon `tel:` linki değil.
- **Hesap numaraları:** IBAN'lar düz metin olarak listelenmiş, kopyalama butonu yok — bağış sürtünmesi yaratıyor (bkz. 2.7).
- **Yayın ilkeleri:** İçerik gerçek ve anlamlı, kanalın değerlerini net yansıtıyor; UX açısından tek eksik sunumun düz metin olması.
- **Sık kullanılan kurumsal bağlantılar:** Header dropdown + üst seviye linkler + footer arasında dağınık; tek bir "Kurumsal" hub sayfası (tüm kurumsal linklerin listelendiği bir orta sayfa) yok.
- **Footer ile ilişkisi:** Footer, `menuPages` koleksiyonunu otomatik listeliyor — bu genişleyebilir bir yapı, ancak header'daki manuel slug gruplamasıyla senkron değil (yeni bir sayfa eklenince footer'da otomatik görünür ama header'da doğru gruba düşmeyebilir).

---

## 10. Mobil ve Responsive Deneyim

- **Header:** Masaüstünde iyi çalışıyor; **mobilde (`< lg`, yani 1024px altı) navigasyon linkleri tamamen kayboluyor ve yerine hiçbir alternatif konmuyor.** Yalnızca logo ve "Canlı İzle" butonu kalıyor. Bu, tablet boyutlarını da (`lg` genelde 1024px) kapsadığından, birçok tablet kullanıcısı da etkileniyor.
- **Menü:** Hamburger/mobil menü bileşeni kod tabanında yok (Alpine `x-data` yalnızca banner carousel ve dropdown için tanımlı).
- **Canlı TV oynatıcısı:** `aspect-video` responsive olarak iyi davranıyor.
- **Radyo oynatıcısı:** Basit kart yapısı responsive, sorun yok.
- **Video kartları:** `grid-cols-2` ile mobilde 2 kolon — küçük ekranlarda kart başına görsel alanı dar kalabilir, kapak görselleri okunaklı kalmayabilir.
- **Yayın akışı:** Yedi günlük kartlar mobilde uzun bir dikey kaydırmaya dönüşüyor, gün atlama mekanizması yok.
- **Program listeleri:** Grid responsive ama arama/filtre kontrolleri mobilde header olmadan sayfaya nasıl ulaşılacağı sorusuyla birlikte anlamını yitiriyor.
- **Uzun metin sayfaları:** `max-w-3xl` ile satır uzunluğu kontrol altında, okunabilir.
- **Dokunma alanları:** Buton/link `padding` değerleri (`px-4 py-2` vb.) genelde 40px+ dokunma hedefi sağlıyor, kabul edilebilir.
- **Font boyutları:** Tailwind varsayılan ölçek kullanılmış, mobilde küçük metinler (`text-xs`, `text-sm`) yer yer okunabilirlik sınırında (özellikle `text-slate-500` gibi düşük kontrastla birleşince).
- **Yatay kaydırma sorunları:** Kod incelemesinde sabit genişlik/`overflow` sorunu görülmedi; grid ve `max-w` kullanımları responsive tasarıma uygun.
- **Tablet görünümü:** `lg` breakpoint'inin hem masaüstü navigasyonunu hem de olası bir mobil menüyü tetiklemesi gerekirken, aradaki boşlukta (`md`–`lg` arası, örn. dikey tablet) kullanıcı ne masaüstü menüsünü ne bir mobil menüyü görüyor — en kötü senaryo tam bu aralıkta yaşanıyor.

---

## 11. Erişilebilirlik (WCAG Yaklaşımıyla)

- **Renk kontrastı:** Ana metin (`text-slate-100`/`text-white` üzerinde `bg-slate-950`) yüksek kontrastlı ve WCAG AA'yı büyük olasılıkla karşılıyor. İkincil metinlerde kullanılan `text-slate-500`/`text-slate-600` tonları koyu arka plan üzerinde WCAG AA (4.5:1) eşiğine yakın veya altında kalabilir — özellikle `text-slate-500 text-xs` kombinasyonları (kategori etiketleri, tarih bilgileri) gerçek bir kontrast ölçümüyle doğrulanmalı.
- **Klavye ile kullanım:** "Kurumsal" dropdown menüsü yalnızca `:hover` (CSS `group-hover`) ile açılıyor; klavye (`Tab`/`Enter`) veya dokunmatik ekranla **açılamıyor**. Bu tek başına bir WCAG 2.1.1 (Klavye) ihlali adayıdır.
- **Focus durumları:** İncelenen Blade dosyalarında hiçbir bileşende özel `focus-visible:` sınıfı tanımlı değil; tarayıcı varsayılan outline'ına güveniliyor, bu bazı Tailwind sıfırlama (preflight) senaryolarında görünmez hale gelebilir.
- **Form etiketleri:** Public sitede kullanıcıya açık form yok (yalnızca admin girişi/Filament formları, ki Filament kendi erişilebilirlik standartlarını taşıyor). Public tarafta risk düşük.
- **Görsel açıklamaları:** Program/bölüm/banner görsellerinde `alt` attribute'u tutarlı şekilde dolduruluyor (`{{ $program->name }}` vb.) — bu iyi bir pratik. Dekoratif SVG ikonlarda (`aria-hidden="true"`) tanımlı değil, ekran okuyucular gereksiz simge açıklamaları duyurabilir.
- **Video oynatıcı kontrolleri:** Native `<video controls>`/`<audio controls>` kullanımı temel erişilebilirlik desteğini (klavye, ekran okuyucu) tarayıcıdan miras alıyor — bu doğru bir taban. Ancak hiçbir videoda altyazı/transkript (`<track>`) yok.
- **Ekran okuyucu uyumu:** "Canlı" pulse animasyonları yalnızca görsel; ekran okuyucuya "canlı yayın" durumunu bildiren `aria-live` veya görünmez metin yok.
- **Başlık hiyerarşisi:** Sayfa başına tek `h1` kullanımı genel olarak doğru; ana sayfada hero `h1` ve altında `h2`'ler mantıklı sırada.
- **Hareketli içerikler:** Banner carousel ve pulse animasyonları `prefers-reduced-motion` medya sorgusuna göre durdurulmuyor — hareket hassasiyeti olan kullanıcılar için bir iyileştirme alanı.
- **Metin okunabilirliği:** Genel gövde metni okunabilir; satır uzunlukları `max-w` ile kontrol altında.

---

## 12. SEO Deneyimi

- **Sayfa başlıkları:** Her rota kendi `@section('title', ...)` tanımına sahip — iyi bir temel (ör. program adı + "Dost TV").
- **Meta açıklamaları:** Yalnızca layout'ta bir varsayılan (`@yield('description', ...)`) ve program detay sayfasında (`Str::limit(strip_tags(...))`) özelleştirilmiş açıklama var. Diğer sayfalar (Programlar listesi, Yayın Akışı, kurumsal sayfalar) varsayılan/genel açıklamayı kullanıyor — sayfa bazlı özgünlük eksik.
- **URL yapıları:** Türkçe, okunabilir ve slug tabanlı (`/programlar/{slug}`, `/canli-tv`, `/yayin-akisi`) — SEO açısından güçlü bir temel.
- **Program detay sayfaları:** Kendi başlığı ve açıklaması var; ancak bölümlerin **kendi URL'i olmadığı için tek bir program sayfası tüm bölümleri "gizliyor"** — her bölüm ayrı indekslenebilir bir içerik olabilecekken bu potansiyel tamamen kayboluyor (arama motorları yalnızca program adını görür, onlarca bölüm başlığını asla göremez).
- **Video detay sayfaları:** Yok (bkz. yukarısı) — bu SEO açısından en büyük kayıp alanı.
- **Kurumsal sayfalar:** Başlık/URL yapısı düzgün; ancak içerik uzun ve tek `h1` + gövde metni dışında yapılandırılmış veri yok.
- **Canonical kullanımı:** Hiçbir view'de `<link rel="canonical">` yok.
- **Open Graph:** Hiç yok — sosyal medyada (WhatsApp, Facebook, Twitter/X) paylaşılan linkler başlıksız/görselsiz, düz URL olarak görünecektir. Bir yayıncı kanalı için ciddi bir eksik.
- **Schema.org:** Hiç yapılandırılmış veri (JSON-LD) yok — `Organization`, `BroadcastEvent`/`BroadcastService`, `VideoObject`, `WebSite` (arama kutusu için `SearchAction`) gibi şema tipleri Google'ın canlı yayın/video içerikli sitelerde zengin sonuç göstermesi için kritik.
- **Breadcrumb:** UI'da yok, `BreadcrumbList` şema işaretlemesi de yok.
- **İç linkleme:** Program ↔ yayın akışı ↔ ana sayfa arasında bağlantılar mevcut, ama bölüm seviyesinde iç linkleme (bir bölümden başka bir bölüme, "benzer programlar"a) hiç yok — site içi link grafiği sığ kalıyor.
- **`robots.txt` / sitemap:** `robots.txt` tüm botlara izin veriyor (`Disallow:` boş — doğru), ancak `Sitemap:` satırı yok ve projede hiçbir sitemap üretim mekanizması (statik veya dinamik) bulunmuyor.

---

## 13. Performansın UX Etkisi

*(Not: Aşağıdaki değerlendirmeler kod/mimari incelemesine dayanır; gerçek Lighthouse/RUM ölçümü yapılmamıştır.)*

- **İlk açılış:** Sunucu tarafı render (klasik Blade), istemci tarafında ağır bir SPA yükü yok — bu, ilk anlamlı boyamaya (FCP) genelde olumlu katkı sağlayan bir mimari tercih.
- **Görseller:** Hiçbir `<img>` etiketinde `width`/`height` veya `loading="lazy"` attribute'u yok. Bu iki eksiklik birlikte: (a) görsel yüklenmeden yer ayrılmaması nedeniyle Cumulative Layout Shift (CLS) riski, (b) ekran dışındaki (below-the-fold) program kartı görsellerinin de eager yüklenmesi nedeniyle gereksiz bant genişliği tüketimi anlamına geliyor.
- **Video kapakları:** Aynı şekilde boyut/lazy-loading optimizasyonu yok; ayrıca tüm görseller orijinal yüklenen boyutta servis ediliyor, responsive `srcset` / thumbnail varyantı üretimi yok (disk üzerinde tek boyut saklanıyor).
- **HLS oynatıcı:** `hls.js` yalnızca `live_tv_type === 'hls'` olduğunda ayrı bir Vite girişiyle (`@vite('resources/js/live-tv.js')`) yükleniyor — bu **iyi bir performans kararı**, gereksiz yere her sayfada büyük bir kütüphane taşınmıyor.
- **JavaScript yükü:** Genel JS yükü hafif (Alpine.js + koşullu hls.js); ağır bir framework/bundle riski yok.
- **Livewire etkileşimleri:** Public sitede Livewire kullanılmadığından bu maddenin doğrudan bir karşılığı yok; kategori filtreleme gibi etkileşimler tam sayfa yenilemesiyle çalışıyor — bu basitliği artırır ama "anlık" (SPA benzeri) his vermez.
- **Skeleton ve loading durumları:** Hiçbir sayfada iskelet/yüklenme animasyonu yok; sunucu render modelinde bu genelde daha az kritik olsa da, video/görsel yüklenme süresince kullanıcıya geri bildirim eksikliği (özellikle HLS başlatma anında) fark edilir bir boşluk.
- **Layout shift:** Görsellerdeki boyut attribute eksikliği + banner carousel'in yüklenmeden önce yer kaplamaması (aspect-ratio class'ı var, bu olumlu) karışık bir tablo çiziyor — carousel için risk düşük, kart görselleri için risk orta.
- **Mobil performans:** Sunucu render + hafif JS mimarisi mobil için temelde uygun; ancak optimize edilmemiş görseller mobil veri/performansını en çok etkileyecek unsur.
- **Zayıf internet bağlantısındaki deneyim:** HLS/iframe canlı yayın düşük bant genişliğinde donabilir ve hiçbir hata/yeniden bağlanma mesajı sunulmadığından kullanıcı "yayın bitti mi, bağlantım mı koptu" ayrımını yapamaz — bu, zayıf bağlantı senaryosunda en büyük UX riskidir.

---

## 14. Sorunların Önceliklendirilmesi

| # | Sorun | Etkilenen Sayfa | Kullanıcıya Etkisi | Önerilen Çözüm | Teknik Zorluk | Beklenen Fayda |
|---|---|---|---|---|---|---|
| 1 | **Kritik** — Mobilde ana navigasyon menüsü hiç yok | Tüm site (layout header) | Mobil kullanıcılar Programlar, Yayın Akışı, Radyo, Kurumsal, İletişim'e ulaşamıyor | Hamburger menü + Alpine ile aç/kapa mobil navigasyon paneli ekle | Düşük-Orta | Çok yüksek |
| 2 | **Kritik** — Bölümlerin (episode) bağımsız URL'i yok | Program detay sayfası | Paylaşım, deep-link, SEO, geri tuşu çalışmıyor | `/programlar/{program}/{episode}` rotası + controller/view ekle | Orta | Çok yüksek |
| 3 | **Yüksek** — "Kurumsal" dropdown yalnızca hover ile açılıyor | Header | Klavye/dokunmatik kullanıcılar menüye erişemiyor | `:focus-within` + tıklama toggle ekle | Düşük | Yüksek |
| 4 | **Yüksek** — Canlı TV/Radyo'da "şimdi/sırada" bilgisi yok | live/tv, live/radio | Kullanıcı ne izlediğini/dinlediğini bilmiyor | Schedule verisinden "şu an" hesaplayıp sayfada göster | Orta | Yüksek |
| 5 | **Yüksek** — HLS oynatıcıda hata/yeniden bağlanma yönetimi yok | live/tv (HLS modu) | Yayın koparsa kullanıcı donuk ekranla kalır | `hls.js` error event dinleyicisi + kullanıcı mesajı + retry | Orta | Yüksek |
| 6 | **Yüksek** — Radyo sayfadan ayrılınca duruyor, kalıcı player yok | live/radio, tüm site | "Arka planda dinleme" beklentisi karşılanmıyor | Site genelinde sticky mini-player (state Alpine/localStorage ile taşınır) | Orta-Yüksek | Yüksek |
| 7 | **Yüksek** — Program/bölüm aramada arama kutusu yok | programs.index, program detay | İçerik keşfi zorlaşıyor | Basit `LIKE` arama + query string, ileride full-text | Düşük-Orta | Yüksek |
| 8 | **Yüksek** — Ana sayfada "son eklenen video" bölümü yok | home | Taze içerik keşfi kayboluyor | Episode `latest()` sorgusuyla yeni bir home section | Düşük | Yüksek |
| 9 | **Yüksek** — Open Graph / schema.org hiç yok | Tüm sayfalar | Sosyal paylaşımlar başlıksız/görselsiz, zengin sonuç yok | OG meta + JSON-LD (Organization, VideoObject, BroadcastEvent) ekle | Orta | Yüksek |
| 10 | Orta — Bölüm süresi (duration) hiç yok | Program detay, bölüm kartları | Kullanıcı içerik uzunluğunu bilmiyor | `episodes.duration` alanı + admin form + kart gösterimi | Düşük | Orta |
| 11 | Orta — Yayın akışında "şimdi" vurgusu yok | schedule.index, home widget | Kullanıcı saatleri manuel karşılaştırıyor | Sunucu tarafında şu anki saat/gün ile karşılaştırıp CSS vurgusu | Düşük | Orta |
| 12 | Orta — Banner carousel erişilebilir değil (hover durdurmuyor, aria yok, reduced-motion yok) | home | Hareket hassasiyeti olan/klavye kullanıcıları etkileniyor | `aria-label`, `prefers-reduced-motion`, hover/focus durdurma | Düşük | Orta |
| 13 | Orta — Görsellerde width/height ve lazy-loading yok | Tüm site | CLS ve gereksiz veri tüketimi | `width`/`height` + `loading="lazy"` (above-fold hariç) | Düşük | Orta |
| 14 | Orta — Sitemap yok, `robots.txt`'de sitemap satırı yok | SEO altyapısı | Arama motorları içerik keşfini yavaş yapıyor | Dinamik sitemap route'u + robots.txt güncellemesi | Düşük | Orta |
| 15 | Orta — Breadcrumb hiçbir yerde yok | Programlar, kurumsal sayfalar | Konum/geri dönüş belirsiz | Basit breadcrumb bileşeni + `BreadcrumbList` şeması | Düşük | Orta |
| 16 | Orta — Telefon/IBAN'lar tıklanabilir/kopyalanabilir değil | İletişim, Vakıf sayfası | Bağış/arama sürtünmesi | `tel:` linkleri + IBAN kopyala butonu (Alpine) | Düşük | Orta |
| 17 | Orta — Laravel varsayılan pagination görünümü koyu temayla çakışabilir | programs.index | Sayfalama alanı görsel olarak bozuk görünebilir | Özel Tailwind-dark pagination view yayınla | Düşük | Orta |
| 18 | Orta — YouTube vs upload video kaynakları arasında tutarsız oynatıcı deneyimi | Program detay, bölümler | Marka bütünlüğü ve izleme deneyimi tutarsız | Mümkünse tüm videoları tek bir player katmanı (ör. Plyr) ile sarmalama | Orta | Orta |
| 19 | Düşük-Orta — Odak (focus) stilleri tanımlı değil, skip-link yok | Tüm site | Klavye kullanıcıları için gezinme zorlaşıyor | `focus-visible:ring` sınıfları + "İçeriğe geç" linki | Düşük | Orta |
| 20 | Düşük — Test kapsamı yalnızca iskelet (`ExampleTest`) | Genel | Regresyonlar fark edilmeden yayına çıkabilir | Kritik akışlar (routing, model ilişkileri) için Feature testleri | Orta | Orta (uzun vadede yüksek) |

---

## 15. Mutlaka Düzeltilmesi Gereken 30 Madde

1. **Mobil navigasyon menüsünün tamamen eksik olması** — Neden önemli: Mobil kullanıcılar siteyi fiilen kullanamıyor (yalnızca Canlı TV erişilebilir). Çözüm: Hamburger menü + off-canvas panel ekle. Öncelik: **Kritik**
2. **Bölümlerin bağımsız URL/route'a sahip olmaması** — Neden önemli: Paylaşım, SEO, deep-link, tarayıcı geçmişi tamamen çalışmıyor. Çözüm: Episode için özel rota ve view oluştur. Öncelik: **Kritik**
3. **Footer'da "Programlar" linkinin bulunmaması** — Neden önemli: Header olmayan mobilde tek erişim yolu olan footer'da bile bu temel sayfaya link yok. Çözüm: Footer link listesine ekle. Öncelik: **Kritik**
4. **"Kurumsal" dropdown'ın yalnızca hover ile çalışması** — Neden önemli: Klavye ve dokunmatik kullanıcılar erişemiyor (WCAG 2.1.1). Çözüm: Tıklama/focus tabanlı toggle. Öncelik: **Yüksek**
5. **HLS canlı yayında hata/yeniden bağlanma yönetimi olmaması** — Neden önemli: Kesinti anında kullanıcı sonsuz donuk ekranla kalıyor. Çözüm: `Hls.Events.ERROR` dinleyicisi + retry + mesaj. Öncelik: **Yüksek**
6. **Radyonun sayfa değişince durması, kalıcı player olmaması** — Neden önemli: Radyo ürününün temel kullanım şekli (arka planda dinleme) desteklenmiyor. Çözüm: Site geneli sticky mini-player. Öncelik: **Yüksek**
7. **Canlı TV/Radyo sayfalarında "şimdi/sırada" bilgisinin olmaması** — Neden önemli: Kullanıcı yayın bağlamını kaybediyor. Çözüm: Schedule verisinden anlık hesaplama. Öncelik: **Yüksek**
8. **Program/bölüm aramasının hiç olmaması** — Neden önemli: İçerik arttıkça keşif imkansızlaşıyor. Çözüm: Ad bazlı arama kutusu. Öncelik: **Yüksek**
9. **Ana sayfada "son eklenen bölümler" bölümünün olmaması** — Neden önemli: Düzenli üretilen taze içerik hiç öne çıkmıyor. Çözüm: Episode `latest()` home section. Öncelik: **Yüksek**
10. **Open Graph / Twitter Card etiketlerinin tamamen eksik olması** — Neden önemli: Sosyal paylaşımlar markasız/görselsiz görünüyor. Çözüm: Dinamik OG meta bileşeni. Öncelik: **Yüksek**
11. **Schema.org (JSON-LD) yapılandırılmış verisinin olmaması** — Neden önemli: Google'da zengin sonuç ve canlı yayın/video tanınırlığı kayboluyor. Çözüm: Organization, VideoObject, BroadcastEvent şemaları. Öncelik: **Yüksek**
12. **Ana sayfada bağış/destek CTA'sının hiç bulunmaması** — Neden önemli: Vakıf bağışı gömülü bir alt sayfada kaybolmuş. Çözüm: Ana sayfa/footer'a görünür "Bağış Yap" CTA'sı. Öncelik: **Yüksek**
13. **Bölüm süresi (duration) alanının veritabanında hiç olmaması** — Neden önemli: Kullanıcı hiçbir içeriğin uzunluğunu göremiyor. Çözüm: Migration + form alanı + gösterim. Öncelik: **Orta-Yüksek**
14. **Sitemap.xml'in hiç olmaması** — Neden önemli: Arama motoru keşfi yavaşlıyor. Çözüm: Dinamik sitemap route + robots.txt referansı. Öncelik: **Orta**
15. **Yayın akışında "şu an" satırının vurgulanmaması** — Neden önemli: Kullanıcı saatleri manuel karşılaştırmak zorunda. Çözüm: Sunucu tarafı "current" hesaplama + CSS vurgusu. Öncelik: **Orta**
16. **Yayın akışı sayfasında gün seçim/atlama mekanizmasının olmaması** — Neden önemli: Mobilde 7 günü kaydırmak gerekiyor. Çözüm: Gün sekmeleri/dropdown, "bugün"e otomatik odak. Öncelik: **Orta**
17. **Görsellerde width/height ve lazy-loading eksikliği** — Neden önemli: Layout shift ve gereksiz veri tüketimi. Çözüm: Attribute ekle, above-fold hariç lazy-load. Öncelik: **Orta**
18. **Telefon numaralarının `tel:` linki olmaması** — Neden önemli: Mobilde tek tıkla arama yapılamıyor. Çözüm: `<a href="tel:...">`. Öncelik: **Orta**
19. **IBAN'ların kopyalanabilir olmaması** — Neden önemli: Bağış akışında gereksiz sürtünme. Çözüm: Kopyala butonu (Alpine + Clipboard API). Öncelik: **Orta**
20. **Laravel varsayılan pagination görünümünün koyu temayla çakışma riski** — Neden önemli: Sayfalama alanı bozuk görünebilir. Çözüm: Özel dark pagination view yayınla. Öncelik: **Orta**
21. **Breadcrumb'ın hiçbir sayfada olmaması** — Neden önemli: Konum/geri dönüş belirsizleşiyor, SEO breadcrumb şeması da eksik. Çözüm: Basit breadcrumb bileşeni. Öncelik: **Orta**
22. **YouTube ve yüklenen video kaynakları arası tutarsız oynatıcı deneyimi** — Neden önemli: Marka bütünlüğü ve izleme deneyimi kırılıyor. Çözüm: Ortak bir player sarmalayıcı (ör. Plyr) değerlendir. Öncelik: **Orta**
23. **Banner carousel'in erişilebilir olmaması (hover-durdurma, aria, reduced-motion yok)** — Neden önemli: Hareket hassasiyeti/klavye kullanıcıları etkileniyor. Çözüm: `aria-label`, durdurma, `prefers-reduced-motion`. Öncelik: **Orta**
24. **Odak (focus) stillerinin tanımlı olmaması, skip-link eksikliği** — Neden önemli: Klavye erişilebilirliği zayıf. Çözüm: `focus-visible` stilleri + "içeriğe geç" linki. Öncelik: **Orta**
25. **Bölüm açıklamalarının admin'de girilip hiçbir yerde gösterilmemesi** — Neden önemli: Editoryal emek kullanıcıya ulaşmıyor. Çözüm: Bölüm kartı/detayında açıklamayı render et. Öncelik: **Orta**
26. **Sosyal medya bağlantılarının header/footer'da hiç olmaması** — Neden önemli: Marka varlığının diğer kanallara yönlendirilememesi. Çözüm: Footer'a sosyal ikon grubu ekle. Öncelik: **Orta**
27. **Video kapaklarının responsive/optimize varyantlarının olmaması** — Neden önemli: Gereksiz bant genişliği, yavaş yüklenme. Çözüm: Yükleme sırasında görsel yeniden boyutlandırma/optimize etme pipeline'ı. Öncelik: **Düşük-Orta**
28. **Kategori filtrelemenin tam sayfa yenilemeyle çalışması (canlı/AJAX olmaması)** — Neden önemli: Modern kullanıcı beklentisine göre "yavaş" hissediyor. Çözüm: İsteğe bağlı hafif Livewire/Alpine+fetch entegrasyonu. Öncelik: **Düşük**
29. **Otomatik test kapsamının yalnızca iskelet olması** — Neden önemli: Yeni özellik eklerken regresyon riski yüksek. Çözüm: Routing/model ilişkileri için Feature testleri yaz. Öncelik: **Düşük (uzun vadede kritik)**
30. **Header'daki manuel slug bazlı "Kurumsal" gruplamasının kırılgan olması** — Neden önemli: Yeni bir kurumsal sayfa eklenince otomatik doğru gruba düşmüyor. Çözüm: `Page` modeline `group`/`parent` alanı ekleyip menü mantığını veri odaklı yap. Öncelik: **Düşük-Orta**

---

## 16. Sonuç

**Puanlamalar (10 üzerinden):**

| Boyut | Puan | Kısa gerekçe |
|---|---|---|
| Mevcut UX kalitesi (genel) | **5.5 / 10** | Kavramsal IA doğru ve görsel dil tutarlı, ama mobil navigasyon ve video arşivi mimarisi gibi temel kırılmalar genel puanı düşürüyor. |
| Mobil deneyim | **3 / 10** | Ana navigasyonun tamamen eksik olması tek başına bu puanı belirliyor; içerik yapıları responsive olsa da erişilemez durumda. |
| İçerik keşfi | **4 / 10** | Arama yok, "son eklenenler" yok, bölüm deep-link'i yok; keşif yalnızca editoryal sıralamaya (sort_order) bağımlı. |
| Canlı yayın deneyimi | **5 / 10** | Temel oynatma çalışıyor ve teknik tercihler (muted autoplay, lazy hls.js) doğru, ama hata yönetimi, "şimdi/sırada" bağlamı ve radyonun kalıcılığı eksik. |
| Yönetilebilirlik (admin) | **7.5 / 10** | Filament paneli gerçekten iyi kurulmuş: otomatik slug, ilişki seçimi, görsel yükleme, kategori oluşturma akışı — içerik ekibi için düşük sürtünmeli. Eksi puan: video süresi/bölüm açıklaması gibi girilen verinin frontend'e yansımaması ve otomatik test güvencesinin olmaması. |

**Projenin profesyonel bir dijital yayın platformuna dönüşmesi için en önemli beş karar:**

1. **Mobil-önce navigasyonu yeniden kur.** Şu anki "masaüstünde tam menü, mobilde hiç menü" durumu tek başına en yüksek etkili düzeltme; trafiğin çoğunluğu mobil olacak bir TV/radyo sitesinde bu bir öncelik değil, bir ön koşuldur.
2. **Bölümü (episode) birinci sınıf bir varlık haline getir.** Kendi URL'i, kendi meta verisi, kendi paylaşım yüzeyi olmayan bir video arşivi hem SEO hem kullanıcı deneyimi açısından potansiyelinin çok altında kalır; bu, "video arşivi" vaadinin teknik temelini oluşturur.
3. **Canlı TV ve Canlı Radyo'yu bağlama oturt.** "Şimdi ne yayında/çalıyor", hata durumunda ne olacağı ve radyonun sayfalar arası kalıcılığı çözülmeden, bu iki modül birer "embed sayfası" olmaktan öteye geçemez.
4. **SEO ve sosyal paylaşım temelini (OG + schema.org + sitemap) kur.** İçerik zaten üretiliyor; bu yatırımın karşılığını arama ve sosyal kanallardan almak için yapılandırılmış veri ve paylaşım meta'sı şart.
5. **Keşif katmanını (arama + "son eklenenler" + bağış CTA'sı) ekle.** Sıralı/statik listelerden ibaret bir arşiv, içerik arttıkça kullanılamaz hale gelir; aynı şekilde kurumun asli hedeflerinden biri olan bağış çağrısı, ana deneyimin bir parçası değil bir alt sayfa notu olarak kalmamalı.

---

*Bu rapor, mevcut kod tabanının statik incelemesine dayanmaktadır. Kontrast, performans (Lighthouse/CWV) ve gerçek kullanıcı testleri gibi ölçüm gerektiren bulgular için canlı ortamda ek doğrulama yapılması önerilir.*
