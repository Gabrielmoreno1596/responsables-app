<?php

namespace App\Services;

final class RecaptchaService
{
    public function verify(string $token): bool
    {
        $secret = getenv('RECAPTCHA_SECRET_KEY');
        if (!$secret) return true;

        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret'   => $secret,
                'response' => $token,
            ]),
            CURLOPT_TIMEOUT => 8,
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) return false;
        $json = json_decode($resp, true);
        return !empty($json['success']);
    }
}
