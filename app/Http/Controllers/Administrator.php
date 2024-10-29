<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TipeKamar;
use App\Models\Kamar;

class Administrator extends Controller
{
    //CRUD TIPE KAMAR
    public function getTipeKamar($id = null)
    {
        if ($id) {
            $tipeKamar = TipeKamar::find($id);
            if (!$tipeKamar) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tipe kamar tidak ditemukan',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'tipeKamar' => $tipeKamar
            ], 200);
        }
        $tipeKamarList = TipeKamar::all();
        return response()->json([
            'status' => 'success',
            'tipeKamar' => $tipeKamarList
        ], 200);
    }

    public function createTipeKamar(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'nama_tipe_kamar' => 'required|string',
            'harga' => 'required|numeric|min:100000',
            'deskripsi' => 'required|array',
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        if ($req->hasFile('foto')) {
            $file = $req->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $filename);
        } else {
            return response()->json(['status' => false, 'message' => 'Gambar tidak ditemukan'], 400);
        }
        $save = TipeKamar::create([
            'nama_tipe_kamar' => $req->get('nama_tipe_kamar'),
            'harga' => $req->get('harga'),
            'deskripsi' => json_encode($req->get('deskripsi')),
            'foto' => $filename,
        ]);
        if ($save) {
            return response()->json(['status' => true, 'message' => 'Sukses menambahkan']);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal menambahkan']);
        }
    }

    public function updateTipeKamar(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'nama_tipe_kamar' => 'required|string',
            'harga' => 'required|numeric',
            'deskripsi' => 'sometimes|array',
            'foto' => 'sometimes|string'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson());
        }
        $tipeKamar = TipeKamar::where('id_tipe_kamar', $id)->first();
        if (!$tipeKamar) {
            return response()->json(['status' => false, 'message' => 'Tipe kamar tidak ditemukan']);
        }
        $updateData = [
            'nama_tipe_kamar' => $req->get('nama_tipe_kamar'),
            'harga' => $req->get('harga'),
            'deskripsi' => json_encode($req->get('deskripsi')),
        ];
        if ($req->has('foto')) {
            $fotoData = $req->get('foto');
            list($type, $fotoData) = explode(';', $fotoData);
            list(, $fotoData) = explode(',', $fotoData);
            $fotoData = base64_decode($fotoData);
            $fileName = time() . '.png';
            if (file_exists(public_path('uploads/' . $tipeKamar->foto))) {
                unlink(public_path('uploads/' . $tipeKamar->foto));
            }
            file_put_contents(public_path('uploads/') . $fileName, $fotoData);
            $updateData['foto'] = $fileName;
            $update = TipeKamar::where('id_tipe_kamar', $id)->update($updateData);
            if ($update) {
                return response()->json(['status' => true, 'message' => 'Sukses mengubah']);
            } else {
                return response()->json(['status' => false, 'message' => 'Gagal mengubah']);
            }
        }
    }

    public function deleteTipeKamar($id)
    {
        $tipeKamar = TipeKamar::where('id_tipe_kamar', $id)->first();
        if (!$tipeKamar) {
            return response()->json(['status' => false, 'message' => 'Tipe kamar tidak ditemukan']);
        }
        $delete = TipeKamar::where('id_tipe_kamar', $id)->delete();
        if ($delete) {
            return Response()->json(['status' => true, 'message' => 'Sukses menghapus']);
        } else {
            return Response()->json(['status' => false, 'message' => 'Gagal menghapus']);
        }
    }

    //CRUD KAMAR
    public function getKamar(){
        $dataKamar=Kamar::get();
        return response()->json($dataKamar);
    }

    public function createKamar(Request $req){
        $validator = Validator::make($req->all(), [
            'nomor_kamar' => 'required|integer',
            'id_tipe_kamar' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $save = Kamar::create([
            'nomor_kamar' => $req->get('nomor_kamar'),
            'id_tipe_kamar' => $req->get('id_tipe_kamar'),
        ]);
        if ($save) {
            return response()->json(['status' => true, 'message' => 'Sukses menambahkan']);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal menambahkan']);
        }
    }

    public function updateKamar(Request $req, $id) {
        $validator = Validator::make($req->all(), [
            'nomor_kamar' => 'required|integer',
            'id_tipe_kamar' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson());
        }
        $Kamar = Kamar::where('id_kamar', $id)->first();
        if (!$Kamar) {
            return response()->json(['status' => false, 'message' => 'Kamar tidak ditemukan']);
        }
        $updateData = [
            'nomor_kamar' => $req->get('nomor_kamar'),
            'id_tipe_kamar' => $req->get('id_tipe_kamar'),
        ];
        $update = Kamar::where('id_kamar', $id)->update($updateData);
        if ($update) {
            return response()->json(['status' => true, 'message' => 'Sukses mengubah']);
        } else {
            return response()->json(['status' => false, 'message' => 'Gagal mengubah']);
        }
    }
    public function deleteKamar($id){
        $Kamar = Kamar::where('id_kamar', $id)->first();
        if (!$Kamar) {
            return response()->json(['status' => false, 'message' => 'Kamar tidak ditemukan']);
        }
        $delete=Kamar::where('id_kamar',$id)->delete();
        if($delete){
            return Response()->json(['status'=>true,'message' => 'Sukses menghapus']);
       } else {
            return Response()->json(['status'=>false,'message' => 'Gagal menghapus']);
       }
    }
}
