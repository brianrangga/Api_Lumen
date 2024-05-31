<?php

namespace App\Http\Controllers;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Helpers\ApiFormatter;
class UserController extends Controller
{
    public function __construct()
{
    $this->middleware('auth:api');
}
    public function index()
    {
       
    $user = User::all();

    if ($user->isEmpty()) {
        return ApiFormatter::sendResponse(404, false, "tidak ada data yang ditemukan");
    }

    return ApiFormatter::sendResponse(200, true, "Data User Berhasil Ditampilkan",$user);
    }


    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                
                'username' => 'required|unique:users|min:3',
                'email' => 'required|unique:users|email',
                'password' => 'required|min:6',
                'role' => ['required', Rule::in(['admin', 'staff'])],
            ]);

            $hashedPassword = Hash::make($request->input('password'));

            $user = User::create([
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => $hashedPassword,
                'role' => $request->input('role'),
            ]);

            return ApiFormatter::sendResponse(201, true, 'User Berhasil Disimpan!', $user);
        } catch (ValidationException $th) {
            return ApiFormatter::sendResponse(400, false, 'Terdapat Kesalahan Input Silahkan Coba Lagi!', $th->validator->errors());
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(400, false, 'Terdapat Kesalahan Input Silahkan Coba Lagi!', $th->getMessage());
        }
    }

    

    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            return ApiFormatter::sendResponse(200, true, "Berhasil Melihat data User Dengan id $id", $user);
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Data Dengan ID $id tidak ditemukan",$th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try{
            $user = User::findOrFail($id);
            $username = ($request->username) ? $request->username : $user->username;
            $email = ($request->email) ? $request->email : $user->email;
            $password = ($request->password) ? $request->password : $user->password;
            $role = ($request->role) ? $request->role : $user->role;
        
        $user->update([
            'username' => $username,
            'email'=> $email,
            'password'=> $password,
            'role' => $role
        ]);
        return ApiFormatter::sendResponse(200, true, "Berhasil Mengubah Data User dengan id $id", [ 'id'=> $id]);
        }catch(\Throwable $th){
        return ApiFormatter::sendResponse(404, false, "Proses gagal silahkan coba lagi", $th->getMessage());
        }
        
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            $user->delete();

            return ApiFormatter::sendResponse(200, true, "Berhasil Hapus Barang Dengan ID $id", ['id' => $id]);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }

    public function deleted(){
        try {
            $users = User::onlyTrashed()->get();

            return ApiFormatter::sendResponse(200, true, "Berhasil Lihat Data User Yang Dihapus", $users);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }
    public function restore($id)
    {
        try {
            $user = user::onlyTrashed()->where('id', $id);

            $user->restore();

            return ApiFormatter::sendResponse(200, true, "Berhasil Mengembalikan Data Yang Telah Di Hapus!", ['id' => $id]);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }
    public function restoreAll()
    {
        try {
            $user = User::onlyTrashed();

            $user->restore();

            return ApiFormatter::sendResponse(200, true, "Berhasil Mengembalikan Semua Data Yang Telah Di Hapus!");   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }
    public function permanentDelete($id)
    {
        try {
            $user = User::onlyTrashed()->where('id', $id)->forceDelete();

            return ApiFormatter::sendResponse(200, true, "Berhasil Berhasil Hapus Permanent Data Yang Telah Di Hapus!", ['id' => $id]);   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }
    
    public function permanentDeleteAll()
    {
        try {
            $users = User::onlyTrashed()->forceDelete();

            return ApiFormatter::sendResponse(200, true, "Berhasil Berhasil Hapus Permanent Data Yang Telah Di Hapus!");   
        } catch (\Throwable $th) {
            return ApiFormatter::sendResponse(404, false, "Proses Gagal! Silakan Coba Lagi!", $th->getMessage());
        }
    }
}