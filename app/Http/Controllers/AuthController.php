<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function loginPage()
    {
        return view('login');
    }

    public function login(LoginFormRequest $request)
    {
        if (Auth::attempt($request->validated())) {
            return redirect()->route('admin.products.index');
        }

        return redirect()->back()->with('error', 'Invalid login credentials');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        // Invalidate and regenerate session
        $request->session()->invalidate();
        $request->session()->regenerate();

        return redirect()->route('login');
    }
}
