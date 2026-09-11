<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormSubmittedMail;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // 1. Honeypot check for spam bots
        if ($request->filled('website')) {
            Log::info('Spam honeypot engellendi.', ['ip' => $request->ip()]);
            return back()->with('contact_success', 'Mesajınız bize ulaştı. En kısa sürede sizinle iletişime geçeceğiz.');
        }

        // 2. Server-side validation
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'name.required' => 'Lütfen adınızı ve soyadınızı giriniz.',
            'email.required' => 'Lütfen e-posta adresinizi giriniz.',
            'email.email' => 'Lütfen geçerli bir e-posta adresi giriniz.',
            'subject.required' => 'Lütfen mesaj konusunu giriniz.',
            'message.required' => 'Lütfen mesajınızı giriniz.',
        ]);

        // 3. Save message to database
        $contactMessage = ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => ContactMessage::STATUS_NEW,
        ]);

        // 4. Try sending email notification (Fail-safe)
        try {
            $siteSetting = SiteSetting::current();
            $recipient = $siteSetting?->email ?: config('mail.from.address');

            if (! empty($recipient)) {
                Mail::to($recipient)->send(new ContactFormSubmittedMail($contactMessage));
            }
        } catch (\Throwable $e) {
            Log::error('İletişim mesajı e-postası gönderilemedi: ' . $e->getMessage(), [
                'contact_message_id' => $contactMessage->id,
                'exception' => $e,
            ]);
        }

        // 5. Success response
        return back()->with('contact_success', 'Mesajınız bize ulaştı. En kısa sürede sizinle iletişime geçeceğiz.');
    }
}
