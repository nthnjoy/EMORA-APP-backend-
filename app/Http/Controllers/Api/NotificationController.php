<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || empty($user->nim)) {
            return response()->json([
                'success' => false,
                'message' => 'NIM user tidak ditemukan.',
            ], 400);
        }

        // Normalisasi nim: cari dengan string DAN integer untuk toleransi perbedaan format
        // antara data yang dikirim PA3 dashboard vs yang disimpan mobile.
        $nimString = (string) $user->nim;
        $nimVariants = array_unique([$nimString, is_numeric($nimString) ? (int) $nimString : $nimString]);

        // Fetch notifications for this user that are not read yet ('belum')
        $notifications = Notification::query()
            ->whereIn('nim', $nimVariants)
            ->where('status', 'belum')
            ->orderByDesc('created_at')
            ->get();

        $data = $notifications->map(function (Notification $notification) {
            return [
                'id'         => (string) $notification->getKey(),
                'nim'        => (string) ($notification->nim ?? ''),
                'pesan'      => (string) ($notification->pesan ?? ''),
                'status'     => (string) ($notification->status ?? 'belum'),
                'created_at' => $notification->created_at?->toISOString(),
                'updated_at' => $notification->updated_at?->toISOString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user || empty($user->nim)) {
            return response()->json([
                'success' => false,
                'message' => 'NIM user tidak ditemukan.',
            ], 400);
        }

        $notification = Notification::query()
            ->where('_id', $id)
            ->where('nim', (string) $user->nim)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        $notification->status = 'sudah';
        $notification->save();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil ditandai sebagai dibaca.',
        ]);
    }
}
