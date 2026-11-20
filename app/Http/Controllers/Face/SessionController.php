<?php

declare(strict_types=1);

namespace App\Http\Controllers\Face;

use App\Enums\LivenessPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Face\SessionRequest;
use App\Services\Face\LivenessSessionService;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function __invoke(SessionRequest $request, LivenessSessionService $sessions): JsonResponse
    {
        [$token, $session] = $sessions->create($request->enum('purpose', LivenessPurpose::class));

        return response()->json(['data' => [
            'session_token' => $token,
            'challenges' => $session->challenges,
            'expires_in' => (int) config('face.session.ttl'),
            'challenge_timeout' => (int) config('face.session.challenge_timeout'),
        ]], 201);
    }
}
