<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class MyProfilePage extends FilamentPage implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use PersistsTablePaginationInUrl;

    protected string $view = 'filament.pages.my-profile';

    protected static ?string $slug = 'hesabim';

    protected static ?string $title = 'Hesabım';

    protected static ?string $navigationLabel = 'Hesabım';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static bool $shouldRegisterNavigation = false;

    public ?array $accountData = [];

    public ?array $passwordData = [];

    public string $activeTab = 'account'; // 'account', 'password', 'audit_logs'

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $tab = request()->query('tab');
        if (in_array($tab, ['account', 'password', 'audit_logs'], true)) {
            $this->activeTab = $tab;
        }

        $user = auth()->user();
        $this->accountForm->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role_label' => $user->roleModel?->name ?? Role::BASE_ROLES[$user->baseRole()] ?? $user->role,
            'status_label' => $user->is_active ? 'Aktif' : 'Pasif',
        ]);

        $this->passwordForm->fill();
    }

    protected function getForms(): array
    {
        return [
            'accountForm',
            'passwordForm',
        ];
    }

    public function accountForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ad Soyad')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('E-posta')
                    ->disabled()
                    ->helperText('E-posta adresi sistem yöneticisi tarafından belirlenir.'),

                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->prefix('+90')
                    ->placeholder('5XX XXX XX XX')
                    ->mask('599 999 99 99')
                    ->helperText('Türkiye GSM numarası (5 ile başlayan 10 hane)')
                    ->formatStateUsing(function (?string $state) {
                        if (blank($state)) {
                            return null;
                        }
                        $digits = preg_replace('/\D/', '', $state);
                        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
                            return substr($digits, 2);
                        }
                        return $digits;
                    })
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (blank($value)) {
                                return;
                            }
                            $digits = preg_replace('/\D/', '', (string) $value);
                            if (str_starts_with($digits, '90') && strlen($digits) === 12) {
                                $digits = substr($digits, 2);
                            }
                            if (strlen($digits) !== 10 || ! str_starts_with($digits, '5')) {
                                $fail('Telefon numarası 5 ile başlayan 10 haneli geçerli bir cep telefonu olmalıdır (Örn: 5XX XXX XX XX).');
                            }
                        };
                    }),

                TextInput::make('role_label')
                    ->label('Rol')
                    ->disabled()
                    ->helperText('Yetki seviyeniz yöneticiniz tarafından belirlenir.'),

                TextInput::make('status_label')
                    ->label('Hesap Durumu')
                    ->disabled(),
            ])
            ->columns(2)
            ->statePath('accountData');
    }

    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label('Mevcut Şifre')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (! Hash::check((string) $value, auth()->user()->password)) {
                                $fail('Mevcut şifreniz hatalı.');
                            }
                        };
                    }),

                TextInput::make('new_password')
                    ->label('Yeni Şifre')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(5)
                    ->confirmed('new_password_confirmation')
                    ->helperText('En az 5 karakter olmalıdır.'),

                TextInput::make('new_password_confirmation')
                    ->label('Yeni Şifre Tekrarı')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(5),
            ])
            ->columns(1)
            ->statePath('passwordData');
    }

    public function updateProfile(): void
    {
        $data = $this->accountForm->getState();
        $user = auth()->user();

        $oldName = $user->name;
        $oldPhone = $user->phone;

        $normalizedPhone = User::normalizePhone($data['phone'] ?? null);

        // Security: Explicitly whitelist only name and phone
        $user->update([
            'name' => $data['name'],
            'phone' => $normalizedPhone,
        ]);

        $nameChanged = $oldName !== $user->name;
        $phoneChanged = $oldPhone !== $normalizedPhone;

        if ($nameChanged || $phoneChanged) {
            AuditLogger::log(
                action: 'updated',
                message: "{$user->name}, hesap bilgilerini güncelledi.",
                subject: $user,
                subjectLabel: $user->name,
            );
        }

        Notification::make()
            ->title('Hesap bilgileriniz başarıyla güncellendi.')
            ->success()
            ->send();
    }

    public function updatePassword(): void
    {
        $data = $this->passwordForm->getState();
        $user = auth()->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Mevcut şifreniz hatalı.')
                ->danger()
                ->send();

            return;
        }

        $user->update([
            'password' => $data['new_password'],
        ]);

        AuditLogger::log(
            action: 'updated',
            message: "{$user->name}, şifresini değiştirdi.",
            subject: $user,
            subjectLabel: $user->name,
        );

        $this->passwordForm->fill();

        Notification::make()
            ->title('Şifreniz başarıyla güncellendi.')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->where('user_id', auth()->id()))
            ->searchPlaceholder('İşlemlerimde ara...')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('İşlem')
                    ->formatStateUsing(fn (string $state, AuditLog $record) => $record->action_label)
                    ->badge()
                    ->color(fn (AuditLog $record): string => $record->action_color),

                TextColumn::make('subject_label')
                    ->label('İçerik')
                    ->searchable()
                    ->default('-')
                    ->color('gray'),

                TextColumn::make('message')
                    ->label('Açıklama')
                    ->searchable()
                    ->wrap()
                    ->icon(fn (AuditLog $record) => $record->is_destructive ? 'heroicon-m-exclamation-triangle' : null)
                    ->color(fn (AuditLog $record) => $record->is_destructive ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('İşlem Türü')
                    ->options(AuditLog::ACTIONS),

                Filter::make('created_at')
                    ->label('Tarih Aralığı')
                    ->form([
                        DatePicker::make('from')->label('Başlangıç Tarihi'),
                        DatePicker::make('until')->label('Bitiş Tarihi'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
