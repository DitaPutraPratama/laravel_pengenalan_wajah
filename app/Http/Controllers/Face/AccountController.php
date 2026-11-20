<?php

declare(strict_types=1);

namespace App\Http\Controllers\Face;

use App\Http\Controllers\Controller;
use App\Http\Requests\Face\NameRequest;
use App\Models\FaceTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Everything a signed-in account can do (F-19 to F-21). */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $account = $this->account($request);

        return view('face.dashboard', [
            'account' => $account,
            'today' => $account->attendances()->whereDate('date', today())->first(),
            'history' => $account->attendances()->latest('date')->limit(30)->get(),
        ]);
    }

    public function updateName(NameRequest $request): RedirectResponse
    {
        $this->account($request)->update(['name' => trim($request->validated('name'))]);

        return back()->with('status', __('face.dashboard.name_saved'));
    }

    /** Once per day at any time; a second tap the same day changes nothing. */
    public function attend(Request $request): RedirectResponse
    {
        $account = $this->account($request);
        if ($account->name === null) {
            return back();
        }

        $attendance = $account->attendances()->firstOrCreate(['date' => today()]);

        return back()->with('status', __($attendance->wasRecentlyCreated ? 'face.dashboard.attended' : 'face.dashboard.already_attended'));
    }

    public function clearAttendance(Request $request): RedirectResponse
    {
        $this->account($request)->attendances()->delete();

        return back()->with('status', __('face.dashboard.attendance_cleared'));
    }

    /** Hard delete: the face template, the name and every attendance row (cascade). */
    public function destroy(Request $request): RedirectResponse
    {
        $account = $this->account($request);
        $this->signOut($request);
        $account->delete();

        return redirect()->route('face.login')->with('status', __('face.dashboard.account_deleted'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->signOut($request);

        return redirect()->route('face.login');
    }

    private function account(Request $request): FaceTemplate
    {
        /** @var FaceTemplate */
        return $request->user();
    }

    private function signOut(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
