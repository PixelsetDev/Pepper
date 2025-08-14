<?php

namespace Pepper\Helpers;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

/**
 * Auth helper
 */
class Auth
{
    public function verifyLogtoIdToken($jwt): false|stdClass
    {
        $jwks = json_decode(file_get_contents(JWKS_URL), true)['keys'];

        $header = json_decode(base64_decode(explode('.', $jwt)[0]), true);
        $kid = $header['kid'] ?? null;
        if ($kid) {
            foreach ($jwks as $key) {
                if ($key['kid'] === $kid) {
                    try {
                        if ($key['kty'] === 'RSA' && isset($key['x5c'])) {
                            $publicKey = "-----BEGIN PUBLIC KEY-----\n"
                                . chunk_split(base64_decode($key['x5c'][0]), 64, "\n")
                                . "-----END PUBLIC KEY-----";
                            $algorithm = $key['alg'] ?? 'RS256';
                        } elseif ($key['kty'] === 'EC' && isset($key['x'], $key['y'])) {
                            $publicKey = $this->convertECKeyToPEM($key);
                            $algorithm = $key['alg'];
                        } else {
                            continue;
                        }

                        return JWT::decode($jwt, new Key($publicKey, $algorithm));
                    } catch (Exception $e) {
                        error_log("JWT decode error: " . $e->getMessage());
                        return false;
                    }
                }
            }
        }
        return false;
    }

    private function convertECKeyToPEM($key): string
    {
        $x = $this->base64urlDecode($key['x']);
        $y = $this->base64urlDecode($key['y']);

        if ($key['crv'] === 'P-384') {
            $curveOid = "\x30\x10\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x05\x2b\x81\x04\x00\x22";
        } else {
            throw new Exception("Unsupported curve: " . $key['crv']);
        }

        $publicKeyPoint = "\x04" . $x . $y;

        $publicKeyInfo = "\x30" . chr(strlen($curveOid) + strlen($publicKeyPoint) + 3)
            . $curveOid
            . "\x03" . chr(strlen($publicKeyPoint) + 1) . "\x00" . $publicKeyPoint;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($publicKeyInfo), 64, "\n")
            . "-----END PUBLIC KEY-----";
    }

    private function base64urlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
