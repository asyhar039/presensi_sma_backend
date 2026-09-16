<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjectNames = [
            // Kelompok Umum (Muatan Nasional)
            'Pendidikan Agama dan Budi Pekerti',
            'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
            'Bahasa Indonesia',
            'Matematika (Wajib)',
            'Sejarah Indonesia',
            'Bahasa Inggris',
            'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)',
            'Informatika / TIK',
            'Seni Budaya',
            'Prakarya dan Kewirausahaan (PKWU)',

            // MIPA (Matematika dan Ilmu Pengetahuan Alam)
            'Matematika Tingkat Lanjut',
            'Fisika',
            'Biologi',
            'Kimia',

            // IPS (Ilmu Pengetahuan Sosial)
            'Sosiologi',
            'Ekonomi',
            'Geografi',
            'Sejarah Tingkat Lanjut',

            // Bahasa dan Budaya
            'Bahasa dan Sastra Indonesia',
            'Bahasa dan Sastra Inggris',
            'Bahasa Asing (Jepang / Mandarin / Jerman)',

            // Kelas Khusus Olahraga (KKO) & Pengembangan
            'Teori dan Praktik Cabang Olahraga Utama',
            'Anatomi dan Fisiologi Olahraga',
            'Kebugaran Jasmani dan Manajemen Olahraga',

            // Muatan Lokal & Kebudayaan
            'Bahasa Jawa',
            'Bimbingan dan Konseling (BK)',
            'Projek Penguatan Profil Pelajar Pancasila (P5)',
        ];

        $now = now();

        $subjects = array_map(fn ($name) => [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], $subjectNames);

        Subject::insert($subjects);
    }
}
