<?php
// app/Models/Pasar.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasar extends Model
{
    use HasFactory;
    
    protected $table = 'pasar';
    protected $fillable = ['nama', 'kabupaten_kota_id', 'status', 'created_at'];
    public $timestamps = false;
    
    public function kabupatenKota()
    {
        return $this->belongsTo(KabupatenKota::class, 'kabupaten_kota_id');
    }
    
    public function komoditas()
    {
        return $this->hasMany(Komoditas::class, 'pasar_id');
    }
}