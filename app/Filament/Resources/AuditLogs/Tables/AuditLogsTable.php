<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('İşlem geçmişinde ara...')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('user_name_snapshot')
                    ->label('Kullanıcı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user'),

                TextColumn::make('action')
                    ->label('İşlem')
                    ->formatStateUsing(fn (string $state, AuditLog $record) => $record->action_label)
                    ->badge()
                    ->color(fn (AuditLog $record): string => $record->action_color),

                TextColumn::make('subject_label')
                    ->label('İçerik')
                    ->searchable()
                    ->default('-')
                    ->color('gray'),

                TextColumn::make('message')
                    ->label('Açıklama')
                    ->searchable()
                    ->wrap()
                    ->icon(fn (AuditLog $record) => $record->is_destructive ? 'heroicon-m-exclamation-triangle' : null)
                    ->color(fn (AuditLog $record) => $record->is_destructive ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Kullanıcı')
                    ->relationship('user', 'name'),

                SelectFilter::make('action')
                    ->label('İşlem Türü')
                    ->options(AuditLog::ACTIONS),

                SelectFilter::make('subject_type')
                    ->label('İçerik Türü')
                    ->options([
                        'App\Models\Program' => 'Program',
                        'App\Models\Episode' => 'Bölüm',
                        'App\Models\ProgramSeason' => 'Sezon',
                        'App\Models\ProgramSeries' => 'Dizi / Seri',
                        'App\Models\ScheduleTemplate' => 'Yayın Akışı Şablonu',
                        'App\Models\Schedule' => 'Yayın Akışı',
                        'App\Models\User' => 'Kullanıcı',
                        'App\Models\Role' => 'Rol',
                        'App\Models\Category' => 'Kategori',
                        'App\Models\Banner' => 'Banner',
                        'App\Models\Page' => 'Sayfa',
                        'App\Models\Announcement' => 'Duyuru',
                    ]),

                Filter::make('created_at')
                    ->label('Tarih Aralığı')
                    ->form([
                        DatePicker::make('from')->label('Başlangıç Tarihi'),
                        DatePicker::make('until')->label('Bitiş Tarihi'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
