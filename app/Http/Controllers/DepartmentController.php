<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class DepartmentController extends Controller
{
    // Get all departments
    public function index()
    {
        $departments = Department::all();
        return response()->json($departments, 200);
    }

    // Create a new department (only accessible by admin)
    public function store(Request $request)
    {
        try {
            // Check if the user is authenticated
            $user = JWTAuth::parseToken()->authenticate();

            // If the user is authenticated but not an admin
            if ($user && $user->role !== 'admin') {
                return response()->json(['error' => 'You do not have the necessary permissions to add a department.'], 403);
            }

            // If the user is not authenticated
            if (!$user) {
                return response()->json(['error' => 'You must be logged in to add a department.'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'nom_dep' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            // Create department
            $department = Department::create($request->all());
            return response()->json(['department' => $department], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
    }

    // Update an existing department
    public function update(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'You do not have the necessary permissions to update a department.'], 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json(['error' => 'Department not found'], 404);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'nom_dep' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Update department
        $department->update($request->all());
        return response()->json(['department' => $department], 200);
    }

    // Delete a department
    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'You do not have the necessary permissions to delete a department.'], 403);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json(['error' => 'Department not found'], 404);
        }

        // Delete department
        $department->delete();
        return response()->json(['message' => 'Department deleted successfully'], 200);
    }

    // Check if user is admin using token
    public function checkAdmin()
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user && $user->role === 'admin') {
            return response()->json(['message' => 'User is admin'], 200);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }
    // get sp belongs to departent
    public function getSpecialitesByDepartment($departmentId)
    {
        // Find the department
        $department = Department::with('specialites')->find($departmentId);

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        // Return the department and its specializations
        return response()->json([
            'department' => $department->name,
            'specialites' => $department->specialites,
        ], 200);
    }
}
