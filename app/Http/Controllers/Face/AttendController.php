<?php

declare(strict_types=1);

namespace App\Http\Controllers\Face;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\AttendRequest;
use App\Services\Face\FaceTemplateService;
use Illuminate\Http\JsonResponse;

class AttendController extends Controller
{
    /** Face photo check (1:1) for today's attendance. The dashboard shows the flashed result. */
    public function __invoke(AttendRequest $request, FaceTemplateService $faces): JsonResponse
    {
        $attendance = $faces->attend($request->user(), $request->validated('embedding'));

        $request->session()->flash('status', __($attendance->wasRecentlyCreated ? 'face.dashboard.attended' : 'face.dashboard.already_attended'));

        return response()->json(['data' => ['redirect' => route('face.dashboard')]]);
    }
}
