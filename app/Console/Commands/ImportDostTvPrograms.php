<?php

namespace App\Console\Commands;

use App\Models\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportDostTvPrograms extends Command
{
    protected $signature = 'import:dosttv-programs {--limit=0}';

    protected $description = 'dosttv.com sitesindeki tv_show sayfalarını çekip Program kayıtlarına aktarır';

    private const SLUGS = [
        'hikmet-arayislari', 'hayatus-sahabe', 'dost-tv-ozel', 'cocuk-ve-biz', 'bir-garip-yolcu',
        'kim-bilir', 'merak-ettiklerimiz', 'hayat-guzeldir', 'beraber-okuyalim', 'kuran-ogreniyorum',
        'rahmet-peygamberi', 'temasa', 'yad-i-cemil', 'kuran-arapcasi-2', 'mukabele',
        'sifa-i-serif-sohbetleri', 'cevsenul-kebir', 'israkiye', 'sabahin-duasi', 'kuran-tefsiri',
        'kuranin-kalbine-yolculuk-2', 'cevap-arayan-sorular', 'elmas-hikayeler', 'faruk-ve-faruk',
        'gozumun-nuru-namaz', 'hacinin-ayak-izleri', 'gundem-hac', 'hayat-akarken',
        'her-yoreden-kuran-ogreniyorum', 'hoca-camide', 'kuran-dersleri', 'kuran-meali',
        'kulli-dusturlar', 'lahikalar', 'nurlu-hatiralar', 'okudukca', 'omur-boyu-saglik', 'pusula',
        'rahmanin-evleri', 'tecdid-i-iman', 'semaili-serif-sohbetleri', 'sozun-ozu',
        'sevgili-ile-bir-gun', 'saglik-rehberi', 'ruhun-silasi-hac', 'ruhul-beyan-sohbetleri',
        'vakit-varken-hac', 'yansimalar', 'yesribde-bahar', 'yoldaki-isaretler', 'z-kusagi',
        'cinaralti', 'riyazus-salihin-sohbetleri', 'insanin-anlam-arayisi', 'hemhal',
        'alti-cizili-satirlar', 'saglik-olsun', 'seyyah', 'ayet-ayet', 'allahin-evine-yolculuk',
        'yildizlarin-izinde', 'andan-zamana', 'cuma-gecesi', 'cumayi-anliyoruz', 'gercegin-pesinde',
        'kavram-atolyesi', 'mehmet-firinci-ile-nur-sohbetleri', 'minik-muhabbet', 'donum-noktasi',
        'risale-okulu', 'merdiven', 'sorularla-islam', 'buhar-i-serif-dersleri', 'saykal',
        'cocuk-ve-aile-rehberi', 'kalp-sevmekten-yorulmaz', 'akla-kapi', 'her-gune-bir-teselli',
        'bir-baba-bir-ogul', 'kuran-bahcesi', 'seherler-ve-sahurlar', 'muhabbet-seyyahi',
        'zamanin-aynasinda', 'hikmet-arayislari-ramazan-ozel', 'ramazanin-huzuru',
        'seherler-sahurlar', 'zirve-lider-hz-peygamber', 'hakikat-cekirdekleri',
        'risale-okumalari-sozler-hayri-akinci', 'bab-i-reyyan', 'soze-yar-olmak', 'dost-meclisi',
        'hakikat-iklimi', 'yaz-aksamlari', 'sozler', '56685', '56688', 'hanim-sahabeler', '56975',
        'seherler-sahurlar-2', 'mukabele-2', 'kalbi-kuduste-kalanlar', 'mektubat', 'ramazanda-saglik',
        'kalbe-sifa-ayetler', 'bab-i-reyyan-2', 'mektubat-2',
    ];

    public function handle(): int
    {
        $slugs = self::SLUGS;
        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $slugs = array_slice($slugs, 0, $limit);
        }

        $imported = 0;
        $skipped = 0;

        foreach ($slugs as $slug) {
            $url = "https://dosttv.com/tv-show/{$slug}/";

            try {
                $response = Http::timeout(20)->withOptions(['verify' => 'D:/php/cacert.pem'])->get($url);
            } catch (\Throwable $e) {
                $this->warn("Atlandı ({$slug}): {$e->getMessage()}");
                $skipped++;

                continue;
            }

            if (! $response->ok()) {
                $this->warn("Atlandı ({$slug}): HTTP {$response->status()}");
                $skipped++;

                continue;
            }

            $data = $this->parse($response->body());

            if (blank($data['title'])) {
                $this->warn("Atlandı ({$slug}): başlık bulunamadı");
                $skipped++;

                continue;
            }

            $coverPath = $data['image'] ? $this->downloadImage($data['image'], $slug) : null;

            Program::updateOrCreate(
                ['slug' => Str::slug($slug)],
                [
                    'name' => $data['title'],
                    'description' => $data['description'],
                    'cover_image' => $coverPath,
                    'is_active' => true,
                ]
            );

            $this->info("İçe aktarıldı: {$data['title']}");
            $imported++;
        }

        $this->newLine();
        $this->info("Toplam: {$imported} program içe aktarıldı, {$skipped} atlandı.");

        return self::SUCCESS;
    }

    /**
     * @return array{title: ?string, description: ?string, image: ?string}
     */
    private function parse(string $html): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        $title = null;
        $titleNodes = $xpath->query('//title');
        if ($titleNodes->length > 0) {
            $title = trim($titleNodes->item(0)->textContent);
            $title = preg_replace('/\s*[–\-]\s*Dost\s*TV\s*$/u', '', $title);
        }

        $description = null;
        $paragraphs = $xpath->query('//p');
        foreach ($paragraphs as $p) {
            $text = trim($p->textContent);
            if (mb_strlen($text) >= 60 && ! str_contains($text, '{')) {
                $description = $text;
                break;
            }
        }

        $image = null;
        $imgNodes = $xpath->query('//img[contains(@class, "wp-post-image")]');
        if ($imgNodes->length > 0) {
            $image = $imgNodes->item(0)->getAttribute('src');
        }

        return compact('title', 'description', 'image');
    }

    private function downloadImage(string $url, string $slug): ?string
    {
        try {
            $response = Http::timeout(20)->withOptions(['verify' => 'D:/php/cacert.pem'])->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $path = "programs/{$slug}.{$extension}";

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }
}
