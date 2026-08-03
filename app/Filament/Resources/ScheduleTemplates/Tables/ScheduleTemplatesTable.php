<?php

namespace App\Filament\Resources\ScheduleTemplates\Tables;

use App\Models\ScheduleTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ScheduleTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Dönem Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('valid_from')
                    ->label('Başlangıç Tarihi')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('valid_until')
                    ->label('Bitiş Tarihi')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ScheduleTemplate::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        'archived' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('is_active')
                    ->label('Varsayılan Dönem')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Varsayılan' : '-')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('items_count')
                    ->label('Yayın Sayısı')
                    ->counts('items')
                    ->alignEnd(),
            ])
            ->defaultSort('valid_from', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ScheduleTemplate::STATUSES),

                TernaryFilter::make('is_active')
                    ->label('Varsayılan Dönem'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('open_schedule')
                    ->label('Yayın Akışını Aç')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('amber')
                    ->url(fn (ScheduleTemplate $record) => route('filament.admin.pages.schedule-calendar', ['template' => $record->id])),

                Action::make('copy_template')
                    ->label('Dönemi Kopyala')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->color('gray')
                    ->modalHeading('Dönemi Kopyala')
                    ->schema([
                        TextInput::make('new_name')
                            ->label('Yeni Dönem Adı')
                            ->default(fn (ScheduleTemplate $record) => $record->name . ' (Kopya)')
                            ->required(),

                        DatePicker::make('valid_from')
                            ->label('Yeni Başlangıç Tarihi')
                            ->default(fn (ScheduleTemplate $record) => $record->valid_from),

                        DatePicker::make('valid_until')
                            ->label('Yeni Bitiş Tarihi')
                            ->default(fn (ScheduleTemplate $record) => $record->valid_until),

                        Toggle::make('copy_items')
                            ->label('Yayınları da Kopyala')
                            ->default(true),
                    ])
                    ->action(function (ScheduleTemplate $record, array $data) {
                        $newTemplate = ScheduleTemplate::create([
                            'name' => $data['new_name'],
                            'slug' => ScheduleTemplate::generateUniqueSlug($data['new_name']),
                            'description' => $record->description,
                            'valid_from' => $data['valid_from'] ?? null,
                            'valid_until' => $data['valid_until'] ?? null,
                            'status' => 'draft',
                            'priority' => $record->priority,
                            'version' => 1,
                            'is_active' => false,
                        ]);

                        if (! empty($data['copy_items'])) {
                            foreach ($record->items as $item) {
                                $newItem = $item->replicate(['schedule_template_id']);
                                $newItem->schedule_template_id = $newTemplate->id;
                                $newItem->save();
                            }
                        }

                        Notification::make()
                            ->title('Yayın dönemi başarıyla kopyalandı.')
                            ->success()
                            ->send();
                    }),

                Action::make('publish')
                    ->label('Yayına Al')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ScheduleTemplate $record) => $record->status !== 'published')
                    ->action(function (ScheduleTemplate $record) {
                        if ($record->items()->count() === 0) {
                            Notification::make()
                                ->title('Uyarı')
                                ->body('Bu dönemde henüz hiçbir yayın bulunmamaktadır. Lütfen önce yayın ekleyiniz.')
                                ->warning()
                                ->send();

                            return;
                        }

                        ScheduleTemplate::where('id', '!=', $record->id)->update(['is_active' => false]);

                        $record->update([
                            'status' => 'published',
                            'is_active' => true,
                        ]);

                        Notification::make()
                            ->title('Yayın dönemi başarıyla yayına alındı.')
                            ->success()
                            ->send();
                    }),

                Action::make('archive')
                    ->label('Arşivle')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ScheduleTemplate $record) => $record->status !== 'archived')
                    ->action(function (ScheduleTemplate $record) {
                        $record->update([
                            'status' => 'archived',
                            'is_active' => false,
                        ]);

                        Notification::make()
                            ->title('Yayın dönemi arşivlendi.')
                            ->info()
                            ->send();
                    }),

                DeleteAction::make()
                    ->label('Sil')
                    ->visible(fn (ScheduleTemplate $record) => $record->status === 'draft' && $record->items()->count() === 0),
            ]);
    }
}
