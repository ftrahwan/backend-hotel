<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detail extends Model
{
    use HasFactory;

    protected $table = 'detail_pemesanan';
    protected $primaryKey = 'id_detail_pemesanan';
    public $timestamps = false;
    public $fillable = [
        'id_pemesanan', 
        'id_kamar', 
        'tgl_akses', 
        'harga'
    ];

    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }

    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'id_kamar', 'id_kamar');
    }

    public function tipeKamar()
    {
        return $this->belongsTo(TipeKamar::class, 'id_kamar', 'id_tipe_kamar');
    }
}

