<?php

declare(strict_types=1);

namespace App\Http\Controllers\Face;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\FaceScanRequest;
use App\Services\Face\FaceTemplateService;
use Illuminate\Http\JsonResponse;

class IdentifyController extends Controller
{
    public function __invoke(FaceScanRequest $request, FaceTemplateService $faces): JsonResponse
    {
        $registered = $faces->identify($request->validated('embedding'), $request->validated('liveness'));

        return response()->json(['data' => ['registered' => $registered]]);
    }
}
