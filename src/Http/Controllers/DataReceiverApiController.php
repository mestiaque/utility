<?php

namespace ME\Utility\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ME\Http\Controllers\Controller;
use ME\Utility\Services\DataReceiverService;

/**
 * Public endpoint — no auth, every method, every content-type.
 */
class DataReceiverApiController extends Controller
{
    public function __construct(private readonly DataReceiverService $service) {}

    public function receive(Request $request): JsonResponse
    {
        try {
            $log = $this->service->process($request);

            return response()->json([
                'success'     => true,
                'message'     => 'Data received and stored successfully.',
                'log_id'      => $log->uuid,
                'received_at' => $log->received_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('UDR: failed to process request', [
                'error'  => $e->getMessage(),
                'method' => $request->method(),
                'url'    => $request->fullUrl(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to store request.'], 500);
        }
    }
}
