<?php
namespace fmihel\router\crypto;

use fmihel\router\crypto;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\input;
use session;

function defineCryptoUnpacker(string $staticKey): void
{
    input::setUnpacker(function ($raw, array $envelope) use ($staticKey) {

        if (empty($envelope['enc']) || ! is_string($raw)) {
            return null;
        }

        $token = session::tokenFromRequest();
        if (! $token) {
            throw new AccessDenied('encrypted payload without token');
        }

        $plain   = crypto::decrypt($raw, crypto::getKeypair($staticKey, $token));
        $decoded = json_decode($plain, true);
        if (! is_array($decoded)) {
            throw new \Exception('bad encrypted payload');
        }
        return $decoded;
    });
}
