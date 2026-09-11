<?php

namespace App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\HomepageLayoutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomepageLayouts extends ListRecords
{
    protected static string $resource = HomepageLayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('+ Yeni Sayfa Düzeni')
                ->modalHeading('Yeni Sayfa Düzeni Oluştur')
                ->modalDescription('Tasarlamak istediğiniz sayfa türünü ve hedefini seçin. Kaydettikten sonra doğrudan Visual Builder açılacaktır.')
                ->form([
                    \Filament\Forms\Components\Select::make('page_type')
                        ->label('Ne Tasarlamak İstiyorsunuz?')
                        ->options([
                            'home' => 'Ana Sayfa',
                            'program_detail' => 'Program Detay Şablonu',
                            'program_index' => 'Programlar Sayfası',
                            'schedule' => 'Yayın Akışı',
                            'live_tv' => 'Canlı TV',
                            'program_collection' => 'Program Koleksiyonu',
                            'video_collection' => 'Video Koleksiyonu',
                        ])
                        ->default('home')
                        ->live()
                        ->required(),

                    \Filament\Forms\Components\Select::make('target_id_program_collection')
                        ->label('Hangi Program Koleksiyonu?')
                        ->options(fn () => \App\Models\ProgramCollection::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                        ->searchable()
                        ->visible(fn (callable $get) => $get('page_type') === 'program_collection')
                        ->required(fn (callable $get) => $get('page_type') === 'program_collection'),

                    \Filament\Forms\Components\Select::make('target_id_video_collection')
                        ->label('Hangi Video Koleksiyonu?')
                        ->options(fn () => \App\Models\VideoCollection::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->visible(fn (callable $get) => $get('page_type') === 'video_collection')
                        ->required(fn (callable $get) => $get('page_type') === 'video_collection'),

                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Düzen Adı (İsteğe Bağlı)')
                        ->placeholder('Boş bırakılırsa sayfa adı otomatik atanır')
                        ->maxLength(255),
                ])
                ->action(function (array $data) {
                    $pageType = $data['page_type'];
                    $targetId = match ($pageType) {
                        'program_collection' => (int) ($data['target_id_program_collection'] ?? null),
                        'video_collection' => (int) ($data['target_id_video_collection'] ?? null),
                        default => null,
                    };

                    $record = \App\Services\Page\PageDiscoveryService::findOrCreateLayoutForTarget(
                        $pageType,
                        $targetId,
                        $data['name'] ?? null
                    );

                    return redirect(HomepageLayoutResource::getUrl('edit', ['record' => $record]));
                }),
        ];
    }
}
