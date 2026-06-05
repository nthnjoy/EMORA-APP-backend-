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
        // Tangani jika status disimpan sebagai boolean true, integer 1, atau string "1"
        $modules = Module::whereIn('status', [true, 1, '1'])->get()->map(function ($module) {
            $data = $module->toArray();

            // Tangani content_url: jika sudah URL lengkap (Cloudinary), langsung pakai
            if (!empty($data['content_url'])) {
                if (!str_starts_with($data['content_url'], 'http')) {
                    // Path lokal lama: modules/content/xxx.pdf
                    $data['content_url'] = url('api/modules/view-pdf/' . $data['content_url']);
                }
                // Jika sudah 'http...', langsung dipakai (URL Cloudinary)
            }

            // Field 'thumbnail' digunakan oleh TA-KEL-12 (bukan 'thumbnail_url')
            // Normalkan ke 'thumbnail_url' agar Flutter bisa membacanya
            $thumbValue = $data['thumbnail'] ?? $data['thumbnail_url'] ?? null;
            if (!empty($thumbValue)) {
                if (!str_starts_with($thumbValue, 'http')) {
                    // Path lokal lama
                    $data['thumbnail_url'] = url('storage/' . $thumbValue);
                } else {
                    // URL Cloudinary atau URL eksternal, langsung pakai
                    $data['thumbnail_url'] = $thumbValue;
                }
            } else {
                $data['thumbnail_url'] = null;
            }

            // Pemetaan field dari backend TA-KEL-12 ke format yang diharapkan Flutter
            $data['points'] = $data['points'] ?? $data['reward_point'] ?? 0;
            $data['category'] = $data['category'] ?? $data['kategori'] ?? 'Umum';
            $data['subtitle'] = $data['subtitle'] ?? $data['target_audiens'] ?? '';
            $data['content'] = $data['content'] ?? $data['description'] ?? '';
            $data['icon'] = $data['icon'] ?? '🧩';
            $data['color'] = $data['color'] ?? '0xFF6366F1';

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
