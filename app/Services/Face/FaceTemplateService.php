<?php

declare(strict_types=1);

namespace App\Services\Face;

use App\Enums\FaceErrorCode;
use App\Enums\LivenessPurpose;
use App\Exceptions\Face\FaceException;
use App\Models\FaceTemplate;

/** Enroll and login by face. Check order follows docs/API.md. */
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

    /** @param array{session_token: string, challenges: list<array<string, mixed>>} $liveness */
    private function checkLiveness(LivenessPurpose $purpose, array $liveness): void
    {
        $session = $this->sessions->consume($liveness['session_token'], $purpose);
        $this->liveness->validate($session, $liveness['challenges']);
    }
}
