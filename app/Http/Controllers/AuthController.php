<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct() {
       $this->middleware("auth:api", ["except" => ["login", "register"]]);
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

    public function register(Request $request)
    {
        $request->validate([
            "name" => ["required", "string"],
            "email" => ["required", "email", "string", "unique:users"],
            "password" => ["required"]
        ]);

        $data = $request->only(["name", "email", "password"]);

        $user = $this->create($data);
        $token = Auth::login($user);
        return $this->respondWithToken($token);

    }

    public function respondWithToken($token)
    {   
        $user = Auth::user();
        $user->avatar = url("/media/avatars/{$user->avatar}");
        return response()->json([
            "user" => Auth::user(),
            "token" => $token,
            "token_type" => "Bearer",
        ]);
    }

    public function create($data)
    { 
        $user = new User;
        $user->name = $data["name"];
        $user->email = $data["email"];
        $user->password = Hash::make($data["password"]);
        $user->save();

        return $user;

    }
}
