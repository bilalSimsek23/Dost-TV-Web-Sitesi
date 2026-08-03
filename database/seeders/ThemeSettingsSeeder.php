<?php

namespace Database\Seeders;

use App\Models\ThemeSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the full "Dost TV Dark" default token catalog used by the Filament
 * Tema Ayarları page. The original 13 keys (color/typography basics)
 * from Aşama 1 are left untouched by updateOrCreate(); this only appends
 * the remaining fields the Aşama 2 admin form needs. Defaults are chosen to
 * match values already in use on the public site (e.g. carousel_interval
 * mirrors home.blade.php's 6000ms auto-rotate, button_radius mirrors the
 * site's rounded-full buttons) so the very first "save" in the admin page
 * doesn't silently change anything the public site currently looks like.
 */
class ThemeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Brand
            ['key' => 'brand.site_name', 'group' => 'brand', 'value' => 'Dost TV', 'value_type' => 'text', 'label' => 'Site Adı', 'sort_order' => 1],
            ['key' => 'brand.site_short_name', 'group' => 'brand', 'value' => 'Dost TV', 'value_type' => 'text', 'label' => 'Kısa Site Adı', 'sort_order' => 2],
            ['key' => 'brand.slogan', 'group' => 'brand', 'value' => 'Her an yanınızda', 'value_type' => 'text', 'label' => 'Slogan', 'sort_order' => 3],
            ['key' => 'brand.logo', 'group' => 'brand', 'value' => null, 'value_type' => 'text', 'label' => 'Logo', 'sort_order' => 4],
            ['key' => 'brand.dark_logo', 'group' => 'brand', 'value' => null, 'value_type' => 'text', 'label' => 'Koyu Logo', 'sort_order' => 5],
            ['key' => 'brand.light_logo', 'group' => 'brand', 'value' => null, 'value_type' => 'text', 'label' => 'Açık Logo', 'sort_order' => 6],
            ['key' => 'brand.favicon', 'group' => 'brand', 'value' => null, 'value_type' => 'text', 'label' => 'Favicon', 'sort_order' => 7],

            // Colors — additive to the 9 already seeded in Aşama 1 (color.primary, color.primary_hover,
            // color.secondary, color.accent, color.background, color.surface, color.text_primary,
            // color.text_secondary, color.live)
            ['key' => 'color.surface_alt', 'group' => 'colors', 'value' => '#111827', 'value_type' => 'color', 'label' => 'Yüzey (Alternatif)', 'sort_order' => 10],
            ['key' => 'color.border', 'group' => 'colors', 'value' => '#1e293b', 'value_type' => 'color', 'label' => 'Kenarlık Rengi', 'sort_order' => 11],
            ['key' => 'color.text_muted', 'group' => 'colors', 'value' => '#64748b', 'value_type' => 'color', 'label' => 'Soluk Metin Rengi', 'sort_order' => 12],
            ['key' => 'color.success', 'group' => 'colors', 'value' => '#22c55e', 'value_type' => 'color', 'label' => 'Başarı Rengi', 'sort_order' => 13],
            ['key' => 'color.warning', 'group' => 'colors', 'value' => '#f59e0b', 'value_type' => 'color', 'label' => 'Uyarı Rengi', 'sort_order' => 14],
            ['key' => 'color.danger', 'group' => 'colors', 'value' => '#ef4444', 'value_type' => 'color', 'label' => 'Tehlike Rengi', 'sort_order' => 15],
            ['key' => 'color.header_background', 'group' => 'colors', 'value' => '#020617', 'value_type' => 'color', 'label' => 'Header Arka Planı', 'sort_order' => 16],
            ['key' => 'color.footer_background', 'group' => 'colors', 'value' => '#020617', 'value_type' => 'color', 'label' => 'Footer Arka Planı', 'sort_order' => 17],

            // Typography — additive to body_font_family, heading_font_family, base_font_size, body_line_height
            ['key' => 'typography.navigation_font_family', 'group' => 'typography', 'value' => 'Instrument Sans', 'value_type' => 'font', 'label' => 'Navigasyon Fontu', 'sort_order' => 10],
            ['key' => 'typography.button_font_family', 'group' => 'typography', 'value' => 'Instrument Sans', 'value_type' => 'font', 'label' => 'Buton Fontu', 'sort_order' => 11],
            ['key' => 'typography.h1_size', 'group' => 'typography', 'value' => '48', 'value_type' => 'number', 'label' => 'H1 Boyutu (px)', 'sort_order' => 12],
            ['key' => 'typography.h2_size', 'group' => 'typography', 'value' => '32', 'value_type' => 'number', 'label' => 'H2 Boyutu (px)', 'sort_order' => 13],
            ['key' => 'typography.h3_size', 'group' => 'typography', 'value' => '24', 'value_type' => 'number', 'label' => 'H3 Boyutu (px)', 'sort_order' => 14],
            ['key' => 'typography.h4_size', 'group' => 'typography', 'value' => '20', 'value_type' => 'number', 'label' => 'H4 Boyutu (px)', 'sort_order' => 15],
            ['key' => 'typography.navigation_font_size', 'group' => 'typography', 'value' => '14', 'value_type' => 'number', 'label' => 'Navigasyon Yazı Boyutu (px)', 'sort_order' => 16],
            ['key' => 'typography.button_font_size', 'group' => 'typography', 'value' => '14', 'value_type' => 'number', 'label' => 'Buton Yazı Boyutu (px)', 'sort_order' => 17],

            // Layout (spacing)
            ['key' => 'spacing.container_max_width', 'group' => 'spacing', 'value' => '1280', 'value_type' => 'number', 'label' => 'Konteyner Maksimum Genişliği (px)', 'sort_order' => 1],
            ['key' => 'spacing.content_max_width', 'group' => 'spacing', 'value' => '1024', 'value_type' => 'number', 'label' => 'İçerik Maksimum Genişliği (px)', 'sort_order' => 2],
            ['key' => 'spacing.header_height', 'group' => 'spacing', 'value' => '72', 'value_type' => 'number', 'label' => 'Header Yüksekliği (px)', 'sort_order' => 3],
            ['key' => 'spacing.mobile_header_height', 'group' => 'spacing', 'value' => '64', 'value_type' => 'number', 'label' => 'Mobil Header Yüksekliği (px)', 'sort_order' => 4],
            ['key' => 'spacing.section_spacing', 'group' => 'spacing', 'value' => '96', 'value_type' => 'number', 'label' => 'Bölüm Boşluğu (px)', 'sort_order' => 5],
            ['key' => 'spacing.grid_gap', 'group' => 'spacing', 'value' => '24', 'value_type' => 'number', 'label' => 'Grid Boşluğu (px)', 'sort_order' => 6],
            ['key' => 'spacing.card_gap', 'group' => 'spacing', 'value' => '24', 'value_type' => 'number', 'label' => 'Kart Boşluğu (px)', 'sort_order' => 7],

            // Buttons
            ['key' => 'buttons.button_height', 'group' => 'buttons', 'value' => '44', 'value_type' => 'number', 'label' => 'Buton Yüksekliği (px)', 'sort_order' => 1],
            ['key' => 'buttons.button_padding_x', 'group' => 'buttons', 'value' => '24', 'value_type' => 'number', 'label' => 'Buton Yatay Boşluk (px)', 'sort_order' => 2],
            ['key' => 'buttons.button_padding_y', 'group' => 'buttons', 'value' => '12', 'value_type' => 'number', 'label' => 'Buton Dikey Boşluk (px)', 'sort_order' => 3],
            ['key' => 'buttons.button_radius', 'group' => 'buttons', 'value' => '9999', 'value_type' => 'number', 'label' => 'Buton Köşe Yuvarlaklığı (px)', 'sort_order' => 4],
            ['key' => 'buttons.button_font_weight', 'group' => 'buttons', 'value' => '600', 'value_type' => 'select', 'label' => 'Buton Font Kalınlığı', 'options' => ['400' => '400 - Regular', '500' => '500 - Medium', '600' => '600 - Semibold', '700' => '700 - Bold'], 'sort_order' => 5],

            // Cards
            ['key' => 'cards.card_radius', 'group' => 'cards', 'value' => '16', 'value_type' => 'number', 'label' => 'Kart Köşe Yuvarlaklığı (px)', 'sort_order' => 1],
            ['key' => 'cards.card_border_width', 'group' => 'cards', 'value' => '1', 'value_type' => 'number', 'label' => 'Kart Kenarlık Kalınlığı (px)', 'sort_order' => 2],
            ['key' => 'cards.card_shadow', 'group' => 'cards', 'value' => 'md', 'value_type' => 'select', 'label' => 'Kart Gölgesi', 'options' => ['none' => 'Yok', 'sm' => 'Küçük', 'md' => 'Orta', 'lg' => 'Büyük'], 'sort_order' => 3],
            ['key' => 'cards.card_hover_enabled', 'group' => 'cards', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Hover Efekti Aktif', 'sort_order' => 4],
            ['key' => 'cards.card_hover_scale', 'group' => 'cards', 'value' => '1.05', 'value_type' => 'number', 'label' => 'Hover Büyütme Oranı', 'sort_order' => 5],

            // Header
            ['key' => 'header.sticky_header', 'group' => 'header', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Sabit (Sticky) Header', 'sort_order' => 1],
            ['key' => 'header.transparent_header', 'group' => 'header', 'value' => '0', 'value_type' => 'boolean', 'label' => 'Şeffaf Header', 'sort_order' => 2],
            ['key' => 'header.show_search', 'group' => 'header', 'value' => '0', 'value_type' => 'boolean', 'label' => 'Arama Göster', 'sort_order' => 3],
            ['key' => 'header.show_live_button', 'group' => 'header', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Canlı TV Butonu Göster', 'sort_order' => 4],
            ['key' => 'header.show_radio_button', 'group' => 'header', 'value' => '0', 'value_type' => 'boolean', 'label' => 'Canlı Radyo Butonu Göster', 'sort_order' => 5],
            ['key' => 'header.show_social_icons', 'group' => 'header', 'value' => '0', 'value_type' => 'boolean', 'label' => 'Sosyal Medya İkonları Göster', 'sort_order' => 6],
            ['key' => 'header.logo_width_desktop', 'group' => 'header', 'value' => '36', 'value_type' => 'number', 'label' => 'Masaüstü Logo Genişliği (px)', 'sort_order' => 7],
            ['key' => 'header.logo_width_mobile', 'group' => 'header', 'value' => '32', 'value_type' => 'number', 'label' => 'Mobil Logo Genişliği (px)', 'sort_order' => 8],
            ['key' => 'header.menu_alignment', 'group' => 'header', 'value' => 'left', 'value_type' => 'select', 'label' => 'Menü Hizalaması', 'options' => ['left' => 'Sol', 'center' => 'Orta', 'right' => 'Sağ'], 'sort_order' => 9],

            // Footer
            ['key' => 'footer.show_footer_logo', 'group' => 'footer', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Footer Logosu Göster', 'sort_order' => 1],
            ['key' => 'footer.show_footer_description', 'group' => 'footer', 'value' => '0', 'value_type' => 'boolean', 'label' => 'Footer Açıklaması Göster', 'sort_order' => 2],
            ['key' => 'footer.show_footer_social', 'group' => 'footer', 'value' => '0', 'value_type' => 'boolean', 'label' => 'Footer Sosyal İkonları Göster', 'sort_order' => 3],
            ['key' => 'footer.show_footer_contact', 'group' => 'footer', 'value' => '0', 'value_type' => 'boolean', 'label' => 'Footer İletişim Bilgisi Göster', 'sort_order' => 4],
            ['key' => 'footer.show_footer_legal', 'group' => 'footer', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Footer Yasal Bağlantıları Göster', 'sort_order' => 5],
            ['key' => 'footer.footer_columns', 'group' => 'footer', 'value' => '4', 'value_type' => 'number', 'label' => 'Footer Kolon Sayısı', 'sort_order' => 6],
            ['key' => 'footer.footer_spacing', 'group' => 'footer', 'value' => '40', 'value_type' => 'number', 'label' => 'Footer Boşluğu (px)', 'sort_order' => 7],

            // Animations
            ['key' => 'animations.animations_enabled', 'group' => 'animations', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Animasyonlar Aktif', 'sort_order' => 1],
            ['key' => 'animations.transition_duration', 'group' => 'animations', 'value' => '300', 'value_type' => 'number', 'label' => 'Geçiş Süresi (ms)', 'sort_order' => 2],
            ['key' => 'animations.hover_scale', 'group' => 'animations', 'value' => '1.05', 'value_type' => 'number', 'label' => 'Hover Büyütme Oranı', 'sort_order' => 3],
            ['key' => 'animations.carousel_autoplay', 'group' => 'animations', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Carousel Otomatik Oynatma', 'sort_order' => 4],
            ['key' => 'animations.carousel_interval', 'group' => 'animations', 'value' => '6000', 'value_type' => 'number', 'label' => 'Carousel Aralığı (ms)', 'sort_order' => 5],

            // Accessibility
            ['key' => 'accessibility.reduced_motion_support', 'group' => 'accessibility', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Azaltılmış Hareket Desteği', 'sort_order' => 1],
            ['key' => 'accessibility.minimum_touch_target', 'group' => 'accessibility', 'value' => '44', 'value_type' => 'number', 'label' => 'Minimum Dokunma Alanı (px)', 'sort_order' => 2],
            ['key' => 'accessibility.focus_ring_width', 'group' => 'accessibility', 'value' => '2', 'value_type' => 'number', 'label' => 'Focus Halkası Kalınlığı (px)', 'sort_order' => 3],
            ['key' => 'accessibility.high_contrast_warning', 'group' => 'accessibility', 'value' => '1', 'value_type' => 'boolean', 'label' => 'Düşük Kontrast Uyarısı', 'sort_order' => 4],

            // Colors already present from Aşama 1 (kept here only as documentation reference, NOT re-inserted
            // with different sort_order — updateOrCreate below is keyed by `key`, so re-listing them would be
            // harmless but is intentionally omitted to keep this file's diff focused on what's new).
        ];

        foreach ($settings as $setting) {
            ThemeSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting + ['is_public' => true]
            );
        }
    }
}
