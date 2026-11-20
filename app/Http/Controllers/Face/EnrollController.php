<?php

declare(strict_types=1);

namespace App\Http\Controllers\Face;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\EnrollRequest;
use App\Services\Face\FaceTemplateService;
use Illuminate\Http\JsonResponse;

class EnrollController extends Controller
{
    public function __invoke(EnrollRequest $request, FaceTemplateService $faces): JsonResponse
    {
        $code = $faces->enroll(
            $request->validated('embedding'),
            $request->input('consent'),
            $request->validated('consent_version'),
            $request->validated('liveness'),
        );

        return response()->json(['data' => ['delete_code' => $code]], 201);
    }
}
