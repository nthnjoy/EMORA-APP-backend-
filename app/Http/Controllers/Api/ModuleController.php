<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::all()->map(function ($module) {
            $data = $module->toArray();

            // Gunakan API route untuk content_url agar terhindar dari masalah 403
            if (!empty($data['content_url'])) {
                if (!str_starts_with($data['content_url'], 'http')) {
                    // Path di DB: modules/content/xxx.pdf
                    // Jadi URL: http://.../api/modules/view-pdf/modules/content/xxx.pdf
                    $data['content_url'] = url('api/modules/view-pdf/' . $data['content_url']);
                }
            }

            // Thumbnail biasanya gambar, tetap pakai storage/ (jika gambar OK)
            if (!empty($data['thumbnail_url'])) {
                if (!str_starts_with($data['thumbnail_url'], 'http')) {
                    $data['thumbnail_url'] = url('storage/' . $data['thumbnail_url']);
                }
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $modules,
        ]);
    }

    public function viewPdf($path)
    {
        // Cari file di storage/app/public/
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File tidak ditemukan di storage/app/public/' . $path);
        }

        $file = Storage::disk('public')->get($path);
        $type = Storage::disk('public')->mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $type)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
    }

    public function upload(Request $request, $id)
    {
        $module = Module::findOrFail($id);

        if ($request->hasFile('pdf')) {
            $pdfPath = $request->file('pdf')->store("modules/content", 'public');
            $module->content_url = $pdfPath;
        }

        if ($request->hasFile('thumbnail')) {
            $thumbPath = $request->file('thumbnail')->store("modules/thumbnails", 'public');
            $module->thumbnail_url = $thumbPath;
        }

        $module->save();

        return response()->json([
            'success' => true,
            'message' => 'File berhasil diupload.',
            'data' => [
                'content_url'   => $module->content_url ? url('api/modules/view-pdf/' . $module->content_url) : null,
                'thumbnail_url' => $module->thumbnail_url ? url('storage/' . $module->thumbnail_url) : null,
            ],
        ]);
    }
}
