<?php
declare(strict_types=1);

/**
 * RFC 6238 TOTP (Google Authenticator compatible).
 */
function totpGenerateSecret(int $length = 16): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $secret;
}

function totpBase32Decode(string $secret): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
    $bits = '';
    $len = strlen($secret);

    for ($i = 0; $i < $len; $i++) {
        $pos = strpos($alphabet, $secret[$i]);
        if ($pos === false) {
            continue;
        }
        $bits .= sprintf('%05b', $pos);
    }

    $output = '';
    $bitLen = strlen($bits);
    for ($i = 0; $i + 8 <= $bitLen; $i += 8) {
        $output .= chr((int) bindec(substr($bits, $i, 8)));
    }

    return $output;
}

function totpCode(string $secret, ?int $timestamp = null, int $period = 30, int $digits = 6): string
{
    $timestamp = $timestamp ?? time();
    $counter = intdiv($timestamp, $period);
    $key = totpBase32Decode($secret);
    if ($key === '') {
        return str_repeat('0', $digits);
    }

    $binary = pack('N*', 0, $counter);
    $hash = hash_hmac('sha1', $binary, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = (
        ((ord($hash[$offset]) & 0x7F) << 24)
        | ((ord($hash[$offset + 1]) & 0xFF) << 16)
        | ((ord($hash[$offset + 2]) & 0xFF) << 8)
        | (ord($hash[$offset + 3]) & 0xFF)
    );
    $otp = $truncated % (10 ** $digits);

    return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
}

function totpVerify(string $secret, string $code, int $window = 2): bool
{
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) < 6) {
        return false;
    }

    $code = substr($code, 0, 6);
    $now = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totpCode($secret, $now + ($i * 30)), $code)) {
            return true;
        }
    }

    return false;
}

function totpProvisioningUri(string $secret, string $accountLabel, string $issuer): string
{
    $label = rawurlencode($issuer . ':' . $accountLabel);
    $issuerEnc = rawurlencode($issuer);

    return 'otpauth://totp/' . $label . '?secret=' . $secret . '&issuer=' . $issuerEnc . '&algorithm=SHA1&digits=6&period=30';
}
