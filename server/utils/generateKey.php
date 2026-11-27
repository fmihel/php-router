<?php
namespace fmihel\router\utils;

/**
 * Генерирует криптографически стойкий ключ (32 байта / 256 бит) для HS256 / HMAC-SHA256.
 *
 * 1. Использование в firebase/php-jwt
 *  $jwt = \Firebase\JWT\JWT::encode($payload, $key, 'HS256');
 *
 * 2. Использование в hash_hmac
 * $signature = hash_hmac('sha256', $data, $key);
 *
 * @param bool $asBase64 Вернуть в формате Base64 (44 символа) вместо Hex (64 символа).
 * @return string
 */
function generateKey(bool $asBase64 = false): string
{
    // 32 байта = 256 бит (оптимальный размер для SHA-256)
    $rawBytes = random_bytes(32);

    if ($asBase64) {
        return sodium_bin2base64($rawBytes, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    return sodium_bin2hex($rawBytes);
}
