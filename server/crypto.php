<?php

namespace fmihel\router;

class crypto
{
    // создание пары публичный и приватныц= ключ
    public static function getKeypair(string $staticKey, string $sessionKey): string
    {
        $seed = hash_hmac('sha256', $sessionKey, $staticKey, true);
        return sodium_crypto_box_seed_keypair($seed);
    }
    
    // поулучить публичный ключ
    public static function getPublicKey(string $keypair): string
    {
        $publicKey = sodium_crypto_box_publickey($keypair);
        return base64_encode($publicKey);
    }

    // расшифровка
    public static function decrypt(string $encryptedBase64, string $keypair): string
    {
        $ciphertext = base64_decode($encryptedBase64, true);
        if ($ciphertext === false) {
            throw new \Exception('Invalid base64 payload');
        }

        // Расшифровка sealed box
        $decrypted = sodium_crypto_box_seal_open($ciphertext, $keypair);

        sodium_memzero($keypair);

        if ($decrypted === false) {
            throw new \Exception('Decryption failed or data tampered');
        }

        return $decrypted;
    }
}
