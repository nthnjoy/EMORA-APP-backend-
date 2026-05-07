<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MoodCategory;
use App\Models\FeelingCategory;

class MoodCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        MoodCategory::truncate();
        FeelingCategory::truncate();

        $moods = [
            [
                'name' => 'Senang',
                'label' => 'Senang',
                'description' => 'Rasa Senang adalah reaksi emosional terhadap pencapaian tujuan atau pengalaman yang menyenangkan, yang memotivasi individu untuk mengulangi perilaku yang menimbulkan kepuasan tersebut.',
                'icon_name' => 'senang.png',
                'gradient_start' => '#FFF2B2',
                'gradient_end' => '#F3C766',
                'text_color' => '#8C6415',
            ],
            [
                'name' => 'Antusias',
                'label' => 'Antusias',
                'description' => 'Rasa Antusias adalah perasaan gairah atau minat yang intens terhadap aktivitas tertentu, yang berfungsi memfokuskan perhatian dan meningkatkan keterlibatan dalam aktivitas tersebut.',
                'icon_name' => 'antusias.png',
                'gradient_start' => '#F6C884',
                'gradient_end' => '#C8873B',
                'text_color' => '#6B451A',
            ],
            [
                'name' => 'Netral',
                'label' => 'Netral',
                'description' => 'Rasa Netral adalah pengalaman emosional yang tidak memicu respons fisiologis atau perilaku yang spesifik.',
                'icon_name' => 'biasa.png',
                'gradient_start' => '#E2F6E2',
                'gradient_end' => '#8DE191',
                'text_color' => '#2C6D30',
            ],
            [
                'name' => 'Terkejut',
                'label' => 'Terkejut',
                'description' => 'Rasa Terkejut adalah emosi yang bersifat singkat dan intens, yang dapat bertransformasi menjadi emosi lain (misal takut atau senang), tergantung konteks stimulus.',
                'icon_name' => 'terkejut.png',
                'gradient_start' => '#ECD8FB',
                'gradient_end' => '#B878EE',
                'text_color' => '#5E2E88',
            ],
            [
                'name' => 'Sedih',
                'label' => 'Sedih',
                'description' => 'Rasa Sedih adalah emosi yang ditandai dengan perasaan duka, kesedihan, atau duka cita terhadap suatu kehilangan.',
                'icon_name' => 'sedih.png',
                'gradient_start' => '#CED9FA',
                'gradient_end' => '#86A3F3',
                'text_color' => '#2B4791',
            ],
            [
                'name' => 'Takut',
                'label' => 'Takut',
                'description' => 'Rasa Takut adalah emosi yang muncul sebagai respons terhadap ancaman atau bahaya yang dirasakan.',
                'icon_name' => 'takut.png',
                'gradient_start' => '#D0D8E1',
                'gradient_end' => '#9CA6B2',
                'text_color' => '#3B4856',
            ],
            [
                'name' => 'Marah',
                'label' => 'Marah',
                'description' => 'Rasa Marah adalah reaksi emosional negatif yang menunjukkan ketidakpuasan atau frustasi terhadap situasi tertentu.',
                'icon_name' => 'marah.png',
                'gradient_start' => '#F9CDCD',
                'gradient_end' => '#DF7B7B',
                'text_color' => '#7A2929',
            ],
        ];

        foreach ($moods as $mood) {
            MoodCategory::create($mood);
        }

        $feelings = [
            // Senang
            ['mood_name' => 'Senang', 'name' => 'Gembira', 'description' => 'Gembira adalah emosi menyenangkan yang muncul saat kita mengalami sesuatu yang positif, mencapai tujuan, atau merasa terhubung dengan orang lain.'],
            ['mood_name' => 'Senang', 'name' => 'Bangga', 'description' => 'Bangga adalah perasaan puas dan senang atas pencapaian diri sendiri atau orang yang kita sayangi.'],
            ['mood_name' => 'Senang', 'name' => 'Bersyukur', 'description' => 'Bersyukur adalah perasaan menghargai hal-hal baik yang ada dalam hidup kita, baik besar maupun kecil.'],
            ['mood_name' => 'Senang', 'name' => 'Ceria', 'description' => 'Ceria adalah perasaan ringan dan bahagia yang membuat kita ingin tersenyum dan menikmati momen saat ini.'],
            // Antusias
            ['mood_name' => 'Antusias', 'name' => 'Semangat', 'description' => 'Semangat adalah dorongan kuat dari dalam diri untuk melakukan sesuatu dengan penuh energi dan tekad.'],
            ['mood_name' => 'Antusias', 'name' => 'Energik', 'description' => 'Energik adalah perasaan penuh tenaga dan vitalitas yang membuat kita siap menghadapi tantangan.'],
            ['mood_name' => 'Antusias', 'name' => 'Kagum', 'description' => 'Kagum adalah perasaan takjub dan menghargai sesuatu yang luar biasa atau mengagumkan.'],
            ['mood_name' => 'Antusias', 'name' => 'Bergairah', 'description' => 'Bergairah adalah perasaan antusias yang kuat terhadap sesuatu yang sangat kita minati.'],
            // Netral
            ['mood_name' => 'Netral', 'name' => 'Biasa Saja', 'description' => 'Biasa saja adalah kondisi emosi yang stabil tanpa perasaan positif atau negatif yang dominan.'],
            ['mood_name' => 'Netral', 'name' => 'Stabil', 'description' => 'Stabil adalah kondisi emosi yang seimbang dan terkendali tanpa fluktuasi yang berarti.'],
            ['mood_name' => 'Netral', 'name' => 'Tenang', 'description' => 'Tenang adalah perasaan damai dan rileks yang membuat pikiran dan tubuh terasa nyaman.'],
            ['mood_name' => 'Netral', 'name' => 'Santai', 'description' => 'Santai adalah perasaan bebas dari tekanan atau stres, menikmati momen dengan ringan.'],
            // Terkejut
            ['mood_name' => 'Terkejut', 'name' => 'Tercengang', 'description' => 'Tercengang adalah perasaan sangat terkejut hingga sulit berkata-kata atau bereaksi.'],
            ['mood_name' => 'Terkejut', 'name' => 'Penasaran', 'description' => 'Penasaran adalah dorongan kuat untuk mengetahui atau memahami sesuatu yang belum diketahui.'],
            ['mood_name' => 'Terkejut', 'name' => 'Tertarik', 'description' => 'Tertarik adalah perasaan ingin tahu lebih dalam tentang sesuatu yang menarik perhatian.'],
            ['mood_name' => 'Terkejut', 'name' => 'Gelagapan', 'description' => 'Gelagapan adalah perasaan bingung dan gugup karena sesuatu yang tidak terduga.'],
            // Sedih
            ['mood_name' => 'Sedih', 'name' => 'Pilu', 'description' => 'Pilu adalah perasaan sedih yang mendalam yang membuat hati terasa berat dan ingin menangis.'],
            ['mood_name' => 'Sedih', 'name' => 'Depresi', 'description' => 'Depresi adalah perasaan sedih berkepanjangan yang mempengaruhi motivasi dan semangat hidup.'],
            ['mood_name' => 'Sedih', 'name' => 'Kesepian', 'description' => 'Kesepian adalah perasaan hampa karena kurangnya koneksi sosial atau kedekatan emosional.'],
            ['mood_name' => 'Sedih', 'name' => 'Putus Asa', 'description' => 'Putus asa adalah perasaan kehilangan harapan dan merasa tidak ada jalan keluar dari masalah.'],
            // Takut
            ['mood_name' => 'Takut', 'name' => 'Cemas', 'description' => 'Cemas adalah perasaan khawatir berlebihan tentang sesuatu yang mungkin terjadi di masa depan.'],
            ['mood_name' => 'Takut', 'name' => 'Khawatir', 'description' => 'Khawatir adalah perasaan gelisah ringan tentang kemungkinan hasil yang tidak diinginkan.'],
            ['mood_name' => 'Takut', 'name' => 'Panik', 'description' => 'Panik adalah perasaan takut yang intens dan tiba-tiba yang membuat sulit berpikir jernih.'],
            ['mood_name' => 'Takut', 'name' => 'Gelisah', 'description' => 'Gelisah adalah perasaan tidak tenang yang membuat sulit untuk diam dan rileks.'],
            // Marah
            ['mood_name' => 'Marah', 'name' => 'Kesal', 'description' => 'Kesal adalah perasaan tidak nyaman yang muncul saat sesuatu tidak berjalan sesuai harapan.'],
            ['mood_name' => 'Marah', 'name' => 'Jengkel', 'description' => 'Jengkel adalah rasa terganggu yang terus-menerus akibat situasi atau perilaku yang mengganggu.'],
            ['mood_name' => 'Marah', 'name' => 'Benci', 'description' => 'Benci adalah perasaan negatif yang kuat terhadap seseorang atau sesuatu yang dianggap merugikan.'],
            ['mood_name' => 'Marah', 'name' => 'Kecewa', 'description' => 'Kecewa adalah perasaan sedih karena harapan atau ekspektasi yang tidak terpenuhi.'],
        ];

        foreach ($feelings as $feeling) {
            FeelingCategory::create($feeling);
        }
    }
}
