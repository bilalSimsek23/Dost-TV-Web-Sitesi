<?php

namespace App\Filament\Resources\LiveStreams\Schemas;

use App\Models\LiveStream;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LiveStreamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Yayın Temel Bilgileri')
                    ->schema([
                        TextInput::make('title')
                            ->label('Yayın Adı / Başlığı')
                            ->placeholder('Örn: Dost TV Canlı Yayın Ana Akışı')
                            ->required(),

                        Select::make('stream_type')
                            ->label('Platform / Yayın Türü')
                            ->options(LiveStream::STREAM_TYPES)
                            ->default('hls')
                            ->required()
                            ->live(),

                        TextInput::make('stream_url')
                            ->label('Canlı Akış / Video / İzleme URL\'si')
                            ->placeholder('Örn: https://dost.stream.emsal.im/tv/live.m3u8 veya YouTube linki')
                            ->helperText('HLS (.m3u8), YouTube URL veya canlı yayın akış adresi'),

                        Textarea::make('embed_code')
                            ->label('iFrame / Embed Kodu')
                            ->visible(fn ($get) => in_array($get('stream_type'), ['iframe', 'youtube']))
                            ->placeholder('<iframe src="..."></iframe>'),

                        TextInput::make('backup_url')
                            ->label('Yedek Yayın URL\'si (İsteğe Bağlı)')
                            ->placeholder('Ana yayın kesildiğinde devreye girecek yedek URL'),

                        FileUpload::make('poster_image')
                            ->label('Kapak / Poster Görseli')
                            ->image()
                            ->directory('live-streams'),
                    ])->columns(2),

                Section::make('Yayın Durumu ve Ana Yayın Tercihi')
                    ->schema([
                        Toggle::make('is_primary')
                            ->label('Ana Varsayılan Yayın')
                            ->helperText('Açık yapıldığında sitedeki varsayılan canlı TV yayını bu akışa güncellenir.')
                            ->default(false),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Toggle::make('is_currently_live')
                            ->label('Şu Anda Canlı Yayında mı?')
                            ->default(true),

                        DateTimePicker::make('start_time')
                            ->label('Yayın Başlangıç Tarihi / Saati'),

                        DateTimePicker::make('end_time')
                            ->label('Yayın Bitiş Tarihi / Saati'),
                    ])->columns(2),

                Section::make('Buton ve Görünüm Ayarları')
                    ->schema([
                        TextInput::make('button_text')
                            ->label('Buton Metni')
                            ->default('Canlı İzle'),

                        Toggle::make('show_watch_button')
                            ->label('"Canlı İzle" Butonu Görünsün mü?')
                            ->default(true),

                        Toggle::make('open_in_new_tab')
                            ->label('Yeni Sekmede Aç')
                            ->default(false),

                        TextInput::make('sort_order')
                            ->label('Sıralama Önceliği')
                            ->numeric()
                            ->default(1),

                        Textarea::make('notes')
                            ->label('Yönetici Notu')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
