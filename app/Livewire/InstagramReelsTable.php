<?php

namespace App\Livewire;

use App\Filament\Resources\InstagramVideos\Tables\InstagramVideosTable as VideoTableSchema;
use App\Models\InstagramVideo;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class InstagramReelsTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return VideoTableSchema::configure($table)
            ->query(InstagramVideo::query());
    }

    public function render()
    {
        return view('livewire.instagram-reels-table');
    }
}
