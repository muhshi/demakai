<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class SsoLogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $loginUrl = Filament::hasLogin() ? Filament::getLoginUrl() : url('/');
        $sipetraBaseUrl = config('services.sipetra.base_url', 'https://bpsdemak.com');

        if (! empty($sipetraBaseUrl)) {
            $returnUrl = urlencode($loginUrl);

            return redirect()->away(rtrim($sipetraBaseUrl, '/').'/oauth/logout?redirect_uri='.$returnUrl);
        }

        return redirect()->to($loginUrl);
    }
}
