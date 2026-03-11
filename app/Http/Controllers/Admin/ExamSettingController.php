<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamSettingRequest;
use App\Services\NotificationService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamSettingController extends Controller
{
    public function edit(SettingService $settingService): View
    {
        $settings = $settingService->getSettings();

        return view('admin.exam-settings.edit', [
            'sections' => $this->sections(),
            'fields' => $this->fieldDefinitions(),
            'settings' => $settings,
        ]);
    }

    public function update(ExamSettingRequest $request, SettingService $settingService): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $values = [];

            foreach ($this->fieldDefinitions() as $fieldKey => $fieldDefinition) {
                $values[$fieldKey] = (string) ($validated[$fieldKey] ?? $fieldDefinition['default'] ?? '');
            }

            $settingService->saveSettings($values);

            NotificationService::UPDATED('Exam settings updated successfully.');

            return redirect()->route('admin.exam-settings.edit');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR('Unable to update exam settings. Please try again.');

            return back()->withInput();
        }
    }

    private function sections(): array
    {
        return config('exam-settings.sections', []);
    }

    private function fieldDefinitions(): array
    {
        return config('exam-settings.fields', []);
    }
}
