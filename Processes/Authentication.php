<?php

namespace Pepper\Process;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Pepper\Processes\PepperResponse;
use starlight\HTTP\Types\ResponseCode;

class Authentication
{
    private string $issuer;
    private string $audience;
    private string $jwksUrl;
    private string $cacheFile;
    private int $cacheTtl;

    public function __construct(
        string $issuer,
        string $audience,
        string $jwksUrl,
        string $cacheDir = __DIR__ . '/../../cache',
        int $cacheTtl = 3600
    ) {
        $this->issuer   = rtrim($issuer, '/');
        $this->audience = $audience;
        $this->jwksUrl  = $jwksUrl;
        $this->cacheTtl = $cacheTtl;

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $this->cacheFile = $cacheDir . '/jwks_cache.json';
    }

    /**
     * Public entry point.
     */
    public function authenticate(array $requiredScopes = []): object
    {
        $token = $this->getBearerToken();

        if (!$token) {
            $this->unauthorized('Missing bearer token');
        }

        $decoded = $this->validateToken($token);

        $this->validateClaims($decoded);

        if (!empty($requiredScopes)) {
            $this->validateScopes($decoded, $requiredScopes);
        }

        return $decoded;
    }

    private function getBearerToken(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            return null;
        }

        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function validateToken(string $token): object
    {
        $jwks = $this->getCachedJwks();
        $keys = JWK::parseKeySet($jwks);

        return JWT::decode($token, $keys);

    }

    private function validateClaims(object $decoded): void
    {
        // Issuer
        if (($decoded->iss ?? '') !== $this->issuer) {
            $this->unauthorized('Invalid issuer');
        }

        // Audience (aud may be string or array)
        $aud = $decoded->aud ?? null;
        $audiences = is_array($aud) ? $aud : [$aud];

        if (!in_array($this->audience, $audiences, true)) {
            $this->unauthorized('Invalid audience');
        }

        // Expiration handled automatically by JWT::decode
        // But we explicitly guard anyway for clarity:
        if (isset($decoded->exp) && $decoded->exp < time()) {
            $this->unauthorized('Token expired');
        }

        if (isset($decoded->nbf) && $decoded->nbf > time()) {
            $this->unauthorized('Token not yet valid');
        }
    }

    private function validateScopes(object $decoded, array $requiredScopes): void
    {
        $tokenScopes = explode(' ', $decoded->scope ?? '');

        foreach ($requiredScopes as $scope) {
            if (!in_array($scope, $tokenScopes, true)) {
                $this->unauthorized('Insufficient scope');
            }
        }
    }

    private function getCachedJwks(): array
    {
        if (
            file_exists($this->cacheFile) &&
            (filemtime($this->cacheFile) + $this->cacheTtl) > time()
        ) {
            return json_decode(file_get_contents($this->cacheFile), true);
        }

        $jwks = file_get_contents($this->jwksUrl);

        if ($jwks === false) {
            throw new Exception('Unable to fetch JWKS');
        }

        file_put_contents($this->cacheFile, $jwks);

        return json_decode($jwks, true);
    }

    #[NoReturn]
    private function unauthorized(string $message): void
    {
        new PepperResponse()->api(ResponseCode::Unauthorized(), $message);
        exit;
    }
}