<?php

namespace App\Filament\Resources\Episodes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EpisodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('program_id')
                    ->label('Program')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->label('Başlık')
                    ->required(),
                Textarea::make('description')
                    ->label('Açıklama')
                    ->columnSpanFull(),
                FileUpload::make('thumbnail')
                    ->label('Küçük Resim')
                    ->image()
                    ->disk('public')
                    ->directory('episodes'),
                Select::make('video_source')
                    ->label('Video Kaynağı')
                    ->options([
                        'youtube' => 'YouTube Linki',
                        'upload' => 'Video Yükle',
                    ])
                    ->default('youtube')
                    ->live()
                    ->required(),
                TextInput::make('youtube_url')
                    ->label('YouTube Linki')
                    ->url()
                    ->visible(fn ($get) => $get('video_source') === 'youtube'),
                FileUpload::make('video_path')
                    ->label('Video Dosyası')
                    ->disk('public')
                    ->directory('episode-videos')
                    ->acceptedFileTypes(['video/mp4', 'video/webm'])
                    ->visible(fn ($get) => $get('video_source') === 'upload'),
                DatePicker::make('aired_at')
                    ->label('Yayın Tarihi'),
                TextInput::make('sort_order')
                    ->label('Sıralama')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
