<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Komoditas extends Model
{
    use HasFactory;
    
    protected $table = 'komoditas';
    protected $fillable = ['tanggal', 'pasar_id', 'kategori_id', 'komoditas_nama', 'satuan', 'harga', 'harga_before', 'created_at'];
    public $timestamps = false;
    
    public function pasar()
    {
        return $this->belongsTo(Pasar::class, 'pasar_id');
    }
    
    public function kategori()
    {
        return $this->belongsTo(KategoriKomoditas::class, 'kategori_id');
    }
}