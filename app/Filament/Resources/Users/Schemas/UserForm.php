<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('UserFormTabs')
                    ->tabs([
                        Tab::make('Genel Bilgiler')
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label('Profil Fotoğrafı')
                                    ->avatar()
                                    ->disk('public')
                                    ->directory('avatars')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->columnSpanFull(),

                                TextInput::make('name')
                                    ->label('Ad Soyad')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label('E-posta')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->placeholder('05xx xxx xx xx')
                                    ->maxLength(50),
                            ])->columns(2),

                        Tab::make('Hesap ve Rol')
                            ->schema([
                                Select::make('role')
                                    ->label('Rol')
                                    ->options(User::ROLES)
                                    ->default('editor')
                                    ->required()
                                    ->disabled(function (?User $record) {
                                        $currentUser = auth()->user();
                                        if (! $currentUser || ! $record) {
                                            return false;
                                        }

                                        // Administrator cannot edit role of a Super Admin
                                        if ($record->hasRole('super_admin') && ! $currentUser->hasRole('super_admin')) {
                                            return true;
                                        }

                                        // Cannot demote last active super admin
                                        if ($record->hasRole('super_admin')) {
                                            $activeSuperAdminsCount = User::where('role', 'super_admin')
                                                ->where('is_active', true)
                                                ->whereNull('deleted_at')
                                                ->count();

                                            return $activeSuperAdminsCount <= 1;
                                        }

                                        return false;
                                    }),

                                Toggle::make('is_active')
                                    ->label('Hesap Aktif')
                                    ->default(true)
                                    ->disabled(function (?User $record) {
                                        $currentUser = auth()->user();
                                        if (! $currentUser || ! $record) {
                                            return false;
                                        }

                                        // Cannot deactivate self
                                        if ($record->id === $currentUser->id) {
                                            return true;
                                        }

                                        // Administrator cannot deactivate Super Admin
                                        if ($record->hasRole('super_admin') && ! $currentUser->hasRole('super_admin')) {
                                            return true;
                                        }

                                        // Cannot deactivate last active super admin
                                        if ($record->hasRole('super_admin') && $record->is_active) {
                                            $activeSuperAdminsCount = User::where('role', 'super_admin')
                                                ->where('is_active', true)
                                                ->whereNull('deleted_at')
                                                ->count();

                                            return $activeSuperAdminsCount <= 1;
                                        }

                                        return false;
                                    }),
                            ])->columns(2),

                        Tab::make('Güvenlik')
                            ->schema([
                                TextInput::make('password')
                                    ->label('Şifre')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->confirmed()
                                    ->minLength(8)
                                    ->visible(fn (string $operation): bool => $operation === 'create'),

                                TextInput::make('password_confirmation')
                                    ->label('Şifre Tekrarı')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->minLength(8)
                                    ->visible(fn (string $operation): bool => $operation === 'create'),

                                TextInput::make('new_password')
                                    ->label('Yeni Şifre')
                                    ->password()
                                    ->revealable()
                                    ->confirmed('new_password_confirmation')
                                    ->minLength(8)
                                    ->helperText('Boş bırakılırsa mevcut şifre değişmez.')
                                    ->dehydrated(false)
                                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                                TextInput::make('new_password_confirmation')
                                    ->label('Yeni Şifre Tekrarı')
                                    ->password()
                                    ->revealable()
                                    ->minLength(8)
                                    ->dehydrated(false)
                                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                                Placeholder::make('last_login_at')
                                    ->label('Son Giriş')
                                    ->content(fn (?User $record) => $record && $record->last_login_at ? $record->last_login_at->format('d.m.Y H:i') : 'Henüz giriş yapmadı')
                                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                                Placeholder::make('last_login_ip')
                                    ->label('Son Giriş IP')
                                    ->content(fn (?User $record) => $record && $record->last_login_ip ? $record->last_login_ip : '-')
                                    ->visible(fn (string $operation): bool => $operation === 'edit'),
                            ])->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
