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
        $kategoriList = KategoriKomoditas::orderBy('kategori')->get();
        $selectedKategori = $request->input('kategori_id', $kategoriList->first()->id ?? null);
        
        return view('Dashboard.index', compact('kategoriList', 'selectedKategori'));
    }
    
    public function getData(Request $request)
    {
        $kategoriId = $request->input('kategori_id');
        
        if (!$kategoriId) {
            return response()->json(['error' => 'Kategori harus dipilih'], 400);
        }
        
        // Hitung rata-rata harga per kabupaten/kota untuk kategori tertentu
        $dataKabupaten = DB::table('komoditas as k')
            ->join('pasar as p', 'k.pasar_id', '=', 'p.id')
            ->join('kabupaten_kota as kk', 'p.kabupaten_kota_id', '=', 'kk.id')
            ->where('k.kategori_id', $kategoriId)
            ->select(
                'kk.id',
                'kk.nama',
                'kk.keycode',
                DB::raw('AVG(k.harga) as avg_harga'),
                DB::raw('COUNT(k.id) as jumlah_data')
            )
            ->groupBy('kk.id', 'kk.nama', 'kk.keycode')
            ->get();
        
        if ($dataKabupaten->isEmpty()) {
            return response()->json([
                'data' => [],
                'rata_rata_keseluruhan' => 0,
                'message' => 'Tidak ada data untuk kategori ini'
            ]);
        }
        
        // Hitung rata-rata keseluruhan untuk kategori ini
        $rataRataKeseluruhan = $dataKabupaten->avg('avg_harga');
        
        // Tentukan kategori warna untuk setiap kabupaten
        $result = $dataKabupaten->map(function($item) use ($rataRataKeseluruhan) {
            $harga = $item->avg_harga;
            $selisih = (($harga - $rataRataKeseluruhan) / $rataRataKeseluruhan) * 100;
            
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
                'id' => $item->id,
                'nama' => $item->nama,
                'keycode' => $item->keycode,
                'avg_harga' => round($item->avg_harga, 0),
                'jumlah_data' => $item->jumlah_data,
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
}