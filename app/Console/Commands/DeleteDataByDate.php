<?php

namespace App\Console\Commands;

use App\Models\Mood;
use App\Models\Story;
use Illuminate\Console\Command;

class DeleteDataByDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:data-by-date {date : Tanggal yang ingin dihapus, format YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus data mood dan story berdasarkan tanggal tertentu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');
        
        // Validasi format tanggal
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error('Format tanggal salah! Gunakan format: YYYY-MM-DD');
            return 1;
        }

        // Confirm before deleting
        if (! $this->confirm("Apakah Anda yakin ingin menghapus semua data dari tanggal {$date}?")) {
            $this->info('Pembatalan. Data tidak dihapus.');
            return 0;
        }

        try {
            // Buat date range untuk filter MongoDB
            $startOfDay = \Carbon\Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            $endOfDay = \Carbon\Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

            // Hapus Moods
            $moodCount = Mood::whereBetween('created_at', [$startOfDay, $endOfDay])->delete();
            $this->info("Dihapus {$moodCount} data Mood dari tanggal {$date}");

            // Hapus Stories
            $storyCount = Story::whereBetween('created_at', [$startOfDay, $endOfDay])->delete();
            $this->info("Dihapus {$storyCount} data Story dari tanggal {$date}");

            $this->info("✓ Total data dihapus: " . ($moodCount + $storyCount) . " record");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error saat menghapus data: " . $e->getMessage());
            return 1;
        }
    }
}
