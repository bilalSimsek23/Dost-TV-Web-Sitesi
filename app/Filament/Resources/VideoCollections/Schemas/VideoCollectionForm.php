<?php

namespace App\Filament\Resources\VideoCollections\Schemas;

use App\Models\Category;
use App\Models\VideoCollection;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class VideoCollectionForm
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
                            ->placeholder('Örn: Sağlık Videoları, Sizin İçin Seçtiklerimiz')
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
                            ->placeholder('saglik-videolari')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('source_type')
                            ->label('Kaynak Türü')
                            ->options(VideoCollection::SOURCE_TYPES)
                            ->default('manual')
                            ->required()
                            ->live()
                            ->helperText(function (callable $get) {
                                $st = $get('source_type');
                                if ($st === 'featured') {
                                    return 'Aktif yayın dönemindeki canlı programlara ait yayınlanmış videolar otomatik olarak gelir. Bu koleksiyon otomatik güncellenir.';
                                }
                                if ($st === 'active_period_program_videos') {
                                    return 'Aktif yayın dönemindeki tüm programlara ait yayınlanmış videolar otomatik olarak gelir.';
                                }
                                return null;
                            })
                            ->columnSpanFull(),

                        Select::make('category_id')
                            ->label('Bağlı Kategori')
                            ->options(fn () => Category::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Kategori Seçin...')
                            ->visible(fn (callable $get) => in_array($get('source_type'), ['category', 'hybrid'], true))
                            ->columnSpanFull()
                            ->helperText('Bu kategoriye eklenen tüm aktif/yayındaki videolar koleksiyona otomatik dahil olur.'),

                        Select::make('sort_mode')
                            ->label('Sıralama')
                            ->options(VideoCollection::SORT_MODES)
                            ->default('newest')
                            ->columnSpanFull()
                            ->helperText('En Çok İzlenen / Beğenilen / Yorum Alan seçenekleri YouTube istatistiklerine göre sıralanır.'),

                        Placeholder::make('auto_source_status')
                            ->label('Otomatik Kaynak Durumu')
                            ->visible(fn (?VideoCollection $record, callable $get) => $record !== null && ($get('source_type') ?? $record->source_type) !== 'manual')
                            ->content(function (?VideoCollection $record, callable $get) {
                                if (! $record) {
                                    return null;
                                }

                                $sourceType = $get('source_type') ?? $record->source_type;
                                if ($sourceType === 'manual') {
                                    return null;
                                }

                                $tempRecord = clone $record;
                                $tempRecord->source_type = $sourceType;
                                if ($get('category_id')) {
                                    $tempRecord->category_id = $get('category_id');
                                }

                                $resolvedEpisodes = $tempRecord->resolveEpisodes();
                                $totalCount = $resolvedEpisodes->count();

                                if ($sourceType === 'category') {
                                    $catId = $get('category_id') ?? $record->category_id;
                                    $cat = $catId ? Category::find($catId) : null;
                                    $catName = $cat ? $cat->name : 'Seçilen';
                                    if ($totalCount === 0) {
                                        return 'Bu kategoriye bağlı public/published video bulunmuyor. Bu koleksiyon şu anda public sitede boş olacaktır.';
                                    }
                                    $formattedCount = number_format($totalCount, 0, ',', '.');
                                    return "{$catName} kategorisine bağlı {$formattedCount} public/published video bulundu.";
                                }

                                if ($sourceType === 'featured') {
                                    if ($totalCount === 0) {
                                        return 'Aktif yayın dönemindeki canlı programlara ait video bulunamadı. Bu koleksiyon şu anda public sitede boş olacaktır.';
                                    }
                                    $formattedCount = number_format($totalCount, 0, ',', '.');
                                    return "Aktif yayın dönemindeki canlı programlardan {$formattedCount} video bulundu.";
                                }

                                if ($sourceType === 'active_period_program_videos') {
                                    if ($totalCount === 0) {
                                        return 'Aktif yayın dönemindeki programlara ait video bulunamadı. Bu koleksiyon şu anda public sitede boş olacaktır.';
                                    }
                                    $formattedCount = number_format($totalCount, 0, ',', '.');
                                    return "Aktif yayın dönemindeki tüm programlardan {$formattedCount} video bulundu.";
                                }

                                if ($sourceType === 'hybrid') {
                                    $pinnedCount = $record->episodes()->count();
                                    $pinnedIds = $record->episodes()->pluck('episodes.id')->all();
                                    $autoEpisodes = $resolvedEpisodes->reject(fn ($ep) => in_array($ep->id, $pinnedIds, true));
                                    $autoCount = $autoEpisodes->count();

                                    $pinnedFormatted = number_format($pinnedCount, 0, ',', '.');
                                    $autoFormatted = number_format($autoCount, 0, ',', '.');

                                    if ($totalCount === 0) {
                                        return 'Bu koleksiyonda sabitlenen veya otomatik gelen video bulunmuyor. Bu koleksiyon şu anda public sitede boş olacaktır.';
                                    }

                                    return "{$pinnedFormatted} sabitlenen video, {$autoFormatted} otomatik gelen video bulundu.";
                                }

                                return null;
                            })
                            ->columnSpanFull(),

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
                            ->helperText(function (?VideoCollection $record) {
                                if (! $record) {
                                    return 'Pasif koleksiyonlar ana sayfa ve public vitrinlerde gösterilmez.';
                                }
                                $usage = app(\App\Services\Home\CollectionUsageDetector::class)->getVideoCollectionUsage($record);
                                $usedIn = [];
                                if ($usage['isPublished']) {
                                    $usedIn[] = 'Ana Sayfa';
                                }
                                if ($usage['isVideoCenter']) {
                                    $usedIn[] = 'Video Merkezi';
                                }
                                if (! empty($usedIn)) {
                                    $joined = implode(' ve ', $usedIn);
                                    return "⚠️ Bu koleksiyon {$joined} üzerinde kullanılıyor. Pasife alırsanız ilgili raflar görünmez.";
                                }
                                return 'Pasif koleksiyonlar ana sayfa ve public vitrinlerde gösterilmez.';
                            })
                            ->default(true),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }
}
