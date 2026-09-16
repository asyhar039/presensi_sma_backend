<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roomNames = [
            // Manajemen & Administrasi
            'Ruang Kepala Sekolah',
            'Ruang Wakil Kepala Sekolah',
            'Ruang Guru',
            'Ruang Tata Usaha (TU)',
            'Ruang Bimbingan Konseling (BK)',
            'Ruang Piket Guru',
            'Ruang Server / ICT',

            // Kelas X (Sepuluh)
            'Ruang Kelas X IPA 1', 'Ruang Kelas X IPA 2', 'Ruang Kelas X IPA 3', 'Ruang Kelas X IPA 4', 'Ruang Kelas X IPA 5', 'Ruang Kelas X IPA 6',
            'Ruang Kelas X IPS 1', 'Ruang Kelas X IPS 2', 'Ruang Kelas X IPS 3', 'Ruang Kelas X IPS 4',
            'Ruang Kelas X Bahasa 1', 'Ruang Kelas X Bahasa 2',
            'Ruang Kelas X KKO 1',

            // Kelas XI (Sebelas)
            'Ruang Kelas XI IPA 1', 'Ruang Kelas XI IPA 2', 'Ruang Kelas XI IPA 3', 'Ruang Kelas XI IPA 4', 'Ruang Kelas XI IPA 5', 'Ruang Kelas XI IPA 6',
            'Ruang Kelas XI IPS 1', 'Ruang Kelas XI IPS 2', 'Ruang Kelas XI IPS 3', 'Ruang Kelas XI IPS 4',
            'Ruang Kelas XI Bahasa 1', 'Ruang Kelas XI Bahasa 2',
            'Ruang Kelas XI KKO 1',

            // Kelas XII (Dua Belas)
            'Ruang Kelas XII IPA 1', 'Ruang Kelas XII IPA 2', 'Ruang Kelas XII IPA 3', 'Ruang Kelas XII IPA 4', 'Ruang Kelas XII IPA 5', 'Ruang Kelas XII IPA 6',
            'Ruang Kelas XII IPS 1', 'Ruang Kelas XII IPS 2', 'Ruang Kelas XII IPS 3', 'Ruang Kelas XII IPS 4',
            'Ruang Kelas XII Bahasa 1', 'Ruang Kelas XII Bahasa 2',
            'Ruang Kelas XII KKO 1',

            // Laboratorium & Praktikum
            'Laboratorium Fisika',
            'Laboratorium Biologi',
            'Laboratorium Kimia',
            'Laboratorium Komputer 1',
            'Laboratorium Komputer 2',
            'Laboratorium Komputer 3',
            'Laboratorium Bahasa',
            'Laboratorium IPS / Geografi',
            'Laboratorium Seni & Budaya',

            // Fasilitas Pembelajaran & Sumber Belajar
            'Perpustakaan Utama',
            'Ruang Baca Outdoor / Gazebo Baca',
            'Ruang Multi Media',
            'Aula Utama',
            'Ruang Band / Musik',
            'Ruang Karawitan',
            'Ruang Teater / Sanggar Seni',

            // Keorganisasian & Ekstrakurikuler
            'Ruang OSIS',
            'Ruang MPK',
            'Ruang Pramuka',
            'Ruang PMR (Palang Merah Remaja)',
            'Ruang Paskibra',
            'Ruang KIR (Karya Ilmiah Remaja)',
            'Ruang Pecinta Alam',

            // Kesehatan, Keagamaan & Olahraga
            'Ruang UKS Putra',
            'Ruang UKS Putri',
            'Masjid / Musholla Utama',
            'Ruang wudhu & Perlengkapan Masjid',
            'Gudang Alat Olahraga',
            'Ruang Fitness / Gym Sekolah',
            'Ruang Ganti Olahraga Putra',
            'Ruang Ganti Olahraga Putri',

            // Penunjang & Umum
            'Koperasi Sekolah',
            'Kantin Utama / Food Court',
            'Ruang Security / Satpam',
            'Gudang Utama',
            'Gudang Kebersihan',
            'Ruang Parkir Guru & Karyawan',
            'Toilet Guru',
            'Toilet Siswa Putra A',
            'Toilet Siswa Putra B',
            'Toilet Siswa Putra C',
            'Toilet Siswa Putra D',
            'Toilet Siswa Putra E',
            'Toilet Siswa Putri A',
            'Toilet Siswa Putri B',
            'Toilet Siswa Putri C',
            'Toilet Siswa Putri D',
            'Toilet Siswa Putri E',
        ];

        $now = now();

        $rooms = array_map(fn ($name) => [
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], $roomNames);

        Room::insert($rooms);
    }
}
