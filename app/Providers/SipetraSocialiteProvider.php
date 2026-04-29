<?php

namespace App\Providers;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class SipetraSocialiteProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            config('services.sipetra.base_url') . '/oauth/authorize',
            $state
        );
    }

    protected function getTokenUrl()
    {
        return config('services.sipetra.base_url') . '/oauth/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            config('services.sipetra.base_url') . '/api/user',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'     => $user['id'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'avatar' => $user['avatar'] ?? null,
        ]);
    }

    protected function getTokenFields($code)
    {
        $fields = parent::getTokenFields($code);
        $fields['grant_type'] = 'authorization_code';
        return $fields;
    }

    protected function getDefaultScopes()
    {
        return config('services.sipetra.scopes', []);
    }
}
