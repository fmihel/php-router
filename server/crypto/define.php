<?php
namespace fmihel\router\crypto;

use fmihel\router\crypto;
use fmihel\router\exceptions\AccessDenied;
use fmihel\router\input;

class define
{
    public static function cryptoUnpacker($funcReturnKeys): void
    {
        input::setUnpacker(function ($raw, array $envelope) use ($funcReturnKeys) {

            if (empty($envelope['enc']) || ! is_string($raw)) {
                return null;
            }

            $keys      = $funcReturnKeys();
            $staticKey = $keys[0];
            $token     = $keys[1];

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

}
