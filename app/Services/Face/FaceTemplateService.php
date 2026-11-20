<?php

declare(strict_types=1);

namespace App\Services\Face;

use App\Enums\FaceErrorCode;
use App\Enums\LivenessPurpose;
use App\Exceptions\Face\FaceException;
use App\Models\FaceTemplate;

/** Enroll, identify and delete flows. Check order follows docs/API.md. */
final class FaceTemplateService
{
    public function __construct(
        private readonly EmbeddingCodec $codec,
        private readonly FaceMatcher $matcher,
        private readonly DeleteCodeService $deleteCodes,
        private readonly LivenessSessionService $sessions,
        private readonly LivenessValidator $liveness,
    ) {}

    /**
     * @param  array<int, float>  $embedding
     * @param  array{session_token: string, challenges: list<array<string, mixed>>}  $liveness
     * @return string the delete code, shown to the user once
     */
    public function enroll(array $embedding, mixed $consent, string $consentVersion, array $liveness): string
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

        $code = $this->deleteCodes->generate();
        FaceTemplate::query()->create([
            'embedding' => $this->codec->encrypt($normalized),
            'embedding_dim' => count($normalized),
            'model_version' => config('face.model_version'),
            'delete_code_hash' => $this->deleteCodes->hash($code),
            'consent_version' => $consentVersion,
        ]);

        return $code;
    }

    /**
     * @param  array<int, float>  $embedding
     * @param  array{session_token: string, challenges: list<array<string, mixed>>}  $liveness
     */
    public function identify(array $embedding, array $liveness): bool
    {
        $normalized = $this->codec->normalize($embedding);
        $this->checkLiveness(LivenessPurpose::Identify, $liveness);

        return $this->matcher->bestMatchId($normalized) !== null;
    }

    public function deleteByCode(string $code): void
    {
        $deleted = FaceTemplate::query()->where('delete_code_hash', $this->deleteCodes->hash($code))->delete();

        if ($deleted === 0) {
            throw new FaceException(FaceErrorCode::DeleteCodeInvalid);
        }
    }

    /** @param array{session_token: string, challenges: list<array<string, mixed>>} $liveness */
    private function checkLiveness(LivenessPurpose $purpose, array $liveness): void
    {
        $session = $this->sessions->consume($liveness['session_token'], $purpose);
        $this->liveness->validate($session, $liveness['challenges']);
    }
}
