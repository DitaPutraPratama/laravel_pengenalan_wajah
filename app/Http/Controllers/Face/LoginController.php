<?php

declare(strict_types=1);

namespace App\Http\Controllers\Face;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\FaceScanRequest;
use App\Services\Face\FaceTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /** Signs in the account whose face matches. The answer carries no score or identity. */
    public function __invoke(FaceScanRequest $request, FaceTemplateService $faces): JsonResponse
    {
        Auth::login($faces->login($request->validated('embedding'), $request->validated('liveness')));
        $request->session()->regenerate();

        return response()->json(['data' => ['redirect' => route('face.dashboard')]]);
    }
}
