<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Specialite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SpecialiteController extends Controller
{
    // Get all specialites
    public function index()
    {
        $specialites = Specialite::all();
        return response()->json($specialites, 200);
    }

    // Create a new specialite (only accessible by admin, and valid department required)
    public function store(Request $request)
    {
        try {
            // Check if the user is authenticated and has the 'admin' role
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'You must be logged in to add a specialite.'], 401);
            }

            if ($user->role !== 'admin') {
                return response()->json(['error' => 'You do not have the necessary permissions to add a specialite.'], 403);
            }

            // Validate the input
            $validator = Validator::make($request->all(), [
                'nom_sp' => 'required|string|max:255',
                'id_dep' => 'required|exists:departments,id', // Ensure that the department exists
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            // Check if the department exists
            $department = Department::find($request->id_dep);
            if (!$department) {
                return response()->json(['error' => 'Department not found. Cannot add specialite.'], 404);
            }

            // Create specialite
            $specialite = Specialite::create($request->all());
            return response()->json(['specialite' => $specialite], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
    }

    // Update an existing specialite
    public function update(Request $request, $id)
    {
        $specialite = Specialite::find($id);

        if (!$specialite) {
            return response()->json(['error' => 'Specialite not found'], 404);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'You do not have the necessary permissions to update a specialite.'], 403);
        }

        // Validate the input
        $validator = Validator::make($request->all(), [
            'nom_sp' => 'required|string|max:255',
            'id_dep' => 'required|exists:departments,id', // Ensure the department exists
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Check if the department exists
        $department = Department::find($request->id_dep);
        if (!$department) {
            return response()->json(['error' => 'Department not found. Cannot update specialite.'], 404);
        }

        // Update specialite
        $specialite->update($request->all());
        return response()->json(['specialite' => $specialite], 200);
    }

    // Delete a specialite
    public function destroy($id)
    {
        $specialite = Specialite::find($id);

        if (!$specialite) {
            return response()->json(['error' => 'Specialite not found'], 404);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'You do not have the necessary permissions to delete a specialite.'], 403);
        }

        // Delete specialite
        $specialite->delete();
        return response()->json(['message' => 'Specialite deleted successfully'], 200);
    }
    // get users belongs to sp
    public function getUsersBySpecialite($specialiteId)
    {
        // Find the specialization with its associated users
        $specialite = Specialite::with('users')->find($specialiteId);

        if (!$specialite) {
            return response()->json(['message' => 'Specialite not found'], 404);
        }

        return response()->json([
            'specialite' => $specialite->name,
            'users' => $specialite->users,
        ], 200);
    }
}

