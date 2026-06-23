<?php

namespace ME\Utility\Models;

use Illuminate\Database\Eloquent\Model;

class AdmsSetting extends Model
{
    protected $table = 'adms_settings';

    protected $fillable = [
        'api_base_url',
        'api_token',
        'fetch_employee_url',
        'sync_attendance_url',
        'employee_sync_cron',
        'attendance_fetch_cron',
        'adms_port',
        'adms_protocol',
    ];

    // Always work with the single row — create on first access
    public static function instance(): static
    {
        return static::firstOrCreate([], [
            'api_base_url'          => '',
            'api_token'             => null,
            'fetch_employee_url'    => '',
            'sync_attendance_url'   => '',
            'employee_sync_cron'    => '0 * * * *',
            'attendance_fetch_cron' => '*/15 * * * *',
            'adms_port'             => 5015,
            'adms_protocol'         => 'HTTP Push',
        ]);
    }

    // Check if the ADMS server port is currently open/listening
    public function isRunning(): bool
    {
        $connection = @fsockopen('127.0.0.1', $this->adms_port, $errno, $errstr, 1);

        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }

        return false;
    }

    public function deviceUrl(): string
    {
        return 'http://' . request()->getHost() . ':' . $this->adms_port;
    }
}
