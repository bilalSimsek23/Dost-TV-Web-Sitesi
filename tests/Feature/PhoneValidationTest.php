<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\FontFamily;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Page $contactPage;

    protected function setUp(): void
    {
        parent::setUp();

        FontFamily::create([
            'name' => 'Instrument Sans',
            'slug' => 'instrument-sans',
            'source_type' => 'google_fonts',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);

        $this->contactPage = Page::updateOrCreate(
            ['slug' => 'iletisim'],
            [
                'title' => 'İletişim',
                'content' => '',
                'page_type' => 'corporate',
                'status' => 'published',
                'settings' => [
                    'address' => 'Eski Adres',
                    'phone' => '0312 000 00 00',
                    'email' => 'old@dosttv.com',
                ],
            ]
        );
    }

    public function test_phone_format_with_plus_parentheses_and_spaces_saves_successfully(): void
    {
        $address = 'Zübeyde Hanım, İstanbul Caddesi, Devrez Sokak No:1, 06070 Altındağ/Ankara';
        $phone = '+90 (312) 341 21 21';
        $email = 'ik@dosttv.com';

        // 1. Livewire EditPage save
        Livewire::actingAs($this->admin)
            ->test(EditPage::class, ['record' => $this->contactPage->getRouteKey()])
            ->fillForm([
                'settings.address' => $address,
                'settings.phone' => $phone,
                'settings.email' => $email,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Kurumsal Bilgi Güncellendi');

        // 2. DB verification
        $settings = $this->contactPage->fresh()->settings;
        $this->assertEquals($address, $settings['address']);
        $this->assertEquals($phone, $settings['phone']);
        $this->assertEquals($email, $settings['email']);

        // 3. Reload edit page verification
        $reloadResponse = $this->actingAs($this->admin)
            ->get(PageResource::getUrl('edit', ['record' => $this->contactPage]));
        $reloadResponse->assertSuccessful()
            ->assertSee($phone);

        // 4. Public /iletisim GET verification
        $publicResponse = $this->get('/iletisim');
        $publicResponse->assertSuccessful()
            ->assertSee($phone);
    }

    public function test_all_standard_phone_formats_pass_validation(): void
    {
        $formats = [
            '(0312) 341 21 21',
            '0312 341 21 21',
            '03123412121',
            '+90 (312) 341 21 21',
            '+90 312 341 21 21',
            '+903123412121',
            '+90 (312) 341-21-21',
        ];

        foreach ($formats as $testPhone) {
            Livewire::actingAs($this->admin)
                ->test(EditPage::class, ['record' => $this->contactPage->getRouteKey()])
                ->fillForm([
                    'settings.phone' => $testPhone,
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertEquals($testPhone, $this->contactPage->fresh()->settings['phone']);
        }
    }
}
