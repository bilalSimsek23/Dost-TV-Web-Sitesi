<?php

namespace App\Filament\Resources\ScheduleTemplates;

use App\Filament\Resources\ScheduleTemplates\Pages\CreateScheduleTemplate;
use App\Filament\Resources\ScheduleTemplates\Pages\EditScheduleTemplate;
use App\Filament\Resources\ScheduleTemplates\Pages\ListScheduleTemplates;
use App\Filament\Resources\ScheduleTemplates\Schemas\ScheduleTemplateForm;
use App\Filament\Resources\ScheduleTemplates\Tables\ScheduleTemplatesTable;
use App\Models\ScheduleTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScheduleTemplateResource extends Resource
{
    protected static ?string $model = ScheduleTemplate::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Yayın Yönetimi';

    protected static ?string $navigationLabel = 'Yayın Dönemleri';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Yayın Dönemi';

    protected static ?string $pluralModelLabel = 'Yayın Dönemleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static function form(Schema $schema): Schema
    {
        return ScheduleTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduleTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduleTemplates::route('/'),
            'create' => CreateScheduleTemplate::route('/create'),
            'edit' => EditScheduleTemplate::route('/{record}/edit'),
        ];
    }
}
