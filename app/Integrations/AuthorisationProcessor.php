<?php

namespace App\Integrations;

class AuthorisationProcessor
{
    public function __construct($accessToken, $integration)
    {
        $config = config($integration);
        $token = $config['tokenModel'];

        return (new $token($integration))->updateAccessToken($accessToken);
    }
}
