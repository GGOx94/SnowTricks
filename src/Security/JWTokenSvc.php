<?php

namespace App\Security;

use DateTimeImmutable;

class JWTokenSvc
{
    private array $header = [ 'typ' => 'JWT', 'alg' => 'HS256' ];

    public function create(array $payload, string $secret, int $expireHours): string
    {
        if($expireHours > 0)
        {
            $nowTS = (new DateTimeImmutable())->getTimestamp();
            $payload['creationTime'] = $nowTS;
            $payload['expireTime'] = $nowTS + $expireHours * 3600;
        }

        $header64 = base64_encode(json_encode($this->header));
        $payload64 = base64_encode(json_encode($payload));
        
        $header64 = str_replace(['+', '/', '='], ['-', '_', ''], $header64);
        $payload64 = str_replace(['+', '/', '='], ['-', '_', ''], $payload64);
        
        $sign = hash_hmac('sha256', $header64 . '.' . $payload64, base64_encode($secret), true);
        $sign64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($sign));

        return $header64 . '.' . $payload64 . '.' . $sign64;
    }

    public function isRegexValid(string $token): bool
    {
        return preg_match('/^[a-zA-Z\d\-_=]+\.[a-zA-Z\d\-_=]+\.[a-zA-Z\d\-_=]+$/', $token ) === 1;
    }

    public function getPayload(string $token): array
    {
        $array = explode('.', $token);
        return json_decode(base64_decode($array[1]), true);;
    }

    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);
        $now = (new DateTimeImmutable())->getTimestamp();
        return $payload['expireTime'] < $now;
    }

    // Check validity of a given token using the app's secret key by creating a new one and comparing both string
    public function check(string $token, string $secret) : bool
    {
        $array = explode('.', $token);
        $payload = json_decode(base64_decode($array[1]), true);

        $verifToken = $this->create($payload, $secret, 0);

        return $token === $verifToken;
    }
}
