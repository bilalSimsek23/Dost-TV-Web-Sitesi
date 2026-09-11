<?php

namespace App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\HomepageLayoutResource;
use App\Models\HomepageLayout;
use App\Services\Page\PageDiscoveryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListHomepageLayouts extends ListRecords
{
    protected static string $resource = HomepageLayoutResource::class;

    public function mount(): void
    {
        PageDiscoveryService::ensureAllTargetsExist();
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_alternative')
                ->label('+ Yeni Alternatif Düzen')
                ->modalHeading('Yeni Alternatif Düzen Oluştur')
                ->modalDescription('Mevcut bir sayfa için alternatif bir tasarım varyantı (ör. Ramazan, Kandil vb.) oluşturabilirsiniz.')
                ->form([
                    TextInput::make('name')
                        ->label('Düzen / Varyant Adı')
                        ->placeholder('Örn: Ramazan Özel Tasarımı')
                        ->required()
                        ->maxLength(255),

                    Select::make('target_key')
                        ->label('Hedef Sayfa')
                        ->options(fn () => PageDiscoveryService::getSelectableTargetOptions())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $parts = explode('|', $data['target_key']);
                    $pageType = $parts[0] ?? 'home';
                    $targetId = isset($parts[1]) && $parts[1] !== '' ? (int) $parts[1] : null;

                    $record = HomepageLayout::create([
                        'name' => $data['name'],
                        'page_type' => $pageType,
                        'target_id' => $targetId,
                        'is_active' => false,
                        'draft_sections' => [],
                        'published_sections' => [],
                        'published_at' => null,
                    ]);

                    return redirect(HomepageLayoutResource::getUrl('edit', ['record' => $record]));
                }),
        ];
    }
}

