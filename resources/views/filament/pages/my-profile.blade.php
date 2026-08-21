<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Sekmeler -->
        <div class="border-b border-gray-200 dark:border-white/10">
            <nav class="-mb-px flex space-x-6">
                <button type="button" wire:click="$set('activeTab', 'account')"
                    class="py-3 px-1 border-b-2 font-medium text-sm transition {{ $activeTab === 'account' ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    <span class="flex items-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-user" class="w-4 h-4" />
                        <span>Hesap Bilgileri</span>
                    </span>
                </button>

                <button type="button" wire:click="$set('activeTab', 'password')"
                    class="py-3 px-1 border-b-2 font-medium text-sm transition {{ $activeTab === 'password' ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    <span class="flex items-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-key" class="w-4 h-4" />
                        <span>Şifre Değiştir</span>
                    </span>
                </button>

                <button type="button" wire:click="$set('activeTab', 'audit_logs')"
                    class="py-3 px-1 border-b-2 font-medium text-sm transition {{ $activeTab === 'audit_logs' ? 'border-primary-600 text-primary-600 dark:text-primary-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    <span class="flex items-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-clock" class="w-4 h-4" />
                        <span>Benim İşlemlerim</span>
                    </span>
                </button>
            </nav>
        </div>

        <!-- 1. Hesap Bilgileri Sekmesi -->
        @if($activeTab === 'account')
            <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-white/5 space-y-6">
                <form wire:submit="updateProfile" class="space-y-6">
                    {{ $this->accountForm }}

                    <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-white/5">
                        <x-filament::button type="submit">
                            Değişiklikleri Kaydet
                        </x-filament::button>
                    </div>
                </form>
            </div>

        <!-- 2. Şifre Değiştir Sekmesi -->
        @elseif($activeTab === 'password')
            <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl p-6 border border-gray-100 dark:border-white/5 space-y-6 max-w-xl">
                <form wire:submit="updatePassword" class="space-y-6">
                    {{ $this->passwordForm }}

                    <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-white/5">
                        <x-filament::button type="submit">
                            Şifreyi Güncelle
                        </x-filament::button>
                    </div>
                </form>
            </div>

        <!-- 3. Benim İşlemlerim Sekmesi -->
        @elseif($activeTab === 'audit_logs')
            <div class="space-y-4">
                {{ $this->table }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
