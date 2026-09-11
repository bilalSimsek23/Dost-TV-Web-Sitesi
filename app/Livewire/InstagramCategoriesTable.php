<?php

namespace App\Livewire;

use App\Filament\Resources\InstagramCategories\Tables\InstagramCategoriesTable as CategoryTableSchema;
use App\Models\InstagramCategory;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class InstagramCategoriesTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return CategoryTableSchema::configure($table)
            ->query(InstagramCategory::query());
    }

    public function render()
    {
        return view('livewire.instagram-categories-table');
    }
}
