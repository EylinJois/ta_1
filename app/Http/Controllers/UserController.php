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
            'password' => 'nullable|string|min:8|confirmed',
            'email' => 'nullable|email|max:255|unique:users,email,' . $id,
        ]);
        $user = User::findOrFail($id);

        if ($request->filled('name')) {
            $user->name = $request->input('name');
        }

        if ($request->hasFile('profile_media')) {
            $file = $request->file('profile_media');
            $path = $file->store('profile_media', 'public');

            // Delete the old profile media if it exists
            if ($user->profileMedia) {
                Storage::disk('public')->delete($user->profileMedia->file_path);
                $user->profileMedia()->delete();
            }

            // Create a new MediaAsset for the uploaded file
            $mediaAsset = new \App\Models\MediaAsset();
            $mediaAsset->file_path = $path;
            $mediaAsset->original_name = $file->getClientOriginalName();
            $mediaAsset->mime = $file->getClientMimeType();
            $mediaAsset->size = $file->getSize();
            $mediaAsset->uploaded_by = auth()->id();
            $mediaAsset->save();


            $user->profile_media_id = $mediaAsset->id;
        } else {
            if ($user->profileMedia) {
                $user->profile_media_id = $user->profileMedia->id;
            } else {
                $user->profile_media_id = null; // No profile media
            }   
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
