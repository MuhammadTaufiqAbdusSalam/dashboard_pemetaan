<?php

namespace App\Services;

class RuleBasedDecision
{
    /**
     * Threshold default untuk rule-based decision
     */
    const CV_SANGAT_STABIL = 2.0;    // CV < 2%
    const CV_STABIL = 5.0;           // CV 2-5%
    const CV_CUKUP_STABIL = 10.0;    // CV 5-10%
    // CV > 10% = Tidak Stabil

    const DAILY_CHANGE_SANGAT_STABIL = 1.0;  // < 1%
    const DAILY_CHANGE_STABIL = 3.0;          // 1-3%
    const DAILY_CHANGE_CUKUP_STABIL = 5.0;   // 3-5%
    // > 5% = Tidak Stabil

    /**
     * Menerapkan rule-based decision pada hasil analisis statistik.
     * Menentukan status stabilitas dan mengurutkan berdasarkan skor stabilitas.
     */
    public function evaluate(array $hasilAnalisis): array
    {
        $evaluated = array_map(function ($item) {
            // Hitung skor stabilitas (semakin rendah semakin stabil, skala 0-100)
            $skorCV = $this->skorDariCV($item['cv_persen']);
            $skorDailyChange = $this->skorDariDailyChange($item['avg_daily_change_persen']);
            $skorMaxChange = $this->skorDariMaxChange($item['max_daily_change_persen']);

            // Bobot: CV (40%), Avg Daily Change (35%), Max Daily Change (25%)
            $skorStabilitas = ($skorCV * 0.40) + ($skorDailyChange * 0.35) + ($skorMaxChange * 0.25);

            $item['skor_stabilitas'] = round($skorStabilitas, 2);
            $item['status_stabilitas'] = $this->tentukanStatus($item['cv_persen'], $item['avg_daily_change_persen']);
            $item['label_stabilitas'] = $this->tentukanLabel($item['status_stabilitas']);

            return $item;
        }, $hasilAnalisis);

        // Urutkan berdasarkan skor stabilitas (ascending = paling stabil di atas)
        usort($evaluated, function ($a, $b) {
            return $a['skor_stabilitas'] <=> $b['skor_stabilitas'];
        });

        return $evaluated;
    }

    /**
     * Hitung skor dari Koefisien Variasi (0 = sangat stabil, 100 = sangat tidak stabil)
     */
    private function skorDariCV(float $cv): float
    {
        if ($cv <= self::CV_SANGAT_STABIL) return max(0, $cv / self::CV_SANGAT_STABIL * 10);
        if ($cv <= self::CV_STABIL) return 10 + (($cv - self::CV_SANGAT_STABIL) / (self::CV_STABIL - self::CV_SANGAT_STABIL)) * 25;
        if ($cv <= self::CV_CUKUP_STABIL) return 35 + (($cv - self::CV_STABIL) / (self::CV_CUKUP_STABIL - self::CV_STABIL)) * 30;
        return min(100, 65 + (($cv - self::CV_CUKUP_STABIL) / 10) * 35);
    }

    /**
     * Hitung skor dari rata-rata perubahan harian
     */
    private function skorDariDailyChange(float $avgChange): float
    {
        if ($avgChange <= self::DAILY_CHANGE_SANGAT_STABIL) return max(0, $avgChange / self::DAILY_CHANGE_SANGAT_STABIL * 10);
        if ($avgChange <= self::DAILY_CHANGE_STABIL) return 10 + (($avgChange - self::DAILY_CHANGE_SANGAT_STABIL) / (self::DAILY_CHANGE_STABIL - self::DAILY_CHANGE_SANGAT_STABIL)) * 25;
        if ($avgChange <= self::DAILY_CHANGE_CUKUP_STABIL) return 35 + (($avgChange - self::DAILY_CHANGE_STABIL) / (self::DAILY_CHANGE_CUKUP_STABIL - self::DAILY_CHANGE_STABIL)) * 30;
        return min(100, 65 + (($avgChange - self::DAILY_CHANGE_CUKUP_STABIL) / 5) * 35);
    }

    /**
     * Hitung skor dari perubahan harian maksimum
     */
    private function skorDariMaxChange(float $maxChange): float
    {
        if ($maxChange <= 2.0) return max(0, $maxChange / 2.0 * 10);
        if ($maxChange <= 5.0) return 10 + (($maxChange - 2.0) / 3.0) * 25;
        if ($maxChange <= 10.0) return 35 + (($maxChange - 5.0) / 5.0) * 30;
        return min(100, 65 + (($maxChange - 10.0) / 10.0) * 35);
    }

    /**
     * Tentukan status stabilitas berdasarkan aturan gabungan CV dan daily change
     */
    private function tentukanStatus(float $cv, float $avgDailyChange): string
    {
        if ($cv <= self::CV_SANGAT_STABIL && $avgDailyChange <= self::DAILY_CHANGE_SANGAT_STABIL) {
            return 'sangat_stabil';
        }
        if ($cv <= self::CV_STABIL && $avgDailyChange <= self::DAILY_CHANGE_STABIL) {
            return 'stabil';
        }
        if ($cv <= self::CV_CUKUP_STABIL && $avgDailyChange <= self::DAILY_CHANGE_CUKUP_STABIL) {
            return 'cukup_stabil';
        }
        return 'tidak_stabil';
    }

    /**
     * Label yang mudah dibaca untuk status stabilitas
     */
    private function tentukanLabel(string $status): string
    {
        return match ($status) {
            'sangat_stabil' => 'Sangat Stabil',
            'stabil' => 'Stabil',
            'cukup_stabil' => 'Cukup Stabil',
            'tidak_stabil' => 'Tidak Stabil',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Ambil ringkasan: top N komoditas paling stabil dan paling tidak stabil,
     * serta ringkasan per kabupaten.
     */
    public function generateSummary(array $evaluatedData, int $topN = 5): array
    {
        if (empty($evaluatedData)) {
            return [
                'top_stabil' => [],
                'top_tidak_stabil' => [],
                'ringkasan_komoditas' => [],
                'ringkasan_kabupaten' => [],
                'statistik_umum' => [],
            ];
        }

        // Top N paling stabil (skor terendah)
        $topStabil = array_slice($evaluatedData, 0, $topN);

        // Top N paling tidak stabil (skor tertinggi)
        $topTidakStabil = array_slice(array_reverse($evaluatedData), 0, $topN);

        // Ringkasan per komoditas (rata-rata skor stabilitas per komoditas)
        $perKomoditas = collect($evaluatedData)->groupBy('komoditas_nama')->map(function ($items, $komoditasNama) {
            return [
                'komoditas_nama' => $komoditasNama,
                'avg_skor_stabilitas' => round($items->avg('skor_stabilitas'), 2),
                'avg_cv' => round($items->avg('cv_persen'), 2),
                'avg_daily_change' => round($items->avg('avg_daily_change_persen'), 2),
                'jumlah_wilayah' => $items->count(),
                'jumlah_stabil' => $items->whereIn('status_stabilitas', ['sangat_stabil', 'stabil'])->count(),
                'jumlah_tidak_stabil' => $items->where('status_stabilitas', 'tidak_stabil')->count(),
            ];
        })->sortBy('avg_skor_stabilitas')->values()->toArray();

        // Ringkasan per kabupaten (rata-rata skor stabilitas per kabupaten)
        $perKabupaten = collect($evaluatedData)->groupBy('kabupaten_nama')->map(function ($items, $kabupatenNama) {
            return [
                'kabupaten_nama' => $kabupatenNama,
                'avg_skor_stabilitas' => round($items->avg('skor_stabilitas'), 2),
                'avg_cv' => round($items->avg('cv_persen'), 2),
                'jumlah_komoditas' => $items->count(),
                'jumlah_stabil' => $items->whereIn('status_stabilitas', ['sangat_stabil', 'stabil'])->count(),
            ];
        })->sortBy('avg_skor_stabilitas')->values()->toArray();

        // Statistik umum
        $allScores = collect($evaluatedData);
        $statistikUmum = [
            'total_data_analisis' => count($evaluatedData),
            'jumlah_sangat_stabil' => $allScores->where('status_stabilitas', 'sangat_stabil')->count(),
            'jumlah_stabil' => $allScores->where('status_stabilitas', 'stabil')->count(),
            'jumlah_cukup_stabil' => $allScores->where('status_stabilitas', 'cukup_stabil')->count(),
            'jumlah_tidak_stabil' => $allScores->where('status_stabilitas', 'tidak_stabil')->count(),
            'avg_cv_keseluruhan' => round($allScores->avg('cv_persen'), 2),
            'avg_daily_change_keseluruhan' => round($allScores->avg('avg_daily_change_persen'), 2),
        ];

        return [
            'top_stabil' => $topStabil,
            'top_tidak_stabil' => $topTidakStabil,
            'ringkasan_komoditas' => $perKomoditas,
            'ringkasan_kabupaten' => $perKabupaten,
            'statistik_umum' => $statistikUmum,
        ];
    }
}
