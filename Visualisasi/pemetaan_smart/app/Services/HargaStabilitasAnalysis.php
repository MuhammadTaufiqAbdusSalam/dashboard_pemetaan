<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class HargaStabilitasAnalysis
{
    /**
     * Analisis statistik stabilitas harga untuk semua komoditas dalam 7 hari terakhir.
     * Menghitung: rata-rata harga, standar deviasi, koefisien variasi (CV),
     * perubahan harga harian, dan range harga.
     */
    public function analyzeAll(?string $tanggalAkhir = null): array
    {
        $tanggalAkhir = $tanggalAkhir ? Carbon::parse($tanggalAkhir) : Carbon::today();
        $tanggalAwal = $tanggalAkhir->copy()->subDays(6); // 7 hari termasuk hari ini

        // Ambil data harga per komoditas per kabupaten per tanggal (7 hari terakhir)
        $rawData = DB::table('komoditas as k')
            ->join('pasar as p', 'k.pasar_id', '=', 'p.id')
            ->join('kabupaten_kota as kk', 'p.kabupaten_kota_id', '=', 'kk.id')
            ->whereBetween('k.tanggal', [$tanggalAwal->format('Y-m-d'), $tanggalAkhir->format('Y-m-d')])
            ->where('k.harga', '>', 0)
            ->select(
                'kk.id as kabupaten_id',
                'kk.nama as kabupaten_nama',
                'k.komoditas_nama',
                'k.tanggal',
                DB::raw('AVG(k.harga) as avg_harga_harian')
            )
            ->groupBy('kk.id', 'kk.nama', 'k.komoditas_nama', 'k.tanggal')
            ->orderBy('k.komoditas_nama')
            ->orderBy('kk.nama')
            ->orderBy('k.tanggal')
            ->get();

        // Kelompokkan data per komoditas per kabupaten
        $grouped = $rawData->groupBy(function ($item) {
            return $item->komoditas_nama . '||' . $item->kabupaten_nama;
        });

        $hasilAnalisis = [];

        foreach ($grouped as $key => $records) {
            [$komoditasNama, $kabupatenNama] = explode('||', $key);

            // Minimal 2 hari data untuk analisis
            if ($records->count() < 2) {
                continue;
            }

            $hargaHarian = $records->pluck('avg_harga_harian')->map(fn($v) => (float) $v)->values();
            $tanggalList = $records->pluck('tanggal')->values();

            // Statistik deskriptif
            $mean = $hargaHarian->avg();
            $stdDev = $this->standardDeviation($hargaHarian);
            $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 0; // Koefisien Variasi (%)
            $minHarga = $hargaHarian->min();
            $maxHarga = $hargaHarian->max();
            $range = $maxHarga - $minHarga;

            // Perubahan harga harian (daily change %)
            $dailyChanges = [];
            for ($i = 1; $i < $hargaHarian->count(); $i++) {
                $prev = $hargaHarian[$i - 1];
                $curr = $hargaHarian[$i];
                $change = $prev > 0 ? (($curr - $prev) / $prev) * 100 : 0;
                $dailyChanges[] = round($change, 2);
            }

            $avgDailyChange = count($dailyChanges) > 0 ? array_sum(array_map('abs', $dailyChanges)) / count($dailyChanges) : 0;
            $maxDailyChange = count($dailyChanges) > 0 ? max(array_map('abs', $dailyChanges)) : 0;

            $hasilAnalisis[] = [
                'komoditas_nama' => $komoditasNama,
                'kabupaten_nama' => $kabupatenNama,
                'kabupaten_id' => $records->first()->kabupaten_id,
                'jumlah_hari_data' => $records->count(),
                'mean_harga' => round($mean, 0),
                'std_dev' => round($stdDev, 2),
                'cv_persen' => round($cv, 2),
                'min_harga' => round($minHarga, 0),
                'max_harga' => round($maxHarga, 0),
                'range_harga' => round($range, 0),
                'avg_daily_change_persen' => round($avgDailyChange, 2),
                'max_daily_change_persen' => round($maxDailyChange, 2),
                'daily_changes' => $dailyChanges,
                'harga_harian' => $hargaHarian->toArray(),
                'tanggal_list' => $tanggalList->toArray(),
                'tanggal_awal' => $tanggalAwal->format('Y-m-d'),
                'tanggal_akhir' => $tanggalAkhir->format('Y-m-d'),
            ];
        }

        return $hasilAnalisis;
    }

    /**
     * Menghitung standar deviasi populasi dari collection
     */
    private function standardDeviation(Collection $values): float
    {
        $count = $values->count();
        if ($count <= 1) return 0;

        $mean = $values->avg();
        $sumSquaredDiff = $values->reduce(function ($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0);

        return sqrt($sumSquaredDiff / $count);
    }
}
