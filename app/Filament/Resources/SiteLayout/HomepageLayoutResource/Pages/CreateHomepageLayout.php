<?php

namespace App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\HomepageLayoutResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateHomepageLayout extends CreateRecord
{
    protected static string $resource = HomepageLayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->validateUniqueCollections($data);
    }

    protected function getRedirectUrl(): string
    {
        return HomepageLayoutResource::getUrl('edit', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        if ($this->record->is_active) {
            $this->record->activate();
        }
    }

    protected function validateUniqueCollections(array $data): array
    {
        $sections = $data['draft_sections'] ?? [];
        $collectionIds = [];

        foreach ($sections as $section) {
            if (($section['block_type'] ?? '') === 'video_collection') {
                $colId = $section['collection_id'] ?? null;
                if ($colId) {
                    if (in_array($colId, $collectionIds, true)) {
                        throw ValidationException::withMessages([
                            'draft_sections' => 'Aynı video koleksiyonu aynı ana sayfa düzeninde iki farklı blokta seçilemez.',
                        ]);
                    }
                    $collectionIds[] = $colId;
                }
            }
        }

        return $data;
    }
}
