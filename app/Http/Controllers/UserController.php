<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function profileEdit()
    {
        return view('profile.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function profileUpdate(Request $request, string $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'password' => 'nullable|string|min:8|confirmed',
            'email' => 'nullable|email|max:255|unique:users,email,' . $id,
        ]);
        $user = User::findOrFail($id);

        if ($request->filled('name')) {
            $user->name = $request->input('name');
        }

        if ($request->hasFile('img')) {
            if ($user->img) {
                Storage::delete('public/' . $user->img);
            }
            $imagePath = $request->file('img')->store('users', 'public');
            $user->img = $imagePath;
        }

        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        if ($request->filled('email')) {
            $user->email = $request->input('email');
        }

        $user->save();
        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
