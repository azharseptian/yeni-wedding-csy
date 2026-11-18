<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /** Pendaftaran mandiri hanya untuk Calon Pengantin (Klien). */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_pengantin_wanita' => ['required', 'string', 'max:100'],
            'nama_pengantin_pria' => ['required', 'string', 'max:100'],
            'nama_kontak' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'kota' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['phone.regex' => 'Nomor WhatsApp tidak valid (contoh: 081234567890).']);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'role_id' => Role::where('name', RoleName::Klien->value)->value('id'),
                'name' => $data['nama_kontak'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);
            Client::create(['user_id' => $user->id] + collect($data)->except('password', 'password_confirmation')->all());

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.beranda')->with('success', 'Pendaftaran berhasil. Selamat datang di Yeni Wedding!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function home(Request $request): RedirectResponse
    {
        return redirect()->route(match ($request->user()->role->name) {
            RoleName::Owner => 'admin.dashboard',
            RoleName::TimInternal => 'tim.jadwal',
            RoleName::VendorKeluarga => 'vendor.beranda',
            RoleName::Klien => 'portal.beranda',
        });
    }
}
