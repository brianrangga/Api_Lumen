<?php

namespace App\Http\Controllers;

use App\Models\Stuff;
use App\Models\StuffStock;
use App\Helpers\ApiFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StuffStockController extends Controller
{
    public function __construct()
{
    $this->middleware('auth:api');
}
    public function index()
    {

        $stuffStock = StuffStock::with('stuff')->get();

        return ApiFormatter::sendResponse(200, true, 'Lihat Semua Stock', $stuffStock);

        // $stuffStock = StuffStock::all();

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Lihat semua barang masuk',
        //     'data' => $stuffStock
        // ], 200);
    }

    public function store(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'stuff_id' => 'required',
        //     'total_available' => 'required',
        //     'total_defec' => 'required',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'semua kolom wajib di isi',
        //         'data' => $validator->errors()
        //     ], 400);
        // } else {

        //     $stock = StuffStock::updateOrCreate([
        //         'stuff_id' => $request->input('stuff_id'),
        //     ], [
        //         'total_available' => $request->input('total_available'),
        //         'total_defec' => $request->input('total_defec'),
        //     ]);

        //     if ($stock) {
        //         return response()->json([
        //             'success' => true,
        //             'message' => 'stock berhasil disimpan',
        //             'data' => $stock,
        //         ], 201);
        //     } else {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'stock gagal disimpan',
        //         ], 400);
        //     }
        // }

        try{
            $this->validate($request, [
                'stuff_id' => 'required',
                'total_available' => 'required',
                'total_defec' => 'required',
            ]);
            $stock = StuffStock::create([
                'stuff_id' => $request->input('stuff_id'),
                'total_available' => $request->input('total_available'),
                'total_defec' => $request->input('total_defec'),
            ]);
            return ApiFormatter::sendResponse(201, true, 'Barang Berhasil Disimpan!', $stock);
        } catch (\Throwable $th) {
            if ($th->validator->errors()) {
                return ApiFormatter::sendResponse(400, false,
                 'Stock Berhasil Disimpan!', $th->validator->errors());
            }else{
                return ApiFormatter::sendResponse(400, false,
                 'Stock Gagal Disimpan!', $th->getMessage());
            }
        }
    }

    public function show($id)
    {
        // try {
        //     $stock = StuffStock::with('stuff')->find($id);

        //     return response()->json([
        //         'success' => true,
        //         'message' => "lihat stock dengan id $id",
        //         'data' => $stock
        //     ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => "data stock dengan id $id tidak ditemukan"
        //     ], 404);
        // }

        try {
            $stock = StuffStock::with('stuff')->findOrFail($id);

            return ApiFormatter::sendResponse(200, true, "Lihat Stock Dengan ID $id", $stock);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Data Stock ID $id tidak ditemukan");
        }

    }

    public function update(Request $request, $id)
    {
        // try {
        //     $stock = StuffStock::with('stuff')->find($id);

        //     $stuff_id = ($request->stuff_id) ? $request->stuff_id : $stock->stuff_id;
        //     $total_available = ($request->total_available) ? $request->total_available : $stock->total_available;
        //     $total_defec = ($request->total_defec) ? $request->total_defec : $stock->total_defec;

        //     if ($stock) {
        //         $stock->update([
        //             'stuff_id' => $stuff_id,
        //             'total_available' => $total_available,
        //             'total_defec' => $total_defec
        //         ]);

        //         return response()->json([
        //             'success' => true,
        //             'message' => "Berhasil mengubah data stock dengan id $id",
        //             'data' => $stock,
        //         ], 200);
        //     } else {
        //         return response()->json([
        //             'success' => false,
        //             'message' => "Proses Gagal!"
        //         ], 404);
        //     }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => "Proses Gagal! data stock dengan id $id tidak ditemukan!",
        //     ], 404);
        // }

        try {
            $stock = StuffStock::findOrFail($id);

            $total_available = ($request->total_available) ? $request->total_available : $stock->total_available;
            $total_defec = ($request->total_defec) ? $request->total_defec : $stock->total_defec;

            $stock->update([
                'total_available' => $total_available,
                'total_defec' => $total_defec
            ]);

            return ApiFormatter::sendResponse(200, true, "Berhasil Ubah Data Dengan ID $id");   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function destroy($id)
    {
        // try {
        //     $stuffstock = StuffStock::findOrFail($id);

        //     $stuffstock->delete();

        //     return response()->json([
        //         'success' => true,
        //         'message' => "Berhasil hapus data stock dengan id $id",
        //         'data' => ['id' => $id,]
        //     ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => "Proses gagal! data stock dengan id $id tidak ditemukan",
        //     ], 404);
        // }

        try {
            $stuffstock = StuffStock::findOrFail($id);

            $stuffstock->delete();

            return ApiFormatter::sendResponse(200, true, "Berhasil Hapus Barang Dengan ID $id", ['id' => $id]);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function deleted()
    {
        try {
            $stocks = StuffStock::onlyTrashed()->get();

            return ApiFormatter::sendResponse(200, true, "Lihat Data Stock Yang Dihapus", $stocks);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $stock = StuffStock::onlyTrashed()->findOrFail($id);
        
            $stock->restore();

            return ApiFormatter::sendResponse(200, true, "Berhasil Mengembalikan Data Yang Telah Dihapus", $stock);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }


    public function restoreAll()
    {
        try {
            $stocks = StuffStock::onlyTrashed()->restore();

            return ApiFormatter::sendResponse(200, true, "Berhasil Mengembalikan Semua Data Stock Yang Telah Di Hapus!");   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function permanentDelete($id)
    {
        try {
            $stock = StuffStock::onlyTrashed()->where('id', $id)->forceDelete();
            
            return ApiFormatter::sendResponse(200, true, "Berhasil Berhasil Hapus Permanent Data Stocks Yang Telah Di Hapus!", ['id' => $id]);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function permanentDeleteAll()
    {   
        try {
            $stocks = StuffStock::onlyTrashed()->forceDelete();

            return ApiFormatter::sendResponse(200, true, "Berhasil Berhasil Hapus Permanent Data Yang Telah Di Hapus!");   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }
}