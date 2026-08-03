<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class StaticPagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Yayıncı Künye Bilgisi',
                'slug' => 'yayinci-kunye-bilgisi',
                'show_in_menu' => true,
                'sort_order' => 1,
                'content' => <<<'HTML'
                    <h2>Şirket Bilgileri</h2>
                    <ul>
                        <li><strong>Kuruluş adı:</strong> Ankara İletişim Hizmetleri A.Ş.</li>
                        <li><strong>Kanal adı:</strong> Dost TV</li>
                        <li><strong>Yayın türü:</strong> Uydu, tematik</li>
                        <li><strong>Lisans:</strong> U-TV</li>
                        <li><strong>Merkez:</strong> İstanbul Caddesi Devrez Sok No 1, İskitler/Ankara</li>
                        <li><strong>Web:</strong> dosttv.com</li>
                        <li><strong>Telefon:</strong> +90 312 341 21 21</li>
                    </ul>
                    <h2>Sorumlu Müdür</h2>
                    <p>Bekir Ceylan</p>
                    <h2>İzleyici Temsilcisi</h2>
                    <p>İlkay Bayer Şimşek</p>
                    HTML,
            ],
            [
                'title' => 'Dost TV Yayın İlkeleri',
                'slug' => 'dost-tv-yayin-ilkeleri',
                'show_in_menu' => true,
                'sort_order' => 2,
                'content' => <<<'HTML'
                    <p>Dost TV, geçici değil kalıcı değerleri yansıtmayı hedefleyen bir iletişim platformudur. Kuruluş, "biz dostuz" ilkesinden hareket ederek, adil ve merhametli yayıncılığı benimsemiştir.</p>
                    <h2>Yayın Felsefesi</h2>
                    <p>Platform, tematik televizyonculuğun ülkemizdeki en özgün örneklerinden biri olarak konumlandırılmaktadır. Aktüel ve popüler içerik yerine gerçek, iyilik ve güzellik arayışına odaklanır.</p>
                    <h2>Temel Değerler</h2>
                    <ul>
                        <li>Sevgi ve merhamet temelli yayıncılık</li>
                        <li>Reklam politikası yalnızca kurumsal sürdürülebilirliğe yönelik</li>
                        <li>Tüketim kültürüne direniş</li>
                        <li>Rating kaygısı taşımaz; tek bir kişiye ulaşmayı yeterli görür</li>
                    </ul>
                    <h2>İçerik Yaklaşımı</h2>
                    <p>Saldırgan, dışlayıcı veya özel çıkarları koruyan dilden kaçınır. Eğitim ve paylaşım merkezli bir dil kullanmayı tercih eder.</p>
                    <h2>Amacı</h2>
                    <p>Hakikate mütevazi bir ayna olmak ve insan doğasının saflığını ortaya çıkarmaktır.</p>
                    HTML,
            ],
            [
                'title' => 'Neden Dost TV',
                'slug' => 'neden-dost-tv',
                'show_in_menu' => true,
                'sort_order' => 3,
                'content' => <<<'HTML'
                    <p>Dost TV, insanların büyük dünya olaylarından ziyade kişisel gelişime ve manevi bağlantıya odaklanmasını teşvik eder. Kanal, küçük ama bir insan için büyük adımlar atmaya çağırır — özellikle kişinin kendini ve yaratıcısını tanımasına yönelik.</p>
                    <h2>Temel Felsefe</h2>
                    <ul>
                        <li><strong>İletişimin Gücü:</strong> İnsanı insan kılan imtiyazlardan biri ona sözle iletişimin bahşedilmiş olmasıdır.</li>
                        <li><strong>Kalp Odaklılık:</strong> Söylenen her şey kalpten doğar ve kalbe hitap eder.</li>
                        <li><strong>Görselliğe Karşıt Duruş:</strong> Görüntü kirliliğine karşı sıcak, samimi, gerçek bir dünyanın meltemiyle insanı kucaklar.</li>
                    </ul>
                    <h2>Misyon</h2>
                    <p>Kanal, insanlara yalnız olmadıklarını hatırlatarak samimi bir bağlantı kurma amacıyla programlar yayınlar.</p>
                    HTML,
            ],
            [
                'title' => 'Dost Vakfı Hesap Numaraları',
                'slug' => 'dost-vakfi-hesap-numaralari',
                'show_in_menu' => true,
                'sort_order' => 4,
                'content' => <<<'HTML'
                    <p>Dost Vakfı'na bağışlarınız için kullanabileceğiniz banka hesap bilgileri aşağıdadır.</p>
                    <h2>Kuveyt Türk</h2>
                    <p>IBAN: TR85 0020 5000 0022 2122 2000 01</p>
                    <h2>Vakıf Katılım</h2>
                    <p>IBAN: TR10 0021 0000 0007 7171 0000 01</p>
                    HTML,
            ],
            [
                'title' => 'İletişim',
                'slug' => 'iletisim',
                'show_in_menu' => true,
                'sort_order' => 5,
                'content' => <<<'HTML'
                    <h2>Adres</h2>
                    <p>Zübeyde Hanım, İstanbul Cad., Devrez Sok. No:1, 06070 Altındağ/Ankara</p>
                    <h2>Telefon</h2>
                    <p>(0312) 341 21 21</p>
                    <h2>Sosyal Medya</h2>
                    <p><a href="https://www.youtube.com/@DostRadyoTV">YouTube: @DostRadyoTV</a></p>
                    HTML,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
