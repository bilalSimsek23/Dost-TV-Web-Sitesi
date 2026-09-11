<?php

namespace Tests\Feature;

use App\Mail\ContactFormSubmittedMail;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_submits_successfully_and_saves_to_db(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.store'), [
            'name' => 'Ahmet Yılmaz',
            'email' => 'ahmet@example.com',
            'phone' => '05551234567',
            'subject' => 'Program Hakkında Soru',
            'message' => 'Merhaba, yayın akışınızdaki programlar hakkında bilgi almak istiyorum.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('contact_success', 'Mesajınız bize ulaştı. En kısa sürede sizinle iletişime geçeceğiz.');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Ahmet Yılmaz',
            'email' => 'ahmet@example.com',
            'phone' => '05551234567',
            'subject' => 'Program Hakkında Soru',
            'message' => 'Merhaba, yayın akışınızdaki programlar hakkında bilgi almak istiyorum.',
            'status' => 'new',
        ]);

        Mail::assertSent(ContactFormSubmittedMail::class, function ($mail) {
            return $mail->contactMessage->name === 'Ahmet Yılmaz' &&
                   $mail->contactMessage->subject === 'Program Hakkında Soru';
        });
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => '',
            'email' => 'invalid-email',
            'phone' => '',
            'subject' => '',
            'message' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_honeypot_field_blocks_spam_bots(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.store'), [
            'name' => 'Spam Bot',
            'email' => 'bot@spam.com',
            'subject' => 'Spam Subject',
            'message' => 'Buy cheap links now',
            'website' => 'http://spambot.com', // Honeypot field filled
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('contact_success');

        // Message should NOT be saved in DB
        $this->assertDatabaseCount('contact_messages', 0);

        // Mail should NOT be sent
        Mail::assertNothingSent();
    }

    public function test_db_record_persists_even_if_mail_sending_fails(): void
    {
        // Simulate Mail exception
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('SMTP Connection Error'));

        $response = $this->post(route('contact.store'), [
            'name' => 'Mehmet Kaya',
            'email' => 'mehmet@example.com',
            'subject' => 'Test Konu',
            'message' => 'Test Mesaj İçeriği',
        ]);

        // User should still see success response
        $response->assertRedirect();
        $response->assertSessionHas('contact_success');

        // DB record MUST be saved
        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Mehmet Kaya',
            'email' => 'mehmet@example.com',
            'subject' => 'Test Konu',
        ]);
    }

    public function test_admin_can_view_contact_messages_list_and_mark_as_read(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $contactMessage = ContactMessage::create([
            'name' => 'Zeynep Demir',
            'email' => 'zeynep@example.com',
            'subject' => 'Görüş ve Öneri',
            'message' => 'Kanalınızı ilgiyle takip ediyoruz.',
            'status' => ContactMessage::STATUS_NEW,
        ]);

        $response = $this->actingAs($user)->get('/admin/contact-messages/' . $contactMessage->id);

        $response->assertStatus(200);

        // Status should automatically update from 'new' to 'read' upon viewing
        $this->assertEquals(ContactMessage::STATUS_READ, $contactMessage->fresh()->status);
    }
}
