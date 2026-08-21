<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProgram extends EditRecord
{
    protected static string $resource = ProgramResource::class;

    public function hasCombinedRelationManagerTabsWithForm(): bool
    {
        return true;
    }

    protected function afterSave(): void
    {
        $userName = auth()->user()?->name ?? 'Kullanıcı';
        $record = $this->record;

        // Check if show_on_public changed
        if ($record->wasChanged('show_on_public')) {
            $action = $record->show_on_public ? 'published' : 'unpublished';
            $msg = $record->show_on_public
                ? "{$userName}, {$record->name} programını yayına aldı."
                : "{$userName}, {$record->name} programını yayından kaldırdı.";

            AuditLogger::log(
                action: $action,
                message: $msg,
                subject: $record,
                subjectLabel: $record->name,
            );

            return;
        }

        // Check if status changed
        if ($record->wasChanged('status')) {
            AuditLogger::log(
                action: 'updated',
                message: "{$userName}, {$record->name} programının yayın durumunu değiştirdi.",
                subject: $record,
                subjectLabel: $record->name,
            );

            return;
        }

        AuditLogger::log(
            action: 'updated',
            message: "{$userName}, {$record->name} programını düzenledi.",
            subject: $record,
            subjectLabel: $record->name,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_on_site')
                ->label('Sitede Göster')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn (Program $record): string => route('programs.show', $record))
                ->openUrlInNewTab(),
            DeleteAction::make()
                ->before(function (Program $record) {
                    $userName = auth()->user()?->name ?? 'Kullanıcı';
                    AuditLogger::log(
                        action: 'deleted',
                        message: "{$userName}, {$record->name} programını kalıcı olarak sildi.",
                        subject: $record,
                        subjectLabel: $record->name,
                        isDestructive: true,
                    );
                }),
        ];
    }
}
