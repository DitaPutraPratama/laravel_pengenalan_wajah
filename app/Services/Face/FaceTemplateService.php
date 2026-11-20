<?php

declare(strict_types=1);

namespace App\Services\Face;

use App\Enums\FaceErrorCode;
use App\Enums\LivenessPurpose;
use App\Exceptions\Face\FaceException;
use App\Models\Attendance;
use App\Models\FaceTemplate;

/** Enroll, login and attendance by face. Check order follows docs/API.md. */
final class FaceTemplateService
{
    public function __construct(
        private readonly EmbeddingCodec $codec,
        private readonly FaceMatcher $matcher,
        private readonly LivenessSessionService $sessions,
        private readonly LivenessValidator $liveness,
    ) {}

    /**
     * @param  array<int, float>  $embedding
     * @param  array{session_token: string, challenges: list<array<string, mixed>>}  $liveness
     */
    public function enroll(array $embedding, mixed $consent, string $consentVersion, array $liveness): FaceTemplate
    {
        $normalized = $this->codec->normalize($embedding);

        if ($consent !== true) {
            throw new FaceException(FaceErrorCode::ConsentRequired);
        }

        $this->checkLiveness(LivenessPurpose::Enroll, $liveness);

        if (FaceTemplate::query()->count() >= (int) config('face.max_templates')) {
            throw new FaceException(FaceErrorCode::CapacityReached);
        }

        if ($this->matcher->bestMatchId($normalized) !== null) {
            throw new FaceException(FaceErrorCode::FaceAlreadyRegistered);
        }

        return FaceTemplate::query()->create([
            'embedding' => $this->codec->encrypt($normalized),
            'embedding_dim' => count($normalized),
            'model_version' => config('face.model_version'),
            'consent_version' => $consentVersion,
        ]);
    }

    /**
     * @param  array<int, float>  $embedding
     * @param  array{session_token: string, challenges: list<array<string, mixed>>}  $liveness
     */
    public function login(array $embedding, array $liveness): FaceTemplate
    {
        $normalized = $this->codec->normalize($embedding);
        $this->checkLiveness(LivenessPurpose::Login, $liveness);

        $id = $this->matcher->bestMatchId($normalized);

        return ($id === null ? null : FaceTemplate::query()->find($id))
            ?? throw new FaceException(FaceErrorCode::FaceNotFound);
    }

    /**
     * Today's attendance after a 1:1 check against the signed-in account. No liveness (ADR-021):
     * the session itself came from a login with liveness. Once per day; a repeat returns the same row.
     *
     * @param  array<int, float>  $embedding
     */
    public function attend(FaceTemplate $account, array $embedding): Attendance
    {
        $normalized = $this->codec->normalize($embedding);

        if ($account->name === null) {
            throw new FaceException(FaceErrorCode::ValidationFailed, ['fields' => ['name']]);
        }

        if (! $this->matcher->matches($normalized, $account)) {
            throw new FaceException(FaceErrorCode::FaceNotFound);
        }

        return $account->attendances()->firstOrCreate(['date' => today()]);
    }

    /** @param array{session_token: string, challenges: list<array<string, mixed>>} $liveness */
    private function checkLiveness(LivenessPurpose $purpose, array $liveness): void
    {
        $session = $this->sessions->consume($liveness['session_token'], $purpose);
        $this->liveness->validate($session, $liveness['challenges']);
    }
}
