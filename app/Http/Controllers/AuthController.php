<?php


namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\ApiFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => 'login']);
    }


    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ]);


        $credentials = $request->only(['email', 'password']);


        if (! $token = Auth::attempt($credentials)) {
            return ApiFormatter::sendResponse(400, false, 'User not found', 'Silakan cek kembali email dan password anda!');
        }


        $respondWithToken = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->user(),
            'expires_in' => auth()->factory()->getTTL() * 60 * 24
        ];


        return ApiFormatter::sendResponse(200, true, 'Logged In', $respondWithToken);
    }


    public function me()
    {
        return ApiFormatter::sendResponse(200, true, 'success', auth()->user());
    }


    public function logout()
    {
        auth()->logout();


        return ApiFormatter::sendResponse(200, true, 'success', 'Berhasil logout!');
    }
}
