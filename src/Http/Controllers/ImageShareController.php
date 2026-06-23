<?php

namespace ME\Utility\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use ME\Http\Controllers\Controller;
use ME\Utility\Models\UtImage;

class ImageShareController extends Controller
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE_KB   = 5120;  // 5 MB
    private const MAX_DIMENSION = 1920;  // px — resize only if larger

    // ── Dashboard (authenticated) ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $images = UtImage::where('user_id', auth()->id())
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('utility::image-share.index', compact('images'));
    }

    // ── Upload ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:' . self::MAX_SIZE_KB,
            ],
        ]);

        $file = $request->file('image');

        // Second MIME check against real bytes (prevents extension spoofing)
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            return back()->withErrors(['image' => 'Invalid image type detected.']);
        }

        [$storedPath, $finalMime, $finalSize] = $this->processAndStore($file);

        UtImage::create([
            'user_id'       => auth()->id(),
            'original_name' => $file->getClientOriginalName(),
            'path'          => $storedPath,
            'disk'          => 'public',
            'mime_type'     => $finalMime,
            'size'          => $finalSize,
        ]);

        return back()->with('img_success', 'Image uploaded successfully.');
    }

    // ── Public image serve — NO AUTH ───────────────────────────────────────────

    public function show(string $uuid): Response
    {
        $image = UtImage::where('uuid', $uuid)->firstOrFail();

        abort_unless($image->storageExists(), 404, 'Image file not found.');

        $contents = Storage::disk($image->disk)->get($image->path);

        return response($contents, 200)
            ->header('Content-Type', $image->mime_type ?? 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    // ── Delete (owner only) ────────────────────────────────────────────────────

    public function destroy(string $uuid): RedirectResponse
    {
        $image = UtImage::where('uuid', $uuid)->firstOrFail();

        abort_unless((int) auth()->id() === (int) $image->user_id, 403);

        $image->deleteFile();
        $image->delete(); // soft delete

        return back()->with('img_success', 'Image deleted.');
    }

    // ── API: list ──────────────────────────────────────────────────────────────

    public function apiIndex(Request $request): JsonResponse
    {
        $images = UtImage::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $images->map(fn($img) => [
                'uuid'          => $img->uuid,
                'original_name' => $img->original_name,
                'public_url'    => $img->public_url,
                'size'          => $img->formatted_size,
                'mime_type'     => $img->mime_type,
                'created_at'    => $img->created_at->toIso8601String(),
            ]),
            'meta' => [
                'total'        => $images->total(),
                'current_page' => $images->currentPage(),
                'last_page'    => $images->lastPage(),
            ],
        ]);
    }

    // ── API: delete ────────────────────────────────────────────────────────────

    public function apiDestroy(string $uuid): JsonResponse
    {
        $image = UtImage::where('uuid', $uuid)->firstOrFail();

        abort_unless((int) auth()->id() === (int) $image->user_id, 403);

        $image->deleteFile();
        $image->delete();

        return response()->json(['success' => true, 'message' => 'Image deleted.']);
    }

    // ── Private: process & store ───────────────────────────────────────────────

    private function processAndStore($file): array
    {
        $filename = pathinfo($file->hashName(), PATHINFO_FILENAME) . '.jpg';
        $path     = 'ut-images/' . $filename;

        // Use Intervention Image v4 if available for resize + compress
        if (class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
            $processed = \Intervention\Image\Laravel\Facades\Image::read($file->getPathname())
                ->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION)
                ->toJpeg(quality: 85);

            Storage::disk('public')->put($path, $processed);

            return [$path, 'image/jpeg', strlen($processed)];
        }

        // Fallback: store original file as-is
        $originalExt  = $file->getClientOriginalExtension();
        $path         = 'ut-images/' . pathinfo($file->hashName(), PATHINFO_FILENAME) . '.' . $originalExt;
        Storage::disk('public')->put($path, file_get_contents($file->getPathname()));

        return [$path, $file->getMimeType(), $file->getSize()];
    }
}
