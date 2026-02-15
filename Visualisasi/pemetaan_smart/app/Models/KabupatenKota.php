<?php
// app/Models/KabupatenKota.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KabupatenKota extends Model
{
    use HasFactory;
    
    protected $table = 'kabupaten_kota';
    protected $fillable = ['nama', 'keycode', 'created_at'];
    public $timestamps = false;
    
    public function pasar()
    {
        return $this->hasMany(Pasar::class, 'kabupaten_kota_id');
    }
}