<?php

namespace App\Filament\Resources\ProgramCollections\Schemas;

use App\Models\Category;
use App\Models\ProgramCollection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProgramCollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Group::make()->schema([
                Section::make('Genel Bilgiler')
                    ->description('Koleksiyon adı, kaynak türü ve açıklama ayarları')
                    ->schema([
                        TextInput::make('name')
                            ->label('Koleksiyon Adı')
                            ->placeholder('Örn: Güncel Programlar, Aile Programları')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Bağlantı Adresi (Slug)')
                            ->placeholder('guncel-programlar')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('source_type')
                            ->label('Kaynak Türü')
                            ->options(ProgramCollection::getFormSourceTypeOptions())
                            ->default('manual')
                            ->required()
                            ->live()
                            ->helperText(function (callable $get) {
                                if (in_array($get('source_type'), ['manual', 'hybrid'], true)) {
                                    return 'Manuel Seçim veya Hibrit seçtikten sonra önce Kaydet butonuna basın. Sayfa yenilendiğinde alttaki \'Koleksiyondaki Programlar\' bölümünden program ekleyebilir, çıkarabilir ve sıralayabilirsiniz.';
                                }
                                if ($get('source_type') === 'archive_programs') {
                                    $hasArchive = Category::query()
                                        ->where('is_active', true)
                                        ->where(fn ($q) => $q->where('slug', 'arsiv')->orWhere('slug', 'archive')->orWhere('name', 'like', '%Arşiv%'))
                                        ->exists();
                                    return $hasArchive
                                        ? 'Merkezi Arşiv kategorisine bağlı tüm aktif programlar otomatik dahil edilir.'
                                        : 'Arşiv kategorisi bulunamadı.';
                                }
                                return null;
                            })
                            ->columnSpanFull(),

                        Select::make('category_id')
                            ->label('Bağlı Kategori')
                            ->options(fn () => Category::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Kategori Seçin...')
                            ->visible(fn (callable $get) => in_array($get('source_type'), ['category', 'hybrid'], true))
                            ->columnSpanFull()
                            ->helperText('Bu kategoriye yeni eklenen tüm aktif programlar koleksiyona otomatik dahil olur.'),

                        Textarea::make('description')
                            ->label('Kısa Açıklama')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->columnSpan(['lg' => 2]),

            Group::make()->schema([
                Section::make('Yayın Ayarları')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Yayında / Aktif')
                            ->helperText(function (?ProgramCollection $record) {
                                if (! $record) {
                                    return 'Pasif koleksiyonlar ana sayfa ve public vitrinlerde gösterilmez.';
                                }
                                $usage = app(\App\Services\Home\CollectionUsageDetector::class)->getProgramCollectionUsage($record);
                                if ($usage['isPublished']) {
                                    return '⚠️ Bu koleksiyon aktif Ana Sayfa düzeninde kullanılıyor. Pasife alırsanız ilgili Program Vitrini içerik göstermeyebilir.';
                                }
                                return 'Pasif koleksiyonlar ana sayfa ve public vitrinlerde gösterilmez.';
                            })
                            ->default(true),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }
}
