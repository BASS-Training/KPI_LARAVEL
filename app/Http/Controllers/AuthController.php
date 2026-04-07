<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required',
            'name' => 'required'
        ]);

        $employee = User::where('nip', $request->nip)
            ->where('nama', $request->name)
            ->first();

        if (!$employee) {
            return back()->withErrors(['error' => 'NIP atau Nama tidak ditemukan']);
        }

        Session::put('user_id', $employee->id);
        Session::put('user', $employee);
        
        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Session::forget('user_id');
        Session::forget('user');
        return redirect()->route('login');
    }

    public function getUser()
    {
        return Session::get('user');
    }
}
