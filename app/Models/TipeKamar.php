<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipeKamar extends Model
{
    use HasFactory;

    protected $table = 'tipe_kamar';
    protected $primaryKey = 'id_tipe_kamar';
    public $timestamps = false;
    protected $fillable = [
        'nama_tipe_kamar', 
        'harga', 
        'deskripsi', 
        'foto'
    ];

    public function kamar()
    {
        return $this->hasMany(Kamar::class, 'id_tipe_kamar');
    }

    public function detailPemesanan()
    {
        return $this->hasMany(Detail::class, 'id_kamar', 'id_tipe_kamar');
    }
}
