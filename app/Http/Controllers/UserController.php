<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Get all users if the authenticated user is an admin.
     */
    public function getAllUsers(): JsonResponse
    {
        // Return all users if admin
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Update a user's information if the authenticated user is an admin.
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        // Authenticate the user
        $authUser = JWTAuth::parseToken()->authenticate();

        // Ensure the authenticated user is updating their own profile
        if ($authUser->id != $id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $authUser->id,
            'phone' => 'sometimes|string|max:15',
            'profile_photo' => 'sometimes|image|mimes:jpg,jpeg,png,gif|max:2048',  // Validation for the profile photo
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Handle the file upload if a profile photo is provided
        if ($request->hasFile('profile_photo')) {
            // Store the file in the 'user/photo_profile' directory
            $photoPath = $request->file('profile_photo')->store('user/photo_profile', 'public');
            // Save the path in the 'profile_photo_path' field
            $authUser->profile_photo_path = $photoPath;
        }

        // Update the authenticated user's profile
        $authUser->update($request->except('profile_photo'));  // Don't update the photo directly

        return response()->json(['message' => 'Profile updated successfully', 'user' => $authUser]);
    }

    /**
     * Delete a user if the authenticated user is an admin.
     */
    public function deleteUser(int $id): JsonResponse
    {
        // Check if the user has an admin role
        $user = JWTAuth::parseToken()->authenticate();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find and delete the user
        $userToDelete = User::findOrFail($id);
        $userToDelete->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
