<?php

namespace ME\Utility\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use ME\Http\Controllers\Controller;
use ME\Utility\Models\AdmsSetting;

class AdmsController extends Controller
{
    public function settings()
    {
        $settings = AdmsSetting::instance();

        return view('utility::adms.settings', [
            'settings'  => $settings,
            'isRunning' => $settings->isRunning(),
            'deviceUrl' => $settings->deviceUrl(),
        ]);
    }

    public function saveConfig(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'api_base_url'       => ['required', 'url', 'max:500'],
            'api_token'          => ['nullable', 'string', 'max:500'],
            'fetch_employee_url' => ['required', 'url', 'max:500'],
            'sync_attendance_url'=> ['required', 'url', 'max:500'],
        ]);

        AdmsSetting::instance()->update($data);

        return back()->with('config_success', 'Configuration saved.');
    }

    public function testConnection(): JsonResponse
    {
        $settings = AdmsSetting::instance();

        if (empty($settings->api_base_url)) {
            return response()->json(['ok' => false, 'message' => 'API Base URL is not configured.']);
        }

        try {
            $response = Http::timeout(5)->get(rtrim($settings->api_base_url, '/'));

            return response()->json([
                'ok'      => $response->successful(),
                'status'  => $response->status(),
                'message' => $response->successful()
                    ? 'Connection successful (HTTP ' . $response->status() . ')'
                    : 'Server responded with HTTP ' . $response->status(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function saveSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_sync_cron'    => ['required', 'string', 'max:100'],
            'attendance_fetch_cron' => ['required', 'string', 'max:100'],
        ]);

        AdmsSetting::instance()->update($data);

        return back()->with('schedule_success', 'Schedule saved.');
    }
}
