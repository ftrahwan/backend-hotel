<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Kamar;
use App\Models\TipeKamar;
use App\Models\Pemesanan;
use App\Models\Detail;
use PDF;

class Tamu extends Controller
{
    public function getAvailable()
    {
        $kamarTidakTersedia = Detail::whereHas('pemesanan', function ($query) {
            $query->where('status_pemesanan', '!=', 'check_out');
        })->pluck('id_kamar')->toArray();

        $kamar = TipeKamar::with([
            'kamar' => function ($query) use ($kamarTidakTersedia) {
                $query->whereNotIn('id_kamar', $kamarTidakTersedia);
            }
        ])->orderBy('nama_tipe_kamar', 'asc')->get();

        return response()->json([
            'kamar' => $kamar,
        ]);
    }

    public function createOrder(Request $req)
    {
        $user = auth()->user();
        $id_user = $user->id;

        $req->validate([
            'nama_pemesan' => 'required|string',
            'nama_tamu' => 'nullable|string',
            'email_pemesan' => 'nullable|email',
            'tgl_check_in' => 'required|date',
            'tgl_check_out' => 'required|date|after:tgl_check_in',
            'kamar_detail' => 'required|array',
            'kamar_detail.*.id_kamar' => 'required|numeric|exists:kamar,id_kamar',
        ]);

        function getNomorPemesanan()
        {
            $lastPemesanan = Pemesanan::orderBy('id_pemesanan', 'desc')->first();
            $nextId = $lastPemesanan ? $lastPemesanan->id_pemesanan + 1 : 1;

            return 'PM-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        }

        $nama_tamu = $req->nama_tamu ?? $req->nama_pemesan;
        $email_pemesan = $req->email_pemesan ?? $user->email;
        $nomorPemesanan = getNomorPemesanan();
        $jumlah_kamar = count($req->kamar_detail);

        DB::beginTransaction();

        try {
            $pemesanan = Pemesanan::create([
                'nomor_pemesanan' => $nomorPemesanan,
                'id_user' => $id_user,
                'nama_pemesan' => $req->nama_pemesan,
                'nama_tamu' => $nama_tamu,
                'email_pemesan' => $email_pemesan,
                'tgl_check_in' => $req->tgl_check_in,
                'tgl_check_out' => $req->tgl_check_out,
                'jumlah_kamar' => $jumlah_kamar,
                'status_pemesanan' => 'baru',
            ]);

            $detail_pemesanan = [];

            foreach ($req->kamar_detail as $detail) {
                $kamar = Kamar::with('tipeKamar')->where('id_kamar', $detail['id_kamar'])->first();

                if (!$kamar) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Kamar dengan ID ' . $detail['id_kamar'] . ' tidak ditemukan.',
                    ], 404);
                }

                $existingOrder = Detail::where('id_kamar', $detail['id_kamar'])
                    ->whereHas('pemesanan', function ($query) {
                        $query->where('status_pemesanan', '!=', 'check_out');
                    })
                    ->first();

                if ($existingOrder) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Kamar dengan ID ' . $detail['id_kamar'] . ' sudah ada pemesanan aktif.',
                    ], 400);
                }

                $detailPemesanan = Detail::create([
                    'id_pemesanan' => $pemesanan->id_pemesanan,
                    'id_kamar' => $detail['id_kamar'],
                    'harga' => $kamar->tipeKamar->harga,
                    'tgl_akses' => $req->tgl_check_in,
                ]);

                $detail_pemesanan[] = [
                    'id_detail_pemesanan' => $detailPemesanan->id_detail_pemesanan,
                    'id_pemesanan' => $detailPemesanan->id_pemesanan,
                    'id_kamar' => $detail['id_kamar'],
                    'tgl_akses' => $req->tgl_check_in,
                    'harga' => $kamar->tipeKamar->harga,
                ];
            }

            DB::commit();

            $responseData = [
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'nomor_pemesanan' => $pemesanan->nomor_pemesanan,
                'nama_pemesan' => $pemesanan->nama_pemesan,
                'email_pemesan' => $pemesanan->email_pemesan,
                'tgl_pemesanan' => $pemesanan->created_at,
                'tgl_check_in' => $pemesanan->tgl_check_in,
                'tgl_check_out' => $pemesanan->tgl_check_out,
                'nama_tamu' => $pemesanan->nama_tamu,
                'jumlah_kamar' => $jumlah_kamar,
                'status_pemesanan' => $pemesanan->status_pemesanan,
                'id_user' => $id_user,
                'detail_pemesanan' => $detail_pemesanan,
            ];

            return response()->json([
                'status' => true,
                'message' => 'Pemesanan berhasil dibuat.',
                'data' => $responseData,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Pemesanan gagal dibuat: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateOrder(Request $req, $id_pemesanan)
    {
        $user = auth()->user();
        $id_user = $user->id;

        $req->validate([
            'nama_pemesan' => 'required|string',
            'nama_tamu' => 'nullable|string',
            'email_pemesan' => 'nullable|email',
            'tgl_check_in' => 'required|date',
            'tgl_check_out' => 'required|date|after:tgl_check_in',
            'kamar_detail' => 'required|array',
            'kamar_detail.*.id_kamar' => 'required|numeric|exists:kamar,id_kamar',
        ]);

        DB::beginTransaction();

        try {

            $pemesanan = Pemesanan::findOrFail($id_pemesanan);
            $statusSaatIni = $pemesanan->status_pemesanan;

            $pemesanan = Pemesanan::find($id_pemesanan);

            if (!$pemesanan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Pemesanan tidak ditemukan.',
                ], 404);
            }

            $nama_tamu = $req->nama_tamu ?? $req->nama_pemesan;
            $email_pemesan = $req->email_pemesan ?? $user->email;
            $jumlah_kamar = count($req->kamar_detail);

            $pemesanan->update([
                'nama_pemesan' => $req->nama_pemesan,
                'nama_tamu' => $nama_tamu,
                'email_pemesan' => $email_pemesan,
                'tgl_check_in' => $req->tgl_check_in,
                'tgl_check_out' => $req->tgl_check_out,
                'jumlah_kamar' => $jumlah_kamar,
                'status_pemesanan' => $statusSaatIni,
            ]);

            Detail::where('id_pemesanan', $id_pemesanan)->delete();

            $detail_pemesanan = [];

            foreach ($req->kamar_detail as $detail) {
                $kamar = Kamar::with('tipeKamar')->where('id_kamar', $detail['id_kamar'])->first();

                if (!$kamar) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Kamar dengan ID ' . $detail['id_kamar'] . ' tidak ditemukan.',
                    ], 404);
                }

                $existingOrder = Detail::where('id_kamar', $detail['id_kamar'])
                    ->whereHas('pemesanan', function ($query) {
                        $query->where('status_pemesanan', '!=', 'check_out');
                    })
                    ->first();

                if ($existingOrder) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Kamar dengan ID ' . $detail['id_kamar'] . ' sedang ada pemesanan aktif.',
                    ], 400);
                }

                $detailPemesanan = Detail::create([
                    'id_pemesanan' => $id_pemesanan,
                    'id_kamar' => $detail['id_kamar'],
                    'harga' => $kamar->tipeKamar->harga,
                    'tgl_akses' => $req->tgl_check_in,
                ]);

                $detail_pemesanan[] = [
                    'id_detail_pemesanan' => $detailPemesanan->id_detail_pemesanan,
                    'id_pemesanan' => $id_pemesanan,
                    'id_kamar' => $detail['id_kamar'],
                    'tgl_akses' => $req->tgl_check_in,
                    'harga' => $kamar->tipeKamar->harga,
                ];
            }

            DB::commit();

            $responseData = [
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'nomor_pemesanan' => $pemesanan->nomor_pemesanan,
                'nama_pemesan' => $pemesanan->nama_pemesan,
                'email_pemesan' => $pemesanan->email_pemesan,
                'tgl_pemesanan' => $pemesanan->created_at,
                'tgl_check_in' => $pemesanan->tgl_check_in,
                'tgl_check_out' => $pemesanan->tgl_check_out,
                'nama_tamu' => $pemesanan->nama_tamu,
                'jumlah_kamar' => $jumlah_kamar,
                'status_pemesanan' => $statusSaatIni,
                'id_user' => $id_user,
                'detail_pemesanan' => $detail_pemesanan,
            ];

            return response()->json([
                'status' => true,
                'message' => 'Pemesanan berhasil diperbarui.',
                'data' => $responseData,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui pemesanan: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function getNota($id_pemesanan)
    {
        $pemesanan = Pemesanan::with(['detailPemesanan.kamar.tipeKamar', 'user'])->find($id_pemesanan);

        if (!$pemesanan) {
            return response()->json(['status' => false, 'message' => 'Pemesanan tidak ditemukan'], 404);
        }

        $total_harga = $pemesanan->detailPemesanan->sum('harga');

        $data = [
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'nomor_pemesanan' => $pemesanan->nomor_pemesanan,
            'nama_pemesan' => $pemesanan->nama_pemesan,
            'email_pemesan' => $pemesanan->email_pemesan,
            'tgl_pemesanan' => $pemesanan->tgl_pemesanan,
            'tgl_check_in' => $pemesanan->tgl_check_in,
            'tgl_check_out' => $pemesanan->tgl_check_out,
            'jumlah_kamar' => $pemesanan->jumlah_kamar,
            'status_pemesanan' => $pemesanan->status_pemesanan,
            'detail' => $pemesanan->detailPemesanan,
            'total_harga' => $total_harga,
        ];

        $pdf = PDF::loadView('booking', $data);

        return $pdf->download('nota_pemesanan_' . $pemesanan->nomor_pemesanan . '.pdf');
    }
}

