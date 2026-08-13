<?php

namespace App\Filament\Resources\Programs;

use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Filament\Resources\Programs\Schemas\ProgramForm;
use App\Filament\Resources\Programs\Tables\ProgramsTable;
use App\Models\Program;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Program ve Video Yönetimi';

    protected static ?string $navigationLabel = 'Programlar';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EpisodesRelationManager::class,
        ];
    }

    public static function resolveRecordRouteBinding(int | string $key, ?\Closure $modifyQuery = null): ?\Illuminate\Database\Eloquent\Model
    {
        $query = static::getModel()::query();

        if ($modifyQuery) {
            $query = $modifyQuery($query) ?? $query;
        }

        return $query
            ->where(function ($q) use ($key) {
                $q->where('id', $key)
                  ->orWhere('slug', $key);
            })
            ->first();
    }


    public static function getPages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }
}

