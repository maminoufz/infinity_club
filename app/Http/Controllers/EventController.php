<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Image;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class EventController extends Controller
{
    /**
     * Get all events along with the department and image data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllEvents()
    {
        // Fetch events with both department and image relationships
        $events = Event::with(['department', 'image'])->get();

        return response()->json(['events' => $events], 200);
    }

    /**
     * Create a new event with optional image.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addEvent(Request $request)
    {
        // Check if the user has an admin role
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'type'        => 'required|string|max:255',
            'date'        => 'required|date',
            'description' => 'required|string|max:255',
            'id_dep'      => 'required|exists:departments,id',
            'image'       => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Create the event
        $event = Event::create([
            'type'        => $request->type,
            'date'        => $request->date,
            'description' => $request->description,
            'id_dep'      => $request->id_dep,
        ]);

        // Handle image upload via Cloudinary
        if ($request->hasFile('image')) {
            try {
                // Upload to Cloudinary (uses CLOUDINARY_URL from .env automatically)
                $uploadedFile = Cloudinary::upload(
                    $request->file('image')->getRealPath(),
                    [
                        'folder' => 'events',          // organise uploads in a folder
                        'resource_type' => 'image',
                    ]
                );

                // Secure HTTPS URL returned by Cloudinary
                $imageUrl = $uploadedFile->getSecurePath();

            } catch (\Exception $e) {
                // Clean up the event if the upload fails so we don't leave orphaned records
                $event->delete();

                return response()->json([
                    'message' => 'Image upload failed: ' . $e->getMessage(),
                ], 500);
            }

            // Save the Cloudinary URL in the Image table
            Image::create([
                'image_path' => $imageUrl,
                'id_event'   => $event->id,
            ]);
        }

        return response()->json([
            'message' => 'Event created successfully',
            'event'   => $event,
        ]);
    }

    /**
     * Update an existing event if the authenticated user is an admin.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEvent(Request $request, $id)
    {
        // Check if the user has an admin role
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the event to update
        $event = Event::findOrFail($id);

        // Validate the request
        $validator = Validator::make($request->all(), [
            'type'        => 'sometimes|string|max:255',
            'date'        => 'sometimes|date',
            'description' => 'sometimes|string|max:255',
            'id_dep'      => 'sometimes|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Update the event fields
        $event->update($request->only(['type', 'date', 'description', 'id_dep']));

        // Optionally replace the image if a new one is provided
        if ($request->hasFile('image')) {
            $validator2 = Validator::make($request->all(), [
                'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator2->fails()) {
                return response()->json($validator2->errors(), 400);
            }

            try {
                $uploadedFile = Cloudinary::upload(
                    $request->file('image')->getRealPath(),
                    [
                        'folder'        => 'events',
                        'resource_type' => 'image',
                    ]
                );

                $imageUrl = $uploadedFile->getSecurePath();

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Image upload failed: ' . $e->getMessage(),
                ], 500);
            }

            // Update or create the related image record
            Image::updateOrCreate(
                ['id_event' => $event->id],
                ['image_path' => $imageUrl]
            );
        }

        return response()->json([
            'message' => 'Event updated successfully',
            'event'   => $event,
        ]);
    }

    /**
     * Delete an event if the authenticated user is an admin.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteEvent($id)
    {
        // Check if the user has an admin role
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find and delete the event (related images cascade if configured in migration)
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }
}
