<?php

namespace Pepper\Process;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Pepper\Processes\PepperResponse;
use starlight\HTTP\Types\ResponseCode;

/**
 * Class Authentication
 *
 * Handles JWT authentication for PHP APIs using Logto-issued access tokens.
 *
 * @package Pepper\Process
 */
class Authentication
{
    /**
     * @var string The expected issuer (iss) claim in the JWT.
     */
    private string $issuer;

    /**
     * @var string The expected audience (aud) claim in the JWT.
     */
    private string $audience;

    /**
     * @var string The JWKS endpoint URL for public key retrieval.
     */
    private string $jwksUrl;

    /**
     * @var string Local file path to cache the JWKS.
     */
    private string $cacheFile;

    /**
     * @var int JWKS cache time-to-live in seconds.
     */
    private int $cacheTtl;

    /**
     * Authentication constructor.
     *
     * @param string $issuer The expected JWT issuer.
     * @param string $audience The expected JWT audience.
     * @param string $jwksUrl The JWKS URL to retrieve public keys.
     * @param string $cacheDir Directory to cache JWKS (default: __DIR__ . '/../../cache').
     * @param int $cacheTtl JWKS cache lifetime in seconds (default: 3600).
     *
     * @throws Exception If the cache directory cannot be created.
     */
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
     * Authenticates the current request by validating the bearer token.
     *
     * @param array $requiredScopes Optional array of required scopes to validate.
     * @return object The decoded JWT payload if valid.
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

    /**
     * Retrieves the Bearer token from the Authorization header.
     *
     * @return string|null The JWT token, or null if missing.
     */
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

    /**
     * Validates and decodes the JWT using the JWKS.
     *
     * @param string $token The JWT to validate.
     * @return object The decoded JWT payload.
     *
     * @throws Exception If the JWKS cannot be fetched.
     */
    private function validateToken(string $token): object
    {
        $jwks = $this->getCachedJwks();
        $keys = JWK::parseKeySet($jwks);

        return JWT::decode($token, $keys);
    }

    /**
     * Validates standard JWT claims such as issuer, audience, exp, and nbf.
     *
     * @param object $decoded The decoded JWT payload.
     */
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

        if (isset($decoded->exp) && $decoded->exp < time()) {
            $this->unauthorized('Token expired');
        }

        if (isset($decoded->nbf) && $decoded->nbf > time()) {
            $this->unauthorized('Token not yet valid');
        }
    }

    /**
     * Validates that all required scopes exist in the JWT.
     *
     * @param object $decoded The decoded JWT payload.
     * @param array $requiredScopes Array of required scope strings.
     */
    private function validateScopes(object $decoded, array $requiredScopes): void
    {
        $tokenScopes = explode(' ', $decoded->scope ?? '');

        foreach ($requiredScopes as $scope) {
            if (!in_array($scope, $tokenScopes, true)) {
                $this->unauthorized('Insufficient scope');
            }
        }
    }

    /**
     * Retrieves JWKS from cache or fetches from remote if expired.
     *
     * @return array Parsed JWKS array.
     *
     * @throws Exception If JWKS cannot be fetched.
     */
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

    /**
     * Sends a 401 Unauthorized response and terminates execution.
     *
     * @param string $message Error message to return.
     */
    #[NoReturn]
    private function unauthorized(string $message): void
    {
        new PepperResponse()->api(ResponseCode::Unauthorized(), $message);
        exit;
    }


}