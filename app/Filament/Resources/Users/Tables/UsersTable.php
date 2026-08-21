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

                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state) => User::formatPhoneForDisplay($state) ?? '-')
                    ->icon('heroicon-m-phone')
                    ->color(fn (?string $state) => $state ? 'gray' : 'slate-500'),

                TextColumn::make('roleModel.name')
                    ->label('Rol')
                    ->default(fn (User $record) => User::ROLES[$record->role] ?? $record->role)
                    ->badge()
                    ->color(fn (User $record): string => match ($record->baseRole()) {
                        'super_admin' => 'rose',
                        'administrator' => 'amber',
                        'editor' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('is_active')
                    ->label('Durum')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Aktif' : 'Pasif')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('invitation_status')
                    ->label('Davet')
                    ->formatStateUsing(fn (string $state, User $record) => $record->invitation_status_label)
                    ->badge()
                    ->color(fn (User $record) => $record->invitation_status_color),

                TextColumn::make('last_login_at')
                    ->label('Son Giriş')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y H:i') : 'Henüz giriş yapmadı')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label('Rol')
                    ->relationship('roleModel', 'name'),

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
                        if ($record->isSuperAdmin() && ! $currentUser->isSuperAdmin()) {
                            return false;
                        }

                        return true;
                    }),

                Action::make('resendInvitation')
                    ->label('Daveti Tekrar Gönder')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Davet Bağlantısını Yeniden Gönder')
                    ->modalDescription(fn (User $record) => "{$record->name} kullanıcısına 72 saat geçerli yeni bir davet bağlantısı gönderilecek. Eski bağlantı geçersiz kılınacaktır.")
                    ->modalSubmitActionLabel('Evet, Yeniden Gönder')
                    ->visible(function (User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser) {
                            return false;
                        }

                        if ($record->isSuperAdmin() && ! $currentUser->isSuperAdmin()) {
                            return false;
                        }

                        return in_array($record->invitation_status, ['pending', 'expired', 'cancelled'], true);
                    })
                    ->action(function (User $record) {
                        $service = app(\App\Services\Auth\UserInvitationService::class);
                        $res = $service->resendInvitation($record, auth()->user());

                        if ($res['mail_sent']) {
                            Notification::make()
                                ->title('Davet e-postası başarıyla yeniden gönderildi.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Davet yenilendi ancak e-posta gönderilemedi.')
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('cancelInvitation')
                    ->label('Daveti İptal Et')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Davet Bağlantısını İptal Et')
                    ->modalDescription(fn (User $record) => "{$record->name} kullanıcısının bekleyen davet bağlantısı iptal edilecek.")
                    ->modalSubmitActionLabel('Evet, İptal Et')
                    ->visible(function (User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser) {
                            return false;
                        }

                        if ($record->isSuperAdmin() && ! $currentUser->isSuperAdmin()) {
                            return false;
                        }

                        return $record->invitation_status === 'pending';
                    })
                    ->action(function (User $record) {
                        $service = app(\App\Services\Auth\UserInvitationService::class);
                        $service->cancelInvitation($record, auth()->user());

                        Notification::make()
                            ->title('Davet bağlantısı iptal edildi.')
                            ->success()
                            ->send();
                    }),

                Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Pasife Al' : 'Aktifleştir')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => $record->is_active ? 'Kullanıcıyı Pasife Al' : 'Kullanıcıyı Aktifleştir')
                    ->modalDescription(fn (User $record) => $record->is_active
                        ? 'Bu kullanıcıyı pasife almak istediğinize emin misiniz? Kullanıcının açık tüm oturumları anında sonlandırılacaktır.'
                        : 'Bu kullanıcıyı yeniden aktifleştirmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel(fn (User $record) => $record->is_active ? 'Evet, Pasife Al' : 'Evet, Aktifleştir')
                    ->visible(function (User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser) {
                            return false;
                        }

                        // Administrator cannot deactivate Super Admin
                        if ($record->isSuperAdmin() && ! $currentUser->isSuperAdmin()) {
                            return false;
                        }

                        // Last active super admin cannot be deactivated
                        if ($record->isSuperAdmin() && $record->is_active && User::isLastActiveSuperAdmin($record)) {
                            return false;
                        }

                        return true;
                    })
                    ->action(function (User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser) {
                            return;
                        }

                        // Administrator cannot deactivate Super Admin
                        if ($record->isSuperAdmin() && ! $currentUser->isSuperAdmin()) {
                            Notification::make()
                                ->title('Süper Admin hesabını pasife alma yetkiniz yoktur.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Last active super_admin check
                        if ($record->isSuperAdmin() && $record->is_active && User::isLastActiveSuperAdmin($record)) {
                            Notification::make()
                                ->title('Sistemdeki son aktif Süper Admin pasife alınamaz.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $newStatus = ! $record->is_active;
                        $record->update(['is_active' => $newStatus]);

                        if (! $newStatus) {
                            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $record->id)->delete();
                        }

                        $userName = $currentUser->name ?? 'Admin';
                        $action = $newStatus ? 'activated' : 'deactivated';
                        $msg = $newStatus
                            ? "{$userName}, {$record->name} kullanıcısını aktifleştirdi."
                            : "{$userName}, {$record->name} kullanıcısını pasife aldı.";

                        \App\Services\Audit\AuditLogger::log(
                            action: $action,
                            message: $msg,
                            subject: $record,
                            subjectLabel: $record->name,
                        );

                        Notification::make()
                            ->title($record->is_active ? 'Kullanıcı aktifleştirildi.' : 'Kullanıcı pasife alındı ve oturumu kapatıldı.')
                            ->success()
                            ->send();
                    }),

                Action::make('forceDelete')
                    ->label('Kalıcı Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Kullanıcıyı Kalıcı Olarak Sil')
                    ->modalDescription('Bu kullanıcı kalıcı olarak silinecek. Bu işlem geri alınamaz.')
                    ->modalSubmitActionLabel('Evet, Kalıcı Olarak Sil')
                    ->visible(function (User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser || ! $currentUser->isSuperAdmin()) {
                            return false;
                        }

                        // Cannot delete last active super admin
                        if ($record->isSuperAdmin() && User::isLastActiveSuperAdmin($record)) {
                            return false;
                        }

                        return true;
                    })
                    ->action(function (User $record) {
                        $currentUser = auth()->user();
                        if (! $currentUser || ! $currentUser->isSuperAdmin()) {
                            Notification::make()
                                ->title('Yalnızca Süper Admin kullanıcıları kalıcı olarak silebilir.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($record->isSuperAdmin() && User::isLastActiveSuperAdmin($record)) {
                            Notification::make()
                                ->title('Sistemdeki son aktif Süper Admin silinemez.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $userName = $currentUser->name ?? 'Admin';
                        $targetName = $record->name;
                        \App\Services\Audit\AuditLogger::log(
                            action: 'deleted',
                            message: "{$userName}, {$targetName} kullanıcısını kalıcı olarak sildi.",
                            subject: $record,
                            subjectLabel: $targetName,
                            isDestructive: true,
                        );

                        $record->forceDelete();

                        Notification::make()
                            ->title('Kullanıcı kalıcı olarak silindi.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
