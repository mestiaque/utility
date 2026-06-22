<?php

namespace ME\Utility\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ME\Http\Controllers\Controller;
use ME\Utility\Models\UdrDataLog;
use ME\Utility\Models\UdrUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataReceiverController extends Controller
{
    // ── API Info page ──────────────────────────────────────────────────────────

    public function index()
    {
        $endpoint = url('/api/receive');

        $stats = [
            'total'    => UdrDataLog::count(),
            'today'    => UdrDataLog::whereDate('received_at', today())->count(),
            'files'    => UdrUploadedFile::count(),
            'ips'      => UdrDataLog::distinct('ip_address')->count('ip_address'),
            'trend'    => UdrDataLog::selectRaw("date(received_at) as date, count(*) as total")
                            ->where('received_at', '>=', now()->subDays(14))
                            ->groupByRaw('date(received_at)')
                            ->orderBy('date')
                            ->get(),
            'top_types' => UdrDataLog::selectRaw('content_type, count(*) as total')
                            ->whereNotNull('content_type')
                            ->groupBy('content_type')
                            ->orderByDesc('total')
                            ->limit(5)->get(),
        ];

        $examples = [
            ['label' => 'JSON Body',    'method' => 'POST', 'curl' => "curl -X POST {$endpoint} \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\"device\":\"ATT-01\",\"user_id\":42,\"action\":\"check_in\"}'"],
            ['label' => 'Form Data',   'method' => 'POST', 'curl' => "curl -X POST {$endpoint} \\\n  -d \"device_id=SENSOR01&temp=36.5&status=ok\""],
            ['label' => 'File Upload', 'method' => 'POST', 'curl' => "curl -X POST {$endpoint} \\\n  -F \"photo=@face.jpg\" \\\n  -F \"device=camera-01\""],
            ['label' => 'GET / Ping',  'method' => 'GET',  'curl' => "curl \"{$endpoint}?sensor=A1&temp=36.5&status=ok\""],
            ['label' => 'XML',         'method' => 'POST', 'curl' => "curl -X POST {$endpoint} \\\n  -H \"Content-Type: application/xml\" \\\n  -d '<log><device>pos-001</device><event>sale</event></log>'"],
        ];

        return view('utility::data-receiver.index', compact('endpoint', 'stats', 'examples'));
    }

    // ── Logs ───────────────────────────────────────────────────────────────────

    public function logs(Request $request)
    {
        $filters = $request->only(['method', 'ip', 'content_type', 'keyword', 'date_from', 'date_to', 'has_files']);
        $logs    = UdrDataLog::with('uploadedFiles')
                    ->filter($filters)
                    ->latest('received_at')
                    ->paginate(25)
                    ->withQueryString();

        return view('utility::data-receiver.logs', compact('logs', 'filters'));
    }

    public function logShow(string $uuid)
    {
        $log = UdrDataLog::with('uploadedFiles')->where('uuid', $uuid)->firstOrFail();
        return view('utility::data-receiver.log-show', compact('log'));
    }

    public function logDestroy(string $uuid)
    {
        $log = UdrDataLog::with('uploadedFiles')->where('uuid', $uuid)->firstOrFail();

        foreach ($log->uploadedFiles as $file) {
            if ($file->storageExists()) Storage::disk($file->disk)->delete($file->storage_path);
            $file->delete();
        }
        $log->delete();

        return redirect()->route('ut.data-receiver.logs')->with('success', 'Log deleted.');
    }

    // ── Files ──────────────────────────────────────────────────────────────────

    public function files(Request $request)
    {
        $files = UdrUploadedFile::with('dataLog')
                    ->when($request->extension, fn($q, $v) => $q->where('extension', $v))
                    ->when($request->filename,  fn($q, $v) => $q->where('original_name', 'like', "%{$v}%"))
                    ->latest()
                    ->paginate(24)
                    ->withQueryString();

        return view('utility::data-receiver.files', compact('files'));
    }

    public function fileDownload(string $uuid)
    {
        $file = UdrUploadedFile::where('uuid', $uuid)->firstOrFail();
        abort_unless($file->storageExists(), 404, 'File not found on storage.');
        return Storage::disk($file->disk)->download($file->storage_path, $file->original_name);
    }

    public function fileDestroy(string $uuid)
    {
        $file    = UdrUploadedFile::where('uuid', $uuid)->firstOrFail();
        $logUuid = $file->dataLog?->uuid;
        if ($file->storageExists()) Storage::disk($file->disk)->delete($file->storage_path);
        $file->delete();

        if ($logUuid) {
            return redirect()->route('ut.data-receiver.log.show', $logUuid)->with('success', 'File deleted.');
        }
        return redirect()->route('ut.data-receiver.files')->with('success', 'File deleted.');
    }

    // ── Export ─────────────────────────────────────────────────────────────────

    public function exportCsv(Request $request)
    {
        $filters = $request->only(['method', 'ip', 'content_type', 'keyword', 'date_from', 'date_to']);

        $response = new StreamedResponse(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'UUID', 'Method', 'Path', 'IP', 'Content-Type', 'Size', 'Has Files', 'Received At']);
            UdrDataLog::filter($filters)->latest('received_at')->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id, $row->uuid, $row->method, $row->path,
                        $row->ip_address, $row->short_content_type,
                        $row->request_size, $row->has_files ? 'Yes' : 'No',
                        $row->received_at?->toIso8601String(),
                    ]);
                }
            });
            fclose($handle);
        });

        $filename = 'udr-logs-' . now()->format('Y-m-d-His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        return $response;
    }
}
