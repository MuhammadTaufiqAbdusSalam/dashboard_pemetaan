<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\KabupatenKota;
use App\Models\KategoriKomoditas;

class PetaController extends Controller
{
    public function index(Request $request)
    {
        // Ambil daftar komoditas unik dari tabel komoditas
        $komoditasList = DB::table('komoditas')
            ->select('komoditas_nama')
            ->distinct()
            ->orderBy('komoditas_nama')
            ->get();
        
        $selectedKomoditas = $request->input('komoditas_nama', $komoditasList->first()->komoditas_nama ?? null);
        
        return view('Dashboard.index', compact('komoditasList', 'selectedKomoditas'));
    }
    
    public function getData(Request $request)
    {
        $komoditasNama = $request->input('komoditas_nama');
        $tanggalAwal = $request->input('tanggal_awal');
        $tanggalAkhir = $request->input('tanggal_akhir');
        
        if (!$komoditasNama) {
            return response()->json(['error' => 'Komoditas harus dipilih'], 400);
        }
        
        // Hitung rata-rata harga per kabupaten/kota untuk komoditas tertentu pada tanggal/periode spesifik
        $query = DB::table('komoditas as k')
            ->join('pasar as p', 'k.pasar_id', '=', 'p.id')
            ->join('kabupaten_kota as kk', 'p.kabupaten_kota_id', '=', 'kk.id')
            ->where('k.komoditas_nama', $komoditasNama)
            ->where('k.harga', '>', 0);

        if ($tanggalAwal && $tanggalAkhir) {
            $query->whereBetween('k.tanggal', [$tanggalAwal, $tanggalAkhir]);
        } else {
            $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
            $query->whereDate('k.tanggal', '=', $tanggal);
        }

        $dataKabupaten = $query->select(
                'kk.id',
                'kk.nama',
                'kk.keycode',
                DB::raw('AVG(k.harga) as avg_harga'),
                DB::raw('COUNT(k.id) as jumlah_data')
            )
            ->groupBy('kk.id', 'kk.nama', 'kk.keycode')
            ->get();
        
        // Ambil semua kabupaten/kota
        $allKabupaten = KabupatenKota::all();
        
        // Hitung rata-rata keseluruhan hanya dari kabupaten yang punya data dengan harga > 0
        $dataWithPrice = $dataKabupaten->filter(function($item) {
            return $item->avg_harga > 0;
        });
        
        $rataRataKeseluruhan = $dataWithPrice->isEmpty() ? 0 : $dataWithPrice->avg('avg_harga');
        
        // Tentukan kategori warna untuk setiap kabupaten
        $result = $allKabupaten->map(function($kabupaten) use ($dataKabupaten, $rataRataKeseluruhan) {
            // Cari data untuk kabupaten ini
            $data = $dataKabupaten->firstWhere('id', $kabupaten->id);
            
            // Jika tidak ada data atau harga 0, beri warna abu-abu
            if (!$data || $data->avg_harga == 0) {
                return [
                    'id' => $kabupaten->id,
                    'nama' => $kabupaten->nama,
                    'keycode' => $kabupaten->keycode,
                    'avg_harga' => 0,
                    'jumlah_data' => 0,
                    'selisih_persen' => 0,
                    'kategori_harga' => 'Tidak Ada Data',
                    'color' => '#9E9E9E', // Abu-abu
                ];
            }
            
            $harga = $data->avg_harga;
            $selisih = $rataRataKeseluruhan > 0 ? (($harga - $rataRataKeseluruhan) / $rataRataKeseluruhan) * 100 : 0;
            
            // Tentukan warna berdasarkan selisih
            if ($selisih < -10) {
                $color = '#FFEB3B'; // Kuning - Harga rendah (< -10%)
                $kategori = 'Rendah';
            } elseif ($selisih > 10) {
                $color = '#F44336'; // Merah - Harga tinggi (> +10%)
                $kategori = 'Tinggi';
            } else {
                $color = '#4CAF50'; // Hijau - Harga normal (-10% s/d +10%)
                $kategori = 'Normal';
            }
            
            return [
                'id' => $data->id,
                'nama' => $data->nama,
                'keycode' => $data->keycode,
                'avg_harga' => round($data->avg_harga, 0),
                'jumlah_data' => $data->jumlah_data,
                'selisih_persen' => round($selisih, 2),
                'kategori_harga' => $kategori,
                'color' => $color,
            ];
        });
        
        return response()->json([
            'data' => $result,
            'rata_rata_keseluruhan' => round($rataRataKeseluruhan, 0),
        ]);
    }
    //chart
    public function chart(Request $request)
    {
        $komoditasList = DB::table('komoditas')
            ->select('komoditas_nama')
            ->distinct()
            ->orderBy('komoditas_nama')
            ->get();
            
        $kabupatenList = DB::table('kabupaten_kota')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();
            
        $selectedKomoditas = $request->input('komoditas_nama', $komoditasList->first()->komoditas_nama ?? null);
        $selectedKabupaten = $request->input('kabupaten_id');
        
        return view('Dashboard.chart', compact('komoditasList', 'kabupatenList', 'selectedKomoditas', 'selectedKabupaten'));
    }

    public function getChartData(Request $request)
    {
        $komoditasNama = $request->input('komoditas_nama');
        $kabupatenId = $request->input('kabupaten_id');
        $tanggalAwal = $request->input('tanggal_awal');
        $tanggalAkhir = $request->input('tanggal_akhir');
        
        if (!$komoditasNama) {
            return response()->json(['error' => 'Komoditas harus dipilih'], 400);
        }
        
        // 1. Rata-rata Jawa Timur (seluruh kabupaten/kota) per tanggal
        $jatimQuery = DB::table('komoditas as k')
            ->where('k.komoditas_nama', $komoditasNama)
            ->where('k.harga', '>', 0);
            
        if ($tanggalAwal && $tanggalAkhir) {
            $jatimQuery->whereBetween('k.tanggal', [$tanggalAwal, $tanggalAkhir]);
        }
        
        $jatimData = $jatimQuery->select(
                'k.tanggal',
                DB::raw('ROUND(AVG(k.harga), 0) as avg_harga')
            )
            ->groupBy('k.tanggal')
            ->orderBy('k.tanggal')
            ->get();
            
        // 2. Rata-rata kabupaten terpilih per tanggal
        $kabupatenData = [];
        if ($kabupatenId) {
            $kabupatenQuery = DB::table('komoditas as k')
                ->join('pasar as p', 'k.pasar_id', '=', 'p.id')
                ->where('k.komoditas_nama', $komoditasNama)
                ->where('p.kabupaten_kota_id', $kabupatenId)
                ->where('k.harga', '>', 0);
                
            if ($tanggalAwal && $tanggalAkhir) {
                $kabupatenQuery->whereBetween('k.tanggal', [$tanggalAwal, $tanggalAkhir]);
            }
            
            $kabupatenData = $kabupatenQuery->select(
                    'k.tanggal',
                    DB::raw('ROUND(AVG(k.harga), 0) as avg_harga')
                )
                ->groupBy('k.tanggal')
                ->orderBy('k.tanggal')
                ->get();
        }
        
        // 3. Rata-rata per kabupaten untuk perbandingan (bar chart) pada periode tersebut
        $comparisonQuery = DB::table('komoditas as k')
            ->join('pasar as p', 'k.pasar_id', '=', 'p.id')
            ->join('kabupaten_kota as kk', 'p.kabupaten_kota_id', '=', 'kk.id')
            ->where('k.komoditas_nama', $komoditasNama)
            ->where('k.harga', '>', 0);
            
        if ($tanggalAwal && $tanggalAkhir) {
            $comparisonQuery->whereBetween('k.tanggal', [$tanggalAwal, $tanggalAkhir]);
        }
        
        $comparisonData = $comparisonQuery->select(
                'kk.nama as kabupaten_nama',
                DB::raw('AVG(k.harga) as avg_harga')
            )
            ->groupBy('kk.id', 'kk.nama')
            ->get();

        // Hitung rata-rata keseluruhan (rata-rata dari rata-rata kabupaten)
        $dataWithPrice = $comparisonData->filter(function($item) {
            return $item->avg_harga > 0;
        });
        
        $rataRataKeseluruhan = $dataWithPrice->isEmpty() ? 0 : $dataWithPrice->avg('avg_harga');

        // Format untuk perbandingan bar chart
        $formattedComparison = $comparisonData->map(function($item) {
            return [
                'kabupaten_nama' => $item->kabupaten_nama,
                'avg_harga' => round($item->avg_harga, 0)
            ];
        })->sortByDesc('avg_harga')->values();
            
        return response()->json([
            'jatim' => $jatimData,
            'kabupaten' => $kabupatenData,
            'perbandingan' => $formattedComparison,
            'rata_rata_keseluruhan' => round($rataRataKeseluruhan, 0),
        ]);
    }
}