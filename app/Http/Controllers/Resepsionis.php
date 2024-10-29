<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pemesanan;

class Resepsionis extends Controller
{
    public function checkIn(Request $req)
    {

        $req->validate([
            'nomor_pemesanan' => 'required|string|exists:pemesanan,nomor_pemesanan',
        ]);

        $pemesanan = Pemesanan::where('nomor_pemesanan', $req->nomor_pemesanan)->first();

        if (!$pemesanan) {
            return response()->json([
                'status' => false,
                'message' => 'Pemesanan tidak ditemukan',
            ]);
        }

        if ($pemesanan->status_pemesanan != 'baru') {
            return response()->json([
                'status' => false,
                'message' => 'Check-in gagal, status pemesanan tidak valid untuk check-in'
            ]);
        }

        $pemesanan->status_pemesanan = 'check_in';
        $pemesanan->save();

        return response()->json([
            'status' => true,
            'message' => 'Check-in berhasil',
        ]);
    }

    public function checkOut(Request $req)
    {
        $req->validate([
            'nomor_pemesanan' => 'required|string|exists:pemesanan,nomor_pemesanan',
        ]);

        $pemesanan = Pemesanan::where('nomor_pemesanan', $req->nomor_pemesanan)->first();

        if ($pemesanan->status_pemesanan != 'check_in') {
            return response()->json([
                'status' => false,
                'message' => 'Check-out gagal, status pemesanan tidak valid untuk check-out'
            ]);
        }

        $pemesanan->status_pemesanan = 'check_out';
        $pemesanan->save();

        return response()->json([
            'status' => true,
            'message' => 'Check-out berhasil',
        ]);
    }
    public function getPemesanan()
    {
        $pemesanan = Pemesanan::with(['detailPemesanan.tipeKamar'])
            ->orderBy('tgl_pemesanan', 'desc')
            ->get();

        return response()->json([
            'pemesanan' => $pemesanan,
        ]);
    }

    public function getFiltered(Request $request)
    {
        $tglCheckIn = $request->input('tgl_check_in');
        $tglCheckOut = $request->input('tgl_check_out');

        $pemesanan = Pemesanan::with(['detailPemesanan.tipeKamar'])
            ->when($tglCheckIn, function ($query, $tglCheckIn) {
                return $query->where('tgl_check_in', '>=', $tglCheckIn);
            })
            ->when($tglCheckOut, function ($query, $tglCheckOut) {
                return $query->where('tgl_check_out', '<=', $tglCheckOut);
            })
            ->orderBy('tgl_pemesanan', 'desc')
            ->get();

        return response()->json([
            'pemesanan' => $pemesanan,
        ]);
    }

}
