<?php

namespace App\Filament\Resources\SiteLayout\HomepageLayoutResource\Schemas;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\VideoCollection;
use App\Services\Home\HomepageBlockRegistry;
use App\Services\Home\HomepageDataService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HomepageLayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Düzen Başlangıç Bilgileri')
                ->description('Sayfa düzeninin adını ve türünü seçin. Tasarım değişikliklerini Visual Builder üzerinden yönetebilirsiniz.')
                ->schema([
                    TextInput::make('name')
                        ->label('Düzen Adı')
                        ->placeholder('Örn: Ana Sayfa Varsayılan, Program Detay Düzeni')
                        ->required()
                        ->maxLength(255),

                    Select::make('page_type')
                        ->label('Sayfa Türü')
                        ->options([
                            'home' => 'Ana Sayfa',
                            'program_detail' => 'Program Detay',
                        ])
                        ->default('home')
                        ->required(),

                    Toggle::make('is_active')
                        ->label('Canlı Yap (Aktif Et)')
                        ->helperText('Bu düzen aktif edildiğinde aynı sayfa türündeki diğer düzenler otomatik olarak pasife alınır.')
                        ->default(false),
                ])->columns(2),
        ]);
    }
}
