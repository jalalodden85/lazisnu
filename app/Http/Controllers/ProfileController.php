<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan formulir pengaturan profil dan ubah password mandiri
     */
    public function edit(): View
    {
        $user = Auth::user();

        return view('profile.edit', compact('user'));
    }

    /**
     * Simpan pembaruan informasi profil dan foto avatar mandiri
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'  => ['nullable', 'string', 'max:25'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required'   => 'Nama lengkap wajib diisi.',
            'email.required'  => 'Alamat email wajib diisi.',
            'email.email'     => 'Format alamat email tidak valid.',
            'email.unique'    => 'Alamat email ini sudah terdaftar oleh pengguna lain.',
            'avatar.image'    => 'File harus berupa gambar.',
            'avatar.mimes'    => 'Format foto profil hanya boleh JPG, JPEG, PNG, atau WEBP.',
            'avatar.max'      => 'Ukuran foto profil maksimal 2MB.',
        ]);

        // Pengelolaan unggah foto profil baru & pembersihan foto lama
        if ($request->hasFile('avatar')) {
            // Hapus file avatar lama dari server jika ada dan file fisiknya eksis
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Simpan foto avatar baru ke dalam folder avatars pada disk public
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->save();

        return redirect()->route('profile.edit')
            ->with('profile_success', 'Informasi profil dan foto berhasil diperbarui.');
    }

    /**
     * Ubah kata sandi (password) mandiri dengan verifikasi password lama
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.min'              => 'Password baru minimal harus 6 karakter.',
            'password.confirmed'        => 'Konfirmasi password baru tidak cocok.',
            'password.different'        => 'Password baru harus berbeda dari password saat ini.',
        ]);

        // Verifikasi kecocokan password saat ini menggunakan Hash::check
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()
                ->withErrors([
                    'current_password' => 'Password saat ini tidak sesuai.',
                ])
                ->with('password_error', 'Gagal memperbarui password. Periksa kembali password saat ini Anda.')
                ->withFragment('card-password');
        }

        // Simpan kata sandi baru (terenkripsi)
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return redirect()->route('profile.edit')
            ->with('password_success', 'Kata sandi berhasil diperbarui. Silakan gunakan kata sandi baru untuk login berikutnya.')
            ->withFragment('card-password');
    }
}
