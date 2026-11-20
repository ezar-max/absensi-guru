<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $layout = 'filament.layouts.auth';
    protected static string $view = 'filament.pages.auth.login';

    public function getTitle(): string | Htmlable
    {
        return 'Masuk - SMKN 1 Gunung Putri';
    }

    public function getHeading(): string | Htmlable
    {
        return 'Selamat Datang';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Silakan login untuk melanjutkan';
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $loginInput = trim($data['email']);

        // Jika bukan format email, cari user berdasarkan nama/username
        if (!str_contains($loginInput, '@')) {
            $user = User::where('name', $loginInput)->first();
            if ($user) {
                $loginInput = $user->email;
            }
        }

        return [
            'email' => $loginInput,
            'password' => $data['password'],
        ];
    }
}
