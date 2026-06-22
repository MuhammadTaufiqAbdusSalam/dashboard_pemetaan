<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HargaStabilitasAnalysis;
use App\Services\RuleBasedDecision;
use App\Services\GeminiNarasiService;

class AnalisisController extends Controller
{
    private HargaStabilitasAnalysis $analisisService;
    private RuleBasedDecision $ruleService;
    private GeminiNarasiService $geminiService;

    public function __construct(
        HargaStabilitasAnalysis $analisisService,
        RuleBasedDecision $ruleService,
        GeminiNarasiService $geminiService
    ) {
        $this->analisisService = $analisisService;
        $this->ruleService = $ruleService;
        $this->geminiService = $geminiService;
    }

    /**
     * API: Ambil data analisis stabilitas + narasi AI
     */
    public function getAnalisis(Request $request)
    {
        $tanggalAkhir = $request->input('tanggal_akhir', $request->input('tanggal', now()->format('Y-m-d')));
        $tanggalAwal = $request->input('tanggal_awal');

        if (!$tanggalAwal) {
            $tanggalAwal = date('Y-m-d', strtotime($tanggalAkhir . ' -6 days'));
        }

        // 1. Analisis Statistik Stabilitas Harga
        $hasilAnalisis = $this->analisisService->analyzeAll($tanggalAwal, $tanggalAkhir);

        if (empty($hasilAnalisis)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang cukup untuk analisis pada periode yang dipilih.',
                'data' => null,
                'narasi' => null,
            ]);
        }

        // 2. Rule-Based Decision
        $evaluatedData = $this->ruleService->evaluate($hasilAnalisis);

        // 3. Hasil Analisis (Summary)
        $summary = $this->ruleService->generateSummary($evaluatedData);

        // 4 & 5. Gemini AI - Natural Language Generation
        $narasi = $this->geminiService->generateNarasi($summary, $tanggalAwal, $tanggalAkhir);

        // 6. Output
        return response()->json([
            'success' => true,
            'tanggal_analisis' => $tanggalAkhir,
            'periode' => [
                'dari' => $tanggalAwal,
                'sampai' => $tanggalAkhir,
            ],
            'statistik_umum' => $summary['statistik_umum'],
            'top_stabil' => array_map(function ($item) {
                return [
                    'komoditas_nama' => $item['komoditas_nama'],
                    'kabupaten_nama' => $item['kabupaten_nama'],
                    'mean_harga' => $item['mean_harga'],
                    'cv_persen' => $item['cv_persen'],
                    'avg_daily_change_persen' => $item['avg_daily_change_persen'],
                    'skor_stabilitas' => $item['skor_stabilitas'],
                    'status_stabilitas' => $item['status_stabilitas'],
                    'label_stabilitas' => $item['label_stabilitas'],
                ];
            }, $summary['top_stabil']),
            'top_tidak_stabil' => array_map(function ($item) {
                return [
                    'komoditas_nama' => $item['komoditas_nama'],
                    'kabupaten_nama' => $item['kabupaten_nama'],
                    'mean_harga' => $item['mean_harga'],
                    'cv_persen' => $item['cv_persen'],
                    'avg_daily_change_persen' => $item['avg_daily_change_persen'],
                    'skor_stabilitas' => $item['skor_stabilitas'],
                    'status_stabilitas' => $item['status_stabilitas'],
                    'label_stabilitas' => $item['label_stabilitas'],
                ];
            }, $summary['top_tidak_stabil']),
            'ringkasan_komoditas' => $summary['ringkasan_komoditas'],
            'ringkasan_kabupaten' => $summary['ringkasan_kabupaten'],
            'narasi' => $narasi,
        ]);
    }

    /**
     * API: Force refresh narasi (buat ulang tanpa cache)
     */
    public function refreshNarasi(Request $request)
    {
        $tanggalAkhir = $request->input('tanggal_akhir', $request->input('tanggal', now()->format('Y-m-d')));
        $tanggalAwal = $request->input('tanggal_awal');

        if (!$tanggalAwal) {
            $tanggalAwal = date('Y-m-d', strtotime($tanggalAkhir . ' -6 days'));
        }

        $hasilAnalisis = $this->analisisService->analyzeAll($tanggalAwal, $tanggalAkhir);

        if (empty($hasilAnalisis)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data untuk analisis.',
                'narasi' => null,
            ]);
        }

        $evaluatedData = $this->ruleService->evaluate($hasilAnalisis);
        $summary = $this->ruleService->generateSummary($evaluatedData);
        $narasi = $this->geminiService->forceGenerateNarasi($summary, $tanggalAwal, $tanggalAkhir);

        return response()->json([
            'success' => true,
            'narasi' => $narasi,
        ]);
    }
}
