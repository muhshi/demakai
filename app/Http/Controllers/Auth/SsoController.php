<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('sipetra')->redirect();
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            // Redirect to filament login with error
            return redirect('/admin/login')->withErrors(['email' => 'Login SSO Dibatalkan.']);
        }

        try {
            $ssoUser = Socialite::driver('sipetra')->stateless()->user();
        } catch (\Exception $e) {
            return redirect('/admin/login')->withErrors(['email' => 'Gagal mengambil data dari SIPETRA SSO: ' . $e->getMessage()]);
        }

        if (!$ssoUser->getId() || !$ssoUser->getEmail()) {
            return redirect('/admin/login')->withErrors(['email' => 'Data User SIPETRA tidak lengkap. Pastikan Scope diizinkan.']);
        }

        $rawData = $ssoUser->getRaw();

        // Find user by sipetra_id first, then fallback to email to prevent duplicates
        $user = User::where('sipetra_id', $ssoUser->getId())->first()
             ?? User::where('email', $ssoUser->getEmail())->first();

        $data = [
            'sipetra_id'            => $ssoUser->getId(),
            'name'                  => $ssoUser->getName(),
            'email'                 => $ssoUser->getEmail(),
            'sipetra_token'         => $ssoUser->token,
            'sipetra_refresh_token' => $ssoUser->refreshToken ?? null,
            'nip'                   => $rawData['nip'] ?? null,
            'jabatan'               => $rawData['jabatan'] ?? null,
        ];

        if ($user) {
            $user->update($data);
        } else {
            // For new users, set password to null. They can use SSO only unless they set a password later.
            $data['password'] = null;
            $user = User::create($data);
            
            // Note: Not using assignRole() here because DEMAKAI doesn't use Spatie Permission yet.
        }

        Auth::login($user);
        return redirect()->intended('/admin');
    }
}
