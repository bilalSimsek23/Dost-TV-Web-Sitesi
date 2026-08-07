<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Kullanıcı ara...')
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(function (User $record) {
                        $initial = mb_strtoupper(mb_substr($record->name, 0, 1));
                        return 'data:image/svg+xml;utf8,' . rawurlencode("
                            <svg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'>
                                <circle cx='20' cy='20' r='20' fill='#334155'/>
                                <text x='50%' y='50%' dominant-baseline='central' text-anchor='middle' fill='#ffffff' font-size='16' font-family='sans-serif' font-weight='bold'>{$initial}</text>
                            </svg>
                        ");
                    }),

                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                TextColumn::make('role')
                    ->label('Rol')
                    ->formatStateUsing(fn (string $state) => User::ROLES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'rose',
                        'administrator' => 'amber',
                        'designer' => 'sky',
                        'editor' => 'info',
                        'content_manager' => 'emerald',
                        default => 'gray',
                    }),

                TextColumn::make('is_active')
                    ->label('Durum')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Aktif' : 'Pasif')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('last_login_at')
                    ->label('Son Giriş')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y H:i') : 'Henüz giriş yapmadı')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options(User::ROLES),

                SelectFilter::make('is_active')
                    ->label('Durum')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Pasif',
                    ]),

                TrashedFilter::make()
                    ->label('Arşivlenmiş Kullanıcılar')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false),
            ])
            ->actions([
                EditAction::make()
                    ->visible(function (User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser) {
                            return false;
                        }
                        // Administrator cannot edit Super Admin
                        if ($record->hasRole('super_admin') && ! $currentUser->hasRole('super_admin')) {
                            return false;
                        }
                        return true;
                    }),

                Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Pasife Al' : 'Aktif Yap')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser) {
                            return;
                        }

                        // 1. Cannot deactivate self
                        if ($record->id === $currentUser->id && $record->is_active) {
                            Notification::make()
                                ->title('Kendi hesabınızı pasife alamazsınız.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 2. Administrator cannot deactivate Super Admin
                        if ($record->hasRole('super_admin') && ! $currentUser->hasRole('super_admin')) {
                            Notification::make()
                                ->title('Süper Admin hesabını pasife alma yetkiniz yoktur.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 3. Last active super_admin check
                        if ($record->hasRole('super_admin') && $record->is_active) {
                            $activeSuperAdminsCount = User::where('role', 'super_admin')
                                ->where('is_active', true)
                                ->whereNull('deleted_at')
                                ->count();

                            if ($activeSuperAdminsCount <= 1) {
                                Notification::make()
                                    ->title('Sistemdeki son aktif Süper Admin pasife alınamaz.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        $record->update(['is_active' => ! $record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? 'Kullanıcı aktifleştirildi.' : 'Kullanıcı pasife alındı.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make('archive')
                    ->label('Arşivle')
                    ->modalHeading('Kullanıcıyı Arşivle')
                    ->modalDescription('Bu kullanıcıyı arşivlemek istediğinize emin misiniz? Arşivlenen kullanıcı sisteme giriş yapamaz.')
                    ->before(function (DeleteAction $action, User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser) {
                            $action->cancel();
                            return;
                        }

                        // Cannot archive self
                        if ($record->id === $currentUser->id) {
                            Notification::make()
                                ->title('Kendi hesabınızı arşivleyemezsiniz.')
                                ->danger()
                                ->send();
                            $action->cancel();
                            return;
                        }

                        // Administrator cannot archive Super Admin
                        if ($record->hasRole('super_admin') && ! $currentUser->hasRole('super_admin')) {
                            Notification::make()
                                ->title('Süper Admin hesabını arşivleme yetkiniz yoktur.')
                                ->danger()
                                ->send();
                            $action->cancel();
                            return;
                        }

                        // Last active super_admin check
                        if ($record->hasRole('super_admin')) {
                            $activeSuperAdminsCount = User::where('role', 'super_admin')
                                ->where('is_active', true)
                                ->whereNull('deleted_at')
                                ->count();

                            if ($activeSuperAdminsCount <= 1) {
                                Notification::make()
                                    ->title('Sistemdeki son aktif Süper Admin arşivlenemez.')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                                return;
                            }
                        }
                    }),

                RestoreAction::make()
                    ->label('Arşivden Çıkar')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin') ?? false),
            ]);
    }
}
