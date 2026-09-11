<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Models\ContactMessage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gönderen & İletişim Bilgileri')
                    ->schema([
                        Group::make([
                            TextInput::make('name')
                                ->label('Ad Soyad')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('email')
                                ->label('E-Posta')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('phone')
                                ->label('Telefon')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Girilmedi'),

                            Select::make('status')
                                ->label('Mesaj Durumu')
                                ->options(ContactMessage::STATUSES)
                                ->required()
                                ->native(false),
                        ])->columns(2),
                    ]),

                Section::make('Mesaj Detayı')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Konu')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Textarea::make('message')
                            ->label('Mesaj İçeriği')
                            ->rows(8)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        DateTimePicker::make('created_at')
                            ->label('Gönderilme Tarihi')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
