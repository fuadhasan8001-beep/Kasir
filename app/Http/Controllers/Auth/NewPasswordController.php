<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'email' => $request->string('email')->toString(),
            'token' => $request->route('token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', PasswordRule::min(8), 'confirmed'],
        ], [
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordResetEvent($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Kata sandi berhasil diatur ulang. Silakan masuk dengan kata sandi baru.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
