<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\Announcements\AnnouncementResource;

class CreateAnnouncement extends BaseCreateRecord
{
    protected static string $resource = AnnouncementResource::class;
}
