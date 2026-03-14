<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminProfileRequest;
use App\Http\Requests\Admin\SiteSettingRequest;
use App\Services\NotificationService;
use App\Services\ProfileImageService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(Request $request, SettingService $settingService): View
    {
        return view('admin.settings.edit', [
            'settings' => $settingService->getSettings(),
            'admin' => $request->user('admin'),
        ]);
    }

    public function update(SiteSettingRequest $request, SettingService $settingService): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $currentSettings = $settingService->getSettings();

            $siteName = trim((string) ($validated['site_name'] ?? ''));

            $logoPath = ProfileImageService::upload(
                $request->file('site_logo'),
                'site-settings',
                $currentSettings['site_logo'] ?? null,
                $siteName !== '' ? $siteName : 'site_logo'
            );

            $settingService->saveSettings([
                'site_name' => $siteName,
                'site_email' => trim((string) ($validated['site_email'] ?? '')),
                'site_phone' => trim((string) ($validated['site_phone'] ?? '')),
                'site_address' => trim((string) ($validated['site_address'] ?? '')),
                'site_logo' => $logoPath,
            ]);

            NotificationService::UPDATED('Site settings updated successfully.');

            return redirect()->route('admin.settings.edit');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR('Unable to update site settings. Please try again.');

            return back()->withInput();
        }
    }

    public function updateProfile(AdminProfileRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $admin = $request->user('admin');

            $name = trim((string) ($validated['name'] ?? ''));

            $payload = [
                'name' => $name,
                'email' => trim((string) ($validated['email'] ?? '')),
                'photo' => ProfileImageService::upload(
                    $request->file('photo'),
                    'admins',
                    $admin?->photo,
                    $name !== '' ? $name : 'admin'
                ),
            ];

            if (! empty($validated['password'])) {
                $payload['password'] = $validated['password'];
            }

            $admin?->update($payload);

            NotificationService::UPDATED('Admin profile updated successfully.');

            return redirect()->route('admin.settings.edit');
        } catch (\Throwable $throwable) {
            report($throwable);
            NotificationService::ERROR('Unable to update admin profile. Please try again.');

            return back()->withInput();
        }
    }
}
