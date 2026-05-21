<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeminiNarasiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->model = config('services.gemini.model', 'gemini-2.0-flash');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    }

    /**
     * Generate narasi stabilitas harga menggunakan Gemini AI.
     * Hasilnya di-cache selama 24 jam.
     */
    public function generateNarasi(array $summary, string $tanggalAkhir): ?string
    {
        $cacheKey = 'narasi_stabilitas_' . md5(json_encode($summary) . $tanggalAkhir);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($summary, $tanggalAkhir) {
            return $this->callGeminiAPI($summary, $tanggalAkhir);
        });
    }

    /**
     * Force generate tanpa cache (untuk refresh manual)
     */
    public function forceGenerateNarasi(array $summary, string $tanggalAkhir): ?string
    {
        $cacheKey = 'narasi_stabilitas_' . md5(json_encode($summary) . $tanggalAkhir);
        Cache::forget($cacheKey);

        $result = $this->callGeminiAPI($summary, $tanggalAkhir);

        if ($result) {
            Cache::put($cacheKey, $result, now()->addHours(24));
        }

        return $result;
    }

    /**
     * Panggil Gemini API untuk generate narasi
     */
    private function callGeminiAPI(array $summary, string $tanggalAkhir): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key belum dikonfigurasi');
            return $this->generateFallbackNarasi($summary, $tanggalAkhir);
        }

        $prompt = $this->buildPrompt($summary, $tanggalAkhir);

        try {
            $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(30)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1500,
                    'topP' => 0.9,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($text) {
                    Log::info('Narasi berhasil digenerate oleh Gemini AI');
                    return trim($text);
                }
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->generateFallbackNarasi($summary, $tanggalAkhir);
        } catch (\Exception $e) {
            Log::error('Gemini API exception: ' . $e->getMessage());
            return $this->generateFallbackNarasi($summary, $tanggalAkhir);
        }
    }

    /**
     * Bangun prompt untuk Gemini AI
     */
    private function buildPrompt(array $summary, string $tanggalAkhir): string
    {
        $topStabil = $summary['top_stabil'] ?? [];
        $topTidakStabil = $summary['top_tidak_stabil'] ?? [];
        $ringkasanKomoditas = $summary['ringkasan_komoditas'] ?? [];
        $ringkasanKabupaten = $summary['ringkasan_kabupaten'] ?? [];
        $statistikUmum = $summary['statistik_umum'] ?? [];

        // Format data top stabil
        $topStabilText = '';
        foreach (array_slice($topStabil, 0, 5) as $i => $item) {
            $num = $i + 1;
            $topStabilText .= "{$num}. {$item['komoditas_nama']} di {$item['kabupaten_nama']} - CV: {$item['cv_persen']}%, Rata-rata perubahan harian: {$item['avg_daily_change_persen']}%, Harga rata-rata: Rp" . number_format($item['mean_harga'], 0, ',', '.') . "\n";
        }

        // Format data top tidak stabil
        $topTidakStabilText = '';
        foreach (array_slice($topTidakStabil, 0, 5) as $i => $item) {
            $num = $i + 1;
            $topTidakStabilText .= "{$num}. {$item['komoditas_nama']} di {$item['kabupaten_nama']} - CV: {$item['cv_persen']}%, Rata-rata perubahan harian: {$item['avg_daily_change_persen']}%, Harga rata-rata: Rp" . number_format($item['mean_harga'], 0, ',', '.') . "\n";
        }

        // Format ringkasan komoditas terbaik
        $bestKomoditasText = '';
        foreach (array_slice($ringkasanKomoditas, 0, 5) as $i => $item) {
            $num = $i + 1;
            $bestKomoditasText .= "{$num}. {$item['komoditas_nama']} - Skor rata-rata: {$item['avg_skor_stabilitas']}, CV rata-rata: {$item['avg_cv']}%, Stabil di {$item['jumlah_stabil']}/{$item['jumlah_wilayah']} wilayah\n";
        }

        // Format ringkasan kabupaten terbaik
        $bestKabupatenText = '';
        foreach (array_slice($ringkasanKabupaten, 0, 5) as $i => $item) {
            $num = $i + 1;
            $bestKabupatenText .= "{$num}. {$item['kabupaten_nama']} - Skor rata-rata: {$item['avg_skor_stabilitas']}, CV rata-rata: {$item['avg_cv']}%, Stabil: {$item['jumlah_stabil']}/{$item['jumlah_komoditas']} komoditas\n";
        }

        $tanggalAwal = date('d M Y', strtotime($tanggalAkhir . ' -6 days'));
        $tanggalAkhirFormatted = date('d M Y', strtotime($tanggalAkhir));

        $prompt = <<<PROMPT
Kamu adalah analis harga komoditas pangan profesional untuk Provinsi Jawa Timur, Indonesia. Buatkan narasi informatif tentang stabilitas harga komoditas berdasarkan data analisis berikut.

PERIODE ANALISIS: {$tanggalAwal} sampai {$tanggalAkhirFormatted} (7 hari terakhir)

STATISTIK UMUM:
- Total pasangan komoditas-wilayah yang dianalisis: {$statistikUmum['total_data_analisis']}
- Sangat Stabil: {$statistikUmum['jumlah_sangat_stabil']}
- Stabil: {$statistikUmum['jumlah_stabil']}
- Cukup Stabil: {$statistikUmum['jumlah_cukup_stabil']}
- Tidak Stabil: {$statistikUmum['jumlah_tidak_stabil']}
- Koefisien Variasi (CV) rata-rata keseluruhan: {$statistikUmum['avg_cv_keseluruhan']}%
- Perubahan harga harian rata-rata keseluruhan: {$statistikUmum['avg_daily_change_keseluruhan']}%

TOP 5 KOMODITAS-WILAYAH PALING STABIL:
{$topStabilText}

TOP 5 KOMODITAS-WILAYAH PALING TIDAK STABIL:
{$topTidakStabilText}

5 KOMODITAS PALING STABIL (SECARA KESELURUHAN):
{$bestKomoditasText}

5 KABUPATEN/KOTA DENGAN HARGA PALING STABIL:
{$bestKabupatenText}

INSTRUKSI:
1. Buatkan narasi dalam Bahasa Indonesia yang informatif, ringkas, dan mudah dipahami.
2. Narasi harus mencakup: kondisi umum stabilitas harga, komoditas/wilayah paling stabil, komoditas/wilayah yang perlu perhatian, dan rekomendasi singkat.
3. Gunakan format paragraf yang rapi (3-4 paragraf), BUKAN bullet points.
4. Jangan tampilkan angka desimal yang terlalu banyak, bulatkan jika perlu.
5. Gunakan bahasa yang profesional namun mudah dipahami masyarakat umum.
6. Panjang narasi sekitar 200-350 kata.
7. Jangan menggunakan markdown formatting (tidak ada **, ##, dll). Gunakan teks biasa saja.
PROMPT;

        return $prompt;
    }

    /**
     * Narasi fallback jika Gemini API tidak tersedia
     */
    private function generateFallbackNarasi(array $summary, string $tanggalAkhir): string
    {
        $statistik = $summary['statistik_umum'] ?? [];
        $topStabil = $summary['top_stabil'] ?? [];
        $topTidakStabil = $summary['top_tidak_stabil'] ?? [];
        $ringkasanKomoditas = $summary['ringkasan_komoditas'] ?? [];
        $ringkasanKabupaten = $summary['ringkasan_kabupaten'] ?? [];

        if (empty($statistik)) {
            return 'Data analisis stabilitas harga belum tersedia. Pastikan terdapat data harga komoditas dalam 7 hari terakhir.';
        }

        $tanggalAwal = date('d M Y', strtotime($tanggalAkhir . ' -6 days'));
        $tanggalAkhirFormatted = date('d M Y', strtotime($tanggalAkhir));

        $totalStabil = ($statistik['jumlah_sangat_stabil'] ?? 0) + ($statistik['jumlah_stabil'] ?? 0);
        $totalAnalisis = $statistik['total_data_analisis'] ?? 0;
        $persenStabil = $totalAnalisis > 0 ? round(($totalStabil / $totalAnalisis) * 100, 1) : 0;

        $narasi = "Berdasarkan analisis data harga komoditas di Provinsi Jawa Timur periode {$tanggalAwal} hingga {$tanggalAkhirFormatted}, ";
        $narasi .= "dari total {$totalAnalisis} pasangan komoditas-wilayah yang dianalisis, sebanyak {$persenStabil}% menunjukkan harga yang stabil. ";
        $narasi .= "Koefisien variasi rata-rata keseluruhan tercatat sebesar {$statistik['avg_cv_keseluruhan']}% dengan perubahan harga harian rata-rata {$statistik['avg_daily_change_keseluruhan']}%.\n\n";

        if (!empty($topStabil)) {
            $best = $topStabil[0];
            $narasi .= "Komoditas dengan stabilitas harga terbaik adalah {$best['komoditas_nama']} di wilayah {$best['kabupaten_nama']} ";
            $narasi .= "dengan koefisien variasi hanya {$best['cv_persen']}% dan rata-rata perubahan harian {$best['avg_daily_change_persen']}%.";
        }

        if (!empty($ringkasanKomoditas)) {
            $bestKom = $ringkasanKomoditas[0];
            $narasi .= " Secara keseluruhan, komoditas {$bestKom['komoditas_nama']} menunjukkan stabilitas terbaik di {$bestKom['jumlah_stabil']} dari {$bestKom['jumlah_wilayah']} wilayah.";
        }

        $narasi .= "\n\n";

        if (!empty($topTidakStabil)) {
            $worst = $topTidakStabil[0];
            $narasi .= "Di sisi lain, {$worst['komoditas_nama']} di {$worst['kabupaten_nama']} menunjukkan fluktuasi harga tertinggi ";
            $narasi .= "dengan koefisien variasi {$worst['cv_persen']}%. Komoditas dan wilayah ini perlu mendapat perhatian lebih ";
            $narasi .= "untuk menjaga stabilitas harga pangan di Jawa Timur.";
        }

        return $narasi;
    }
}
