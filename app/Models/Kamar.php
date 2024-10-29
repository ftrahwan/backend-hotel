<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    protected $table = 'kamar';
    protected $primarykey = 'id_kamar';
    public $timestamps = false;
    public $fillable = [
        'nomor_kamar',
        'id_tipe_kamar'
    ];

    public function tipeKamar()
    {
        return $this->belongsTo(TipeKamar::class, 'id_tipe_kamar');
    }

    public function detailPemesanan()
    {
        return $this->hasMany(Detail::class, 'id_kamar', 'id_kamar');
    }
}
