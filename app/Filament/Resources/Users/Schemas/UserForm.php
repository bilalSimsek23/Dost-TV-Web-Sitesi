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
                                    ->prefix('+90')
                                    ->placeholder('5XX XXX XX XX')
                                    ->mask('599 999 99 99')
                                    ->helperText('Türkiye GSM numarası (5 ile başlayan 10 hane)')
                                    ->formatStateUsing(function (?string $state) {
                                        if (blank($state)) {
                                            return null;
                                        }
                                        $digits = preg_replace('/\D/', '', $state);
                                        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
                                            return substr($digits, 2);
                                        }
                                        return $digits;
                                    })
                                    ->rule(function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            if (blank($value)) {
                                                return;
                                            }
                                            $digits = preg_replace('/\D/', '', (string) $value);
                                            if (str_starts_with($digits, '90') && strlen($digits) === 12) {
                                                $digits = substr($digits, 2);
                                            }
                                            if (strlen($digits) !== 10 || ! str_starts_with($digits, '5')) {
                                                $fail('Telefon numarası 5 ile başlayan 10 haneli geçerli bir cep telefonu olmalıdır (Örn: 5XX XXX XX XX).');
                                            }
                                        };
                                    })
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Tab::make('Hesap ve Rol')
                            ->schema([
                                Select::make('role_id')
                                    ->label('Rol')
                                    ->options(function () {
                                        $currentUser = auth()->user();
                                        $query = \App\Models\Role::query()->where('is_active', true);
                                        if (! $currentUser || ! $currentUser->isSuperAdmin()) {
                                            $query->where('base_role', '!=', 'super_admin');
                                        }

                                        return $query->pluck('name', 'id');
                                    })
                                    ->default(fn () => \App\Models\Role::where('slug', 'editor')->value('id'))
                                    ->required()
                                    ->disabled(function (?User $record) {
                                        $currentUser = auth()->user();
                                        if (! $currentUser || ! $record) {
                                            return false;
                                        }

                                        // Administrator cannot edit role of a Super Admin
                                        if ($record->isSuperAdmin() && ! $currentUser->isSuperAdmin()) {
                                            return true;
                                        }

                                        // Cannot demote last active super admin
                                        if ($record->isSuperAdmin()) {
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
                                Placeholder::make('invitation_info')
                                    ->label('Şifre Belirleme')
                                    ->content('Kullanıcı oluşturulduğunda e-posta adresine 72 saat geçerli güvenli davet ve şifre belirleme bağlantısı gönderilecektir.')
                                    ->visible(fn (string $operation): bool => $operation === 'create')
                                    ->columnSpanFull(),

                                TextInput::make('new_password')
                                    ->label('Yeni Şifre')
                                    ->password()
                                    ->revealable()
                                    ->confirmed('new_password_confirmation')
                                    ->minLength(5)
                                    ->helperText('Boş bırakılırsa mevcut şifre değişmez.')
                                    ->dehydrated(false)
                                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                                TextInput::make('new_password_confirmation')
                                    ->label('Yeni Şifre Tekrarı')
                                    ->password()
                                    ->revealable()
                                    ->minLength(5)
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
