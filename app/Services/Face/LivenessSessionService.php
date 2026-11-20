<?php

declare(strict_types=1);

namespace App\Services\Face;

use App\Enums\ChallengeType;
use App\Enums\FaceErrorCode;
use App\Enums\LivenessPurpose;
use App\Exceptions\Face\FaceException;
use App\Models\LivenessSession;
use Random\Randomizer;

/** Single-use liveness sessions stored in the database (F-01, ADR-007). */
final class LivenessSessionService
{
    /** @return array{0: string, 1: LivenessSession} plain token (sent once) and the stored session */
    public function create(LivenessPurpose $purpose): array
    {
        $randomizer = new Randomizer;

        if ($randomizer->getInt(1, max(1, (int) config('face.session.cleanup_lottery'))) === 1) {
            LivenessSession::query()->where('expires_at', '<', now())->delete();
        }

        $token = bin2hex($randomizer->getBytes(32));
        $session = LivenessSession::query()->create([
            'token_hash' => hash('sha256', $token),
            'purpose' => $purpose->value,
            'challenges' => $randomizer->shuffleArray(array_column(ChallengeType::cases(), 'value')),
            'expires_at' => now()->addSeconds((int) config('face.session.ttl')),
        ]);

        return [$token, $session];
    }

    /** Marks the session used on first sight, even when the request later fails. */
    public function consume(string $token, LivenessPurpose $purpose): LivenessSession
    {
        $hash = hash('sha256', $token);
        $claimed = LivenessSession::query()
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        $session = $claimed === 1 ? LivenessSession::query()->where('token_hash', $hash)->first() : null;

        if ($session === null || $session->purpose !== $purpose->value) {
            throw new FaceException(FaceErrorCode::SessionInvalid);
        }

        return $session;
    }
}
