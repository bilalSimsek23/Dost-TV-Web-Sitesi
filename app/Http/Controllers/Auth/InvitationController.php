<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\UserInvitationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function __construct(
        protected UserInvitationService $invitationService
    ) {}

    public function show(string $token): View
    {
        $invitation = $this->invitationService->findValidInvitation($token);

        if (! $invitation) {
            return view('auth.invitation-accept', [
                'isValid' => false,
                'token' => $token,
                'errorMessage' => 'Bu davet bağlantısı geçersiz veya süresi dolmuş.',
            ]);
        }

        return view('auth.invitation-accept', [
            'isValid' => true,
            'token' => $token,
            'user' => $invitation->user,
            'invitation' => $invitation,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = $this->invitationService->findValidInvitation($token);

        if (! $invitation) {
            return redirect()->route('invitation.accept', ['token' => $token])
                ->withErrors(['password' => 'Bu davet bağlantısı geçersiz veya süresi dolmuş.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:5', 'confirmed'],
        ], [
            'password.required' => 'Şifre alanı zorunludur.',
            'password.min' => 'Şifre en az 5 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
        ]);

        $success = $this->invitationService->acceptInvitation($token, $validated['password']);

        if (! $success) {
            return redirect()->route('invitation.accept', ['token' => $token])
                ->withErrors(['password' => 'Davet onaylanırken bir hata oluştu.']);
        }

        session()->flash('status', 'Şifreniz oluşturuldu. Yönetim paneline giriş yapabilirsiniz.');

        return redirect()->to('/admin/login');
    }
}
