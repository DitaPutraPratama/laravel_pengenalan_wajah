<?php

declare(strict_types=1);

namespace App\Http\Controllers\Face;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\EnrollRequest;
use App\Services\Face\FaceTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EnrollController extends Controller
{
    /** Registers the face and signs the new account in. */
    public function __invoke(EnrollRequest $request, FaceTemplateService $faces): JsonResponse
    {
        $template = $faces->enroll(
            $request->validated('embedding'),
            $request->input('consent'),
            $request->validated('consent_version'),
            $request->validated('liveness'),
        );

        Auth::login($template);
        $request->session()->regenerate();

        return response()->json(['data' => ['redirect' => route('face.dashboard')]], 201);
    }
}
