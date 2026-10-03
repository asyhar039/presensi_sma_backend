<?php

namespace App\Enums\Enums;

enum SubjectEnums: int
{
    case KEPALA_SEKOLAH = 1;
    case EKONOMI = 2;
    case GEOGRAFI_TINGKAT_LANJUT = 3;
    case KIMIA_TINGKAT_LANJUT = 4;
    case BIOLOGI_TINGKAT_LANJUT = 5;
    case BK = 6;
    case MATEMATIKA = 7;
    case BAHASA_INGGRIS = 8;
    case EKONOMI_TINGKAT_LANJUT = 9;
    case PENDIDIKAN_AGAMA_KATOLIK = 10;
    case PENDIDIKAN_PANCASILA = 11;
    case GEOGRAFI = 12;
    case BIOLOGI = 13;
    case SENI_PRAKARYA_MUSIK = 14;
    case BAHASA_INDONESIA = 15;
    case SEJARAH = 16;
    case PJOK = 17;
    case SENI_BUDAYA_SENI_PRAKARYA = 18;
    case SEJARAH_TINGKAT_LANJUT = 19;
    case SOSIOLOGI_TINGKAT_LANJUT = 20;
    case PENDIDIKAN_AGAMA_KRISTEN = 21;
    case BAHASA_JERMAN_TINGKAT_LANJUT = 22;
    case PENDIDIKAN_AGAMA_ISLAM = 23;
    case INFORMATIKA = 24;
    case INFORMATIKA_TINGKAT_LANJUT = 25;
    case PENDIDIKAN_AGAMA_BUDHA = 26;
    case PENDIDIKAN_AGAMA_HINDU = 27;
    case BAHASA_JAWA = 28;
    case FISIKA_TINGKAT_LANJUT = 29;
    case FISIKA = 30;
    case SOSIOLOGI = 31;
    case KIMIA = 32;
    case MATEMATIKA_TINGKAT_LANJUT = 33;

    public function label(): string
    {
        return match ($this) {
            self::KEPALA_SEKOLAH => 'Kepala Sekolah',
            self::EKONOMI => 'Ekonomi',
            self::GEOGRAFI_TINGKAT_LANJUT => 'Geografi Tingkat Lanjut',
            self::KIMIA_TINGKAT_LANJUT => 'Kimia Tingkat Lanjut',
            self::BIOLOGI_TINGKAT_LANJUT => 'Biologi Tingkat Lanjut',
            self::BK => 'BK',
            self::MATEMATIKA => 'Matematika',
            self::BAHASA_INGGRIS => 'Bahasa Inggris',
            self::EKONOMI_TINGKAT_LANJUT => 'Ekonomi Tingkat Lanjut',
            self::PENDIDIKAN_AGAMA_KATOLIK => 'Pendidikan Agama Katolik',
            self::PENDIDIKAN_PANCASILA => 'Pendidikan Pancasila',
            self::GEOGRAFI => 'Geografi',
            self::BIOLOGI => 'Biologi',
            self::SENI_PRAKARYA_MUSIK => 'Seni & Prakarya (S Musik)',
            self::BAHASA_INDONESIA => 'Bahasa Indonesia',
            self::SEJARAH => 'Sejarah',
            self::PJOK => 'PJOK',
            self::SENI_BUDAYA_SENI_PRAKARYA => 'Seni Budaya, Seni & Prakarya',
            self::SEJARAH_TINGKAT_LANJUT => 'Sejarah Tingkat Lanjut',
            self::SOSIOLOGI_TINGKAT_LANJUT => 'Sosiologi Tingkat Lanjut',
            self::PENDIDIKAN_AGAMA_KRISTEN => 'Pendidikan Agama Kristen',
            self::BAHASA_JERMAN_TINGKAT_LANJUT => 'Bahasa Jerman Tingkat Lanjut',
            self::PENDIDIKAN_AGAMA_ISLAM => 'Pendidikan Agama Islam',
            self::INFORMATIKA => 'Informatika',
            self::INFORMATIKA_TINGKAT_LANJUT => 'Informatika Tingkat Lanjut',
            self::PENDIDIKAN_AGAMA_BUDHA => 'Pendidikan Agama Budha',
            self::PENDIDIKAN_AGAMA_HINDU => 'Pendidikan Agama Hindu',
            self::BAHASA_JAWA => 'Bahasa Jawa',
            self::FISIKA_TINGKAT_LANJUT => 'Fisika Tingkat Lanjut',
            self::FISIKA => 'Fisika',
            self::SOSIOLOGI => 'Sosiologi',
            self::KIMIA => 'Kimia',
            self::MATEMATIKA_TINGKAT_LANJUT => 'Matematika Tingkat Lanjut',
        };
    }

    public function keyLabel(): array
    {
        return [
            'key' => $this->value,
            'label' => $this->label(),
        ];
    }
}
