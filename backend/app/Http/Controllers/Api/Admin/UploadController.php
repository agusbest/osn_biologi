<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LessonResource;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /** Hanya gambar raster (tanpa SVG, mencegah XSS), maks 2 MB. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'mimetypes:image/jpeg,image/png,image/webp,image/gif', 'max:2048'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'alt_text' => ['required', 'string', 'max:255'], // alt text wajib (aksesibilitas)
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads/' . date('Y/m'), 'public'); // nama acak, bukan nama asli
        $res = LessonResource::create([
            'lesson_id' => $data['lesson_id'] ?? null, 'type' => 'image', 'path' => $path,
            'alt_text' => $data['alt_text'], 'caption' => $data['caption'] ?? null, 'size' => $file->getSize(),
        ]);
        AuditLogger::log($request->user(), 'upload.created', $res);

        return response()->json([
            'id' => $res->id, 'path' => $path, 'url' => Storage::disk('public')->url($path), 'alt_text' => $res->alt_text,
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $res = LessonResource::findOrFail($id);
        Storage::disk('public')->delete($res->path);
        $res->delete();
        AuditLogger::log($request->user(), 'upload.deleted', $res);

        return response()->json(['ok' => true]);
    }
}
