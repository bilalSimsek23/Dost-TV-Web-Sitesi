<!DOCTYPE html>
<html lang="tr" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOST TV — Şifre Belirleme</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans antialiased text-gray-900 dark:text-gray-100">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-amber-500 text-white font-bold text-xl shadow-md mb-4">
            D
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            DOST TV Yönetim Paneli
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Hesap Etkinleştirme ve Şifre Belirleme
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white dark:bg-gray-900 py-8 px-6 shadow-sm rounded-2xl sm:px-10 border border-gray-100 dark:border-white/5">
            @if(! $isValid)
                <div class="rounded-xl bg-red-50 dark:bg-red-950/40 p-4 border border-red-200 dark:border-red-900/50 text-center">
                    <div class="flex justify-center text-red-500 mb-2">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                        Geçersiz Bağlantı
                    </h3>
                    <p class="mt-1 text-xs text-red-700 dark:text-red-400">
                        {{ $errorMessage ?? 'Bu davet bağlantısı geçersiz veya süresi dolmuş.' }}
                    </p>
                    <div class="mt-4">
                        <a href="/admin/login" class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition">
                            Giriş Ekranına Dön
                        </a>
                    </div>
                </div>
            @else
                <div class="mb-6 pb-4 border-b border-gray-100 dark:border-white/5">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Davet Edilen Hesap</p>
                    <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-950/40 p-3 border border-red-200 dark:border-red-900/50">
                        <ul class="text-xs text-red-700 dark:text-red-400 space-y-1 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('invitation.accept.post', ['token' => $token]) }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="password" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Yeni Şifre <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password" id="password" required minlength="5" autocomplete="new-password"
                            placeholder="En az 5 karakter"
                            class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 transition">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Yeni Şifre Tekrarı <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required minlength="5" autocomplete="new-password"
                            placeholder="Şifrenizi tekrar girin"
                            class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 transition">
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-amber-600 hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition">
                            Şifremi Belirle ve Giriş Yap
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</body>
</html>
