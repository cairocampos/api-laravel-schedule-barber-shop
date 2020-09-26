<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct() {
       $this->middleware("auth:api", ["except" => ["login", "create"]]);
    }

    public function login(Request $request)
    {      
        $request->validate([
            "email" => ["required", "email", "string", "max:100"],
            "password" => ["required", "string"],
        ]);

        $credentials = $request->only(["email", "password"]);

        $token = Auth()->attempt($credentials);
        if(!$token) {
            return response()->json(["error" => "Unauthorized" ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function respondWithToken($token)
    {
        return response()->json([
            "access_token" => $token,
            "token_type" => "bearer",
            "expires_in" => Auth::factory()->getTTL() * 60
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->only(["name", "email", "password"]);        

        $user = new User;
        $user->name = $data["name"];
        $user->email = $data["email"];
        $user->password = Hash::make($data["password"]);
        $user->save();

        return $user;

    }
}
