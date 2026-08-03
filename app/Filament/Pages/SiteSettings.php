<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.site-settings';

    protected static ?string $navigationLabel = 'Site Ayarları';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('site_name')
                    ->label('Site Adı')
                    ->required(),
                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->disk('public')
                    ->directory('branding'),
                Select::make('live_tv_type')
                    ->label('Canlı TV Türü')
                    ->options([
                        'iframe' => 'Iframe (embed link)',
                        'hls' => 'HLS Yayın (.m3u8)',
                    ])
                    ->default('iframe')
                    ->required(),
                TextInput::make('live_tv_url')
                    ->label('Canlı TV Yayın Linki')
                    ->url(),
                TextInput::make('radio_name')
                    ->label('Radyo Adı'),
                TextInput::make('radio_stream_url')
                    ->label('Canlı Radyo Yayın Linki (mp3/aac stream)')
                    ->url(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        SiteSetting::current()->update($this->form->getState());

        Notification::make()
            ->title('Ayarlar kaydedildi')
            ->success()
            ->send();
    }
}
