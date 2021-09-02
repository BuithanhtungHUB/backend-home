<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
//        $this ->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized']);
        }

        return $this->createNewToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required|string|between:2,100',
            'phone' => 'required|regex:/(0)+[0-9]{8}\b/',
            'password' => 'required|confirmed|between:6,8',
            'email' => 'required|email',
            'role' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson());
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ]);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Signed out successfully']);
    }

    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    public function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
}