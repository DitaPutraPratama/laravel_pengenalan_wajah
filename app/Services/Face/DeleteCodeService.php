<?php

declare(strict_types=1);

namespace App\Services\Face;

use Random\Randomizer;

/** Delete codes: 26 base32 characters (130 bits). Only the SHA-256 hash is stored (F-07). */
final class DeleteCodeService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const LENGTH = 26;

    public function generate(): string
    {
        return (new Randomizer)->getBytesFromString(self::ALPHABET, self::LENGTH);
    }

    /** Users may paste the code with spaces, dashes or lowercase letters. */
    public function hash(string $code): string
    {
        return hash('sha256', strtoupper((string) preg_replace('/[\s-]+/', '', $code)));
    }
}
