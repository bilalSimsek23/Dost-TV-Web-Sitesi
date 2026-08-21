<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Rol ara...')
            ->columns([
                TextColumn::make('name')
                    ->label('Rol Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('base_role')
                    ->label('Yetki Seviyesi')
                    ->formatStateUsing(fn (string $state) => Role::BASE_ROLES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'rose',
                        'administrator' => 'amber',
                        'editor' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Kullanıcı Sayısı')
                    ->counts('users')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('is_system')
                    ->label('Tür')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Sistem Rolü' : 'Özel Rol')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'primary' : 'gray'),

                TextColumn::make('is_active')
                    ->label('Durum')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Aktif' : 'Pasif')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('base_role')
                    ->label('Yetki Seviyesi')
                    ->options(Role::BASE_ROLES),

                SelectFilter::make('is_active')
                    ->label('Durum')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Pasif',
                    ]),
            ])
            ->actions([
                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn (Role $record) => ! $record->isSystem())
                    ->before(function (DeleteAction $action, Role $record) {
                        if ($record->isSystem()) {
                            Notification::make()
                                ->title('Sistem rolleri silinemez.')
                                ->danger()
                                ->send();
                            $action->cancel();
                            return;
                        }

                        if ($record->users()->exists()) {
                            Notification::make()
                                ->title('Bu role atanmış kullanıcılar olduğu için silinemez.')
                                ->danger()
                                ->send();
                            $action->cancel();
                            return;
                        }

                        $userName = auth()->user()?->name ?? 'Admin';
                        \App\Services\Audit\AuditLogger::log(
                            action: 'deleted',
                            message: "{$userName}, {$record->name} rolünü sildi.",
                            subject: $record,
                            subjectLabel: $record->name,
                            isDestructive: true,
                        );
                    }),
            ]);
    }
}
