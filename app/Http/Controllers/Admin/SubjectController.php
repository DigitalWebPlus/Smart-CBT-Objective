<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Subject;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SubjectController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::query()
            ->withCount('questions')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        return view('admin.subjects.create', [
            'subject' => new Subject(),
        ]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        try {
            Subject::create($request->validated());
            NotificationService::CREATED('Subject created successfully.');

            return redirect()->route('admin.subjects.index');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();

            return back()->withInput();
        }
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        try {
            $subject->update($request->validated());
            NotificationService::UPDATED('Subject updated successfully.');

            return redirect()->route('admin.subjects.index');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();

            return back()->withInput();
        }
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        try {
            $subject->delete();
            NotificationService::DELETED('Subject removed successfully.');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR();
        }

        return redirect()->route('admin.subjects.index');
    }
}
