# DOST TV CMS Proje Rehberi (Project Handbook)

## 📌 YouTube Playlist İçe Aktar Modülü

DOST TV CMS paneli, editörlerin YouTube üzerindeki oynatma listelerini (playlist) doğrudan sisteme aktararak toplu bölüm (`Episode`) oluşturmasını sağlar.

### 🔑 API Yapılandırması (`.env`)

YouTube Data API v3 üzerinden playlist verilerinin çekilebilmesi için `.env` dosyasında geçerli bir Google YouTube Data API v3 anahtarının bulunması gerekir:

```env
YOUTUBE_API_KEY=AIzaSy...
```

> **Güvenlik Uyarısı:** `YOUTUBE_API_KEY` değişkenini asla kaynak kodlara, git reposuna veya istemci tarafına (JS) eklemeyin.

---

### 🚀 Editör Kullanım Akışı

1. Yönetim panelinde **Program ve Video Yönetimi ➔ Bölümler** (`/admin/episodes`) ekranına gidin.
2. Sağ üst köşedeki **"YouTube Playlist İçe Aktar"** butonuna tıklayın.
3. Açılan `/admin/episodes/youtube-import` formunda:
   - **Program:** Bölümlerin ekleneceği hedef programı seçin.
   - **YouTube Playlist URL:** Aktarılacak playlist bağlantısını yapıştırın (Örn: `https://www.youtube.com/playlist?list=PL...`).
   - **Opsiyonel Ayarlar:** Sezon Numarası, Başlangıç Bölüm Numarası veya "Program adını başlıktan kaldır" seçeneğini belirleyin.
4. **[ Playlist'i Kontrol Et ]** butonuna tıklayarak önizleme tablosunu yükleyin.
5. Önizleme tablosunda videoların **"Yeni"** veya **"Zaten Var"** (mükerrer) durumlarını inceleyin.
6. **[ Bölümleri Oluştur ]** butonuna tıklayarak yalnızca yeni videoları veritabanına kaydedin.
