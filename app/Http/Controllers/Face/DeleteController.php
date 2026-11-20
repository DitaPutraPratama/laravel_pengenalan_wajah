<?php

declare(strict_types=1);

namespace App\Http\Controllers\Face;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\DeleteCodeRequest;
use App\Services\Face\FaceTemplateService;
use Illuminate\Http\JsonResponse;

class DeleteController extends Controller
{
    public function byCode(DeleteCodeRequest $request, FaceTemplateService $faces): JsonResponse
    {
        $faces->deleteByCode($request->validated('delete_code'));

        return response()->json(['data' => ['deleted' => true]]);
    }
}
