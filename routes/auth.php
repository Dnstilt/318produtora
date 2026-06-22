<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('login', fn () => redirect()->route('admin.login'))
    ->name('login');

Route::middleware('guest')->prefix('admin')->group(function () {
    Route::view('forgot-password', 'auth.forgot-password')
        ->name('password.request');

    Route::post('forgot-password', function (Request $request) {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = (string) $request->input('email');

        $isAdmin = User::query()
            ->where('email', $email)
            ->where('is_admin', true)
            ->exists();

        if ($isAdmin) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with('status', 'Se o e-mail estiver cadastrado como administrador, um link de redefinição foi enviado.');
    })->name('password.email');

    Route::get('reset-password/{token}', function (string $token, Request $request) {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    })->name('password.reset');

    Route::post('reset-password', function (Request $request) {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = (string) $request->input('email');

        $isAdmin = User::query()
            ->where('email', $email)
            ->where('is_admin', true)
            ->exists();

        if (!$isAdmin) {
            return back()->withErrors([
                'email' => 'Este e-mail não tem acesso administrativo.',
            ]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('admin.login')
                ->with('status', 'Senha atualizada. Faça login para continuar.');
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => 'Link inválido ou expirado. Solicite um novo link.',
            Password::RESET_THROTTLED => 'Aguarde um pouco antes de solicitar novamente.',
            default => 'Não foi possível redefinir a senha. Tente novamente.',
        };

        return back()->withErrors([
            'email' => $message,
        ]);
    })->name('password.update');
});

Route::prefix('admin')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('admin.login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('admin.logout');
});
