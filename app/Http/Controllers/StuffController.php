<?php

namespace App\Http\Controllers;

use App\Models\Stuff;
use App\Helpers\ApiFormatter;
use App\Models\InboundStuff;
use App\Models\Lending;
use App\Models\StuffStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StuffController extends Controller
{
    public function __construct()
{
    $this->middleware('auth:api');
}
    public function index()
    {
        try {
            $data = Stuff::with('stock')->get();

            return ApiFormatter::sendResponse(200, true, 'success', $data);
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, false, 'bad request', $err->getMessage());
        }


        // $stuff = Stuff::with('stock')->get();

        // return ApiFormatter::sendResponse(200, true, 'Lihat Semua Barang', $stuff);

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Lihat semua barang',
        //     'data' => $stuff
        // ], 200);
    }

    public function store(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required',
        //     'category' => 'required',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Semua Kolom Wajib Diisi',
        //         'data' => $validator->errors()
        //     ], 400);
        // } else {
        //     $stuff = Stuff::create([
        //         'name' => $request->input('name'),
        //         'category' => $request->input('category'),
        //     ]);

        //     if ($stuff) {
        //         return response()->json([
        //             'success' => true,
        //             'message' => 'Barang Berhasil Disimpan!',
        //             'data' => $stuff
        //         ]);
        //     } else {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Barang Gagal Disimpan!',
        //         ], 400);
        //     }
        // }

        try{
            $this->validate($request, [
                'name' => 'required',
                'category' => 'required',
            ]);
            $stuff = Stuff::create([
                'name' => $request->input('name'),
                'category' => $request->input('category'),
            ]);
            return ApiFormatter::sendResponse(201, true, 'Barang Berhasil Disimpan!', $stuff);
        } catch (\Throwable $th) {
            if ($th->validator->errors()) {
                return ApiFormatter::sendResponse(400, false,
                 'Terdapat Kesalahan Input Silakan Coba Lagi!', $th->validator->errors());
            }else{
                return ApiFormatter::sendResponse(400, false,
                 'Terdapat Kesalahan Input Silakan Coba Lagi!', $th->getMessage());
            }
        }
    }

    public function show($id)
    {
        try {
            $stuff = Stuff::with('stock')->findOrFail($id);

            return ApiFormatter::sendResponse(200, true, "Lihat Barang Dengan ID $id", $stuff);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Data Dengan ID $id tidak ditemukan");
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $stuff = Stuff::findOrFail($id);
            $name  = ($request->name) ? $request->name : $stuff->name;
            $category  = ($request->category) ? $request->category :$stuff->category;

            $stuff->update([
                'name' => $name,
                'category' => $category
            ]);

            return ApiFormatter::sendResponse(200, true, "Berhasil Ubah Data Dengan ID $id");   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $stuffStock = StuffStock::where('stuff_id', $id)->first();
            $inboundstuff = InboundStuff::where('stuff_id', $id)->first();
            $lending = Lending::where('stuff_id', $id)->first();

            if ($lending) {
                return ApiFormatter::sendResponse(400, false, 'Tidak dapat menghapus data stuff, sudah terdapat data lending!!!', $lending);
            }elseif ($inboundstuff) {
                return ApiFormatter::sendResponse(400, false, 'Tidak dapat menghapus data stuff, sudah terdapat data inbound!!!', $inboundstuff);
            }elseif ($stuffStock) {
                return ApiFormatter::sendResponse(400, false, 'Tidak dapat menghapus data stuff, sudah terdapat data stuffstock!!!', $stuffStock );
            } else{
                $stuff = stuff::findORFail($id);
                $stuff->delete();
                return ApiFormatter::sendResponse(200, true, 'Data stuff dengan id ' . $stuff['id'] . ' berhasil dihapus.', $stuff);
            }



            // return response()->json([
            //  'success' => true,
            //  'message' => 'Barang Hapus Data dengan id $id',
            //     'data' => $stuff
            // ],200);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(400, false, 'Barang dengan data ' . $id . ' gagal dihapus!!', $th->getMessage());
            // return response()->json([
            // 'success' => false,
            // 'message' => 'Proses gagal! data dengan id $id tidak ditemukan',
            // ],400);
        }
    }

    public function deleted()
    {
        try {
            $stuffs = Stuff::onlyTrashed()->get();

            return ApiFormatter::sendResponse(200, true, "Lihat Data Barang Yang Dihapus", $stuffs);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }
    
    public function restore($id)
    {
        try {
            $stuff = Stuff::onlyTrashed()->where('id', $id);

            $stuff->restore();

            return ApiFormatter::sendResponse(200, true, "Berhasil Mengembalikan Data Yang Telah Di Hapus!", ['id' => $id]);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function restoreAll()
    {
        try {
            $stuffs = Stuff::onlyTrashed();

            $stuffs->restore();

            return ApiFormatter::sendResponse(200, true, "Berhasil Mengembalikan Semua Data Yang Telah Di Hapus!");   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function permanentDelete($id)
    {
        try {
            $stuff = Stuff::onlyTrashed()->where('id', $id)->forceDelete();
            
            return ApiFormatter::sendResponse(200, true, "Berhasil Berhasil Hapus Permanent Data Yang Telah Di Hapus!", ['id' => $id]);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function permanentDeleteAll()
    {
        try {
            $stuff = Stuff::onlyTrashed()->forceDelete();

            return ApiFormatter::sendResponse(200, true, "Berhasil Berhasil Hapus Permanent Data Yang Telah Di Hapus!");   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }
}