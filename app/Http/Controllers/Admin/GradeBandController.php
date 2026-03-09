<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GradeBandRequest;
use App\Models\GradeBand;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GradeBandController extends Controller
{
    public function index(): View
    {
        $bands = GradeBand::ordered()->get();

        return view('admin.grade-bands.index', compact('bands'));
    }

    public function store(GradeBandRequest $request): RedirectResponse
    {
        try {
            GradeBand::create($request->validated());
            NotificationService::CREATED('Grade band added.');

            return redirect()->route('admin.grade-bands.index');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();

            return back()->withInput();
        }
    }

    public function edit(GradeBand $gradeBand): View
    {
        return view('admin.grade-bands.edit', [
            'band' => $gradeBand,
        ]);
    }

    public function update(GradeBandRequest $request, GradeBand $gradeBand): RedirectResponse
    {
        try {
            $gradeBand->update($request->validated());
            NotificationService::UPDATED('Grade band updated.');

            return redirect()->route('admin.grade-bands.index');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();

            return back()->withInput();
        }
    }

    public function destroy(GradeBand $gradeBand): RedirectResponse
    {
        try {
            $gradeBand->delete();
            NotificationService::DELETED('Grade band removed.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        return redirect()->route('admin.grade-bands.index');
    }
}
