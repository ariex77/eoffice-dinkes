<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Memuat kode klasifikasi arsip dari database/data/klasifikasi_arsip.csv. Aman dijalankan berulang. */
class KlasifikasiArsipSeeder extends Seeder
{
    public function run(): void
    {
        $h = fopen(database_path('data/klasifikasi_arsip.csv'), 'r');
        fgetcsv($h, 0, ',', '"', '\\'); // lewati header

        $rows = [];
        while (($r = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
            if (count($r) >= 2 && trim($r[0]) !== '') {
                $rows[] = ['kode' => trim($r[0]), 'uraian' => trim($r[1])];
            }
        }
        fclose($h);

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('klasifikasi_arsip')->upsert($chunk, ['kode'], ['uraian']);
        }
    }
}
