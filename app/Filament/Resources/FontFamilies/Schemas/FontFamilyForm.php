<?php

namespace App\Filament\Resources\FontFamilies\Schemas;

use App\Models\FontFamily;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FontFamilyForm
{
    private const WEIGHT_OPTIONS = [
        '100' => '100 - Thin',
        '200' => '200 - Extra Light',
        '300' => '300 - Light',
        '400' => '400 - Regular',
        '500' => '500 - Medium',
        '600' => '600 - Semibold',
        '700' => '700 - Bold',
        '800' => '800 - Extra Bold',
        '900' => '900 - Black',
    ];

    private const STYLE_OPTIONS = [
        'normal' => 'Normal',
        'italic' => 'İtalik',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Font Adı')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Boş bırakılırsa font adından otomatik üretilir.'),

                Select::make('source_type')
                    ->label('Kaynak Türü')
                    ->options(FontFamily::SOURCE_TYPES)
                    ->default('system')
                    ->required()
                    ->live()
                    ->helperText('"Özel Yükleme" seçeneği yerel (woff/woff2) dosya yükler.'),

                TextInput::make('font_url')
                    ->label('Font URL (Google Fonts vb.)')
                    ->url()
                    ->visible(fn ($get) => $get('source_type') === 'google')
                    ->required(fn ($get) => $get('source_type') === 'google')
                    ->helperText('Google Fonts CSS bağlantısını (https://fonts.googleapis.com/...) girin.'),

                FileUpload::make('local_path')
                    ->label('Font Dosyası')
                    ->disk('public')
                    ->directory('fonts')
                    ->visibility('public')
                    ->acceptedFileTypes(['font/woff', 'font/woff2', 'application/font-woff', 'application/font-woff2', 'application/octet-stream'])
                    ->rules(['extensions:woff,woff2'])
                    ->maxSize(5120)
                    ->visible(fn ($get) => $get('source_type') === 'custom')
                    ->required(fn ($get) => $get('source_type') === 'custom')
                    ->helperText('Yalnızca .woff / .woff2 dosyaları kabul edilir. Maksimum 5 MB.'),

                CheckboxList::make('weights')
                    ->label('Ağırlıklar')
                    ->options(self::WEIGHT_OPTIONS)
                    ->columns(3),

                CheckboxList::make('styles')
                    ->label('Stiller')
                    ->options(self::STYLE_OPTIONS)
                    ->columns(2),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Pasif fontlar Tema Ayarları\'ndaki font seçim listelerinde görünmez.'),

                Toggle::make('is_default')
                    ->label('Varsayılan')
                    ->helperText('Aynı anda yalnızca bir font varsayılan olabilir; bunu seçmek diğerlerini otomatik pasifleştirir (varsayılan olmaktan çıkarır).'),
            ]);
    }
}
