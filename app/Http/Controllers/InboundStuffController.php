<?php

namespace App\Http\Controllers;

use App\models\InboundStuff;
use App\models\Stuff;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\models\StuffStock;
use App\Helpers\ApiFormatter;
use Illuminate\Support\Facades\File;

class InboundStuffController extends Controller
{
    public function __construct()
{
    $this->middleware('auth:api');
}
    public function index(){
        // $inboundStuff = InboundStuff::all();

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Lihat semua Data',
        //     'data' => $inboundStuff
        // ],200);

        try {
            $data = InboundStuff::with('stuff', 'stuff.stock')->get();

            return ApiFormatter::sendResponse(200, true, 'success', $data);
        } catch (\Exception $err) {
            return ApiFormatter::sendResponse(400, false, 'bad request', $err->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stuff_id' => 'required',
            'total' => 'required',
            'date' => 'required',
            'proff_file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Semua kolom wajib disi!',
                'data' => $validator->errors()
            ], 400);
        } else {
            $file = $request->file('proff_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(app()->basePath('public/uploads'), $fileName);

            $inboundStuff = InboundStuff::create([
                'stuff_id' => $request->input('stuff_id'),
                'total' => $request->input('total'),
                'date' => $request->input('date'),
                'proff_file' => $fileName,
            ]);

          
            // Update total_available in StuffStock
            $stuffStock = StuffStock::where('stuff_id', $request->input('stuff_id'))->first();
            $total_stock = (int)$stuffStock->total_available + (int)$request->input('total');

            $stuffStock->update([
                'total_available' => (int)$total_stock
            ]);
         if($inboundStuff && $stuffStock){
            return ApiFormatter::sendResponse(201, true, "Barang masuk berhasil disimpan");
         }else{
            return ApiFormatter::sendResponse(400, false, 'barang masuk gagal disimpan');
         }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $inboundStuff
            ], 201);
        }
    }public function show($id)
    {
        try {
            $inboundStuff = InboundStuff::with('stuff', 'stuff.stock')->findOrFail($id);
            return ApiFormatter::sendResponse(200, true, "Lihat barang masuk dengan id $id", $inboundStuff);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "data dengan id $id tidak ditemukan",$th->getMessage());
        }
    }
    
    public function update(Request $request, $id)
{
    try {
        $inboundStuff = InboundStuff::findOrFail($id);

        $stuff_id = ($request->stuff_id) ? $request->stuff_id : $inboundStuff->stuff_id;
        $total = ($request->total) ? $request->total : $inboundStuff->total;
        $date = ($request->date) ? $request->date : $inboundStuff->date;
   
   if($request->file('proff_file')!== NULL){
        $file = $request->file('proff_file');
        $fileName = $stuff_id . '_'. strtotime($date) . strtotime(date('H.I')). '.' ;
        $file->move('proof', $fileName);
   }else{
    $fileName = $inboundStuff->proff_file;
   }
   $total_s = $total - $inboundStuff->total;
   $total_stock= (int)$inboundStuff->stuff->stock->total_available + $total_s;
   $inboundStuff->stuff->stock->update([
    'total-available' => (int)$total_stock
   ]);


        if ($inboundStuff) {
            $inboundStuff->update([
                'stuff_id' => $stuff_id,
                'total' => $total,
                'date' => $date,
                'proff_file' => $fileName,
            ]);

            return ApiFormatter::sendResponse(200, true, "berhasil ubah data yang masuk dengan id $id",$inboundStuff);
        } else {
            return ApiFormatter::sendResponse(400, false, "Proses gagal");
        }
    } catch (\Throwable $th) {
        return ApiFormatter::sendResponse(404, false, "Proses gagal",$th->getMessage());
    }

}
    public function destroy($id){
        try{
            $inboundStuff =  InboundStuff::find($id);

            if(!$inboundStuff) {
                return response()->json([
                    'success' => false,
                    'message' => 'data dengan id ' . $id . ' tidak ditemukan',
                    ],400);
            }

            $stock = StuffStock::where('stuff_id', $inboundStuff->stuff_id)->first();

            $available_min = $stock->total_available - $inboundStuff->total;
            $available = ($available_min < 0) ? 0 : $available_min;
            $defec = ($available_min < 0) ? $stock->total_defec + ($available * -1) : $stock->total_defec;
            
            $stock->update([
                'total_available'=>$available,
                'total_defec'=>$defec
            ]);
    
            $inboundStuff->delete();
    
            return response()->json([
             'success' => true,
             'message' => 'Barang Hapus Data dengan id: ' . $id,
             'data' => $inboundStuff
            ],200);
        } catch(\Throwable){
            return response()->json([
            'success' => false,
            'message' => 'Proses gagal! data dengan id ' . $id . ' tidak ditemukan',
            ],400);
        }
    }
    public function deleted()
    {
        try {
            $inboundStuff = InboundStuff::onlyTrashed()->get();

            return ApiFormatter::sendResponse(200, true, "lihat data inboundstuff yang dihapus", $inboundStuff);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! silahkan coba lagi!",$th->getMessage());
        }
    }
    
    public function restore($id)
    {
        try {
            $inboundStuff = InboundStuff::onlyTrashed()->findOrFail($id);
            $has_inbound = InboundStuff::where('stuff_id', $inboundStuff->stuff_id)->get();

            if($has_inbound->count() == 1) {
                $message = "Data  sudah ada, tidak ada duplikat data  ,silahkan update data dengan id $inboundStuff->stuff_id";
            } else {
                $inboundStuff->restore();
                $message = "Berhasil mengembalikan data yang telah dihapus";
            }

            return ApiFormatter::sendResponse(200, true, $message, ['id' => $id, 'stuff_id' => $inboundStuff->stuff_id]);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! silahkan coba lagi!",$th->getMessage());
        }
    }
    public function restoreAll()
    {
        try {
            $inboundStuff = InboundStuff::onlyTrashed()->restore();

            return ApiFormatter::sendResponse(200, true, "Berhasil mengembalikan semua data yang telah dihapus!");
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses gagal! silahkan coba lagi!",$th->getMessage());
        }
    }
    
    public function permanentDelete($id)
    // {
    //     try {
    //         $inboundStuff = InboundStuff::onlyTrashed()->where('id', $id)->forceDelete();
            
            
    //         return ApiFormatter::sendResponse(200, true, "Berhasil Berhasil Hapus Permanent Data Stocks Yang Telah Di Hapus!", ['id' => $id]);   
    //     } catch (\Throwable $th) {
    //         return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
    //     }
    // }
    {
        try {
            $inboundStuff = inboundStuff::onlyTrashed()->where('id', $id)->first();
            

            if ($inboundStuff) {
                $imageName = $inboundStuff->proff_file;
                $check = InboundStuff::onlyTrashed()->where('id', $id)->get();
                File::delete('uploads/' . $imageName);
                $inboundStuff->forceDelete();
                return ApiFormatter::sendResponse(200, true, 'Berhasil menghapus permanen data dengan id = ' . $id . 'dan berhasil menghapus semua data permanent dengan file name: ' . $imageName, $check);
            } else {
                return ApiFormatter::sendResponse(200, true, 'Bad request');
            }

        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, 'Proses gagall', $th->getMessage());
        }
    }
    public function permanentDeleteAll()
    {
        try {
            $inboundStuff = inboundStuff::onlyTrashed()->forceDelete();
            if ($inboundStuff) {
                return ApiFormatter::sendResponse(200, true, 'Berhasil menghapus permanen semua data');
            } else {
                return ApiFormatter::sendResponse(400, false, 'bad request');
            }
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, 'Proses gagall', $th->getMessage());
        }
    }
}