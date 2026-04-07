<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Resources\SettingResource;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends ApiController
{
    public function index()
    {
        $settings = Setting::query()->orderBy('key')->get();

        return $this->success(SettingResource::collection($settings)->resolve());
    }

    public function update(UpdateSettingsRequest $request)
    {
        foreach ($request->validated('settings') as $key => $value) {
            Setting::setValue((string) $key, $value);
        }

        ActivityLog::record(
            $request->user(),
            'settings.updated',
            Setting::class,
            null,
            ['keys' => array_keys($request->validated('settings'))],
            $request
        );

        return $this->success(
            SettingResource::collection(Setting::query()->orderBy('key')->get())->resolve(),
            'Settings berhasil diperbarui.'
        );
    }
}
