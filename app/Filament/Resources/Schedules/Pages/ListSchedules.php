<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Schedule;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Yayın Kaydı Ekle'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Tüm Günler'),
        ];

        foreach (Schedule::DAYS as $dayIndex => $dayName) {
            $tabs['day_' . $dayIndex] = Tab::make($dayName)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('day_of_week', $dayIndex))
                ->badge(fn () => Schedule::where('day_of_week', $dayIndex)->count());
        }

        return $tabs;
    }
}
