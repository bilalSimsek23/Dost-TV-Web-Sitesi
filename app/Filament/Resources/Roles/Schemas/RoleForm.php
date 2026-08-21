<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rol Bilgileri')
                    ->description('Kullanıcılara atanacak yetki grubu bilgilerini tanımlayın.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Rol Adı')
                            ->placeholder('Örn: Yayın Editörü, İçerik Sorumlusu')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (?Role $record) => $record?->isSystem() ?? false)
                            ->dehydrated(),

                        Select::make('base_role')
                            ->label('Yetki Seviyesi')
                            ->options(function () {
                                $currentUser = auth()->user();
                                if ($currentUser && $currentUser->isSuperAdmin()) {
                                    return Role::BASE_ROLES;
                                }

                                return [
                                    'administrator' => 'Yönetici',
                                    'editor' => 'Editör',
                                ];
                            })
                            ->default('editor')
                            ->required()
                            ->helperText('Rolün sistemdeki temel izin sınırlarını belirler.')
                            ->disabled(fn (?Role $record) => $record?->isSystem() ?? false)
                            ->dehydrated(),

                        Textarea::make('description')
                            ->label('Açıklama')
                            ->placeholder('Bu rolün görev ve sorumluluk alanı...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Rol Aktif')
                            ->default(true)
                            ->disabled(fn (?Role $record) => $record?->isSystem() ?? false)
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }
}
