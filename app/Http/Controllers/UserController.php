<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{   
    public function __construct()
    {
        $this->middleware("auth:api");
    }

    public function read()
    {   
        $user = Auth::user();
        $user->avatar = url("media/avatars/{$user->avatar}");
        return response()->json(Auth::user());
    }
}
