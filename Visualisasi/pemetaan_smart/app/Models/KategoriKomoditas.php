<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriKomoditas extends Model
{
    use HasFactory;
    
    protected $table = 'kategori_komoditas';
    protected $fillable = ['kategori', 'created_at'];
    public $timestamps = false;
    
    public function komoditas()
    {
        return $this->hasMany(Komoditas::class, 'kategori_id');
    }
}