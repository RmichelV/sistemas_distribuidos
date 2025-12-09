<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Firebase\JWT\JWT;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
        ]);
        return response()->json(['id' => $user->id], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'branch_id' => $user->branch_id,
            'iat' => time(),
            'exp' => time() + 60 * 60 * 4, // 4h
        ];
        $token = JWT::encode($payload, env('JWT_SECRET', 'please_change_me'), 'HS256');
        return response()->json(['token' => $token]);
    }
}
