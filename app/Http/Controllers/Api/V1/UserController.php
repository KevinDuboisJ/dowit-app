<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Api\V1\UserRequest;
use App\Services\TeamService;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function store(UserRequest $userRequest, TeamService $teamService)
    {
        // The request is already validated at this point
        $validatedData = $userRequest->validated();

        // Check if a user with the same username but a different edb_id exists
        $existingUser = User::where('username', $validatedData['username'])->first();

        if ($existingUser && $existingUser->edb_id !== $validatedData['edb_id']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username already exists for another edb_id.',
            ], 409); // 409 Conflict response
        }

        // Begin transaction
        $user = DB::transaction(function () use ($validatedData, $teamService) {
            // Create or update the user
            $user = User::updateOrCreate(
                ['edb_id' => $validatedData['edb_id']],
                array_merge($validatedData, ['is_active' => 1]) // Ensure is_active = 1
            );

            // Assign user to the correct teams
            $teamService->autoAssignTeamsToUsers(collect([$user]));

            return $user;
        });

        // Determine response message
        $message = $user->wasRecentlyCreated ? 'User created successfully' : 'User updated successfully';

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $user,
        ], 200);
    }

    public function updateByEdbId(UserRequest $userRequest, TeamService $teamService, User $user)
    {
        // The request is already validated at this point
        $validatedData = $userRequest->validated();

        // Update the user with validated data
        $user->update($validatedData);

        // Assign user to the correct teams
        $teamService->autoAssignTeamsToUsers(collect([$user]));

        // Return successfull response
        return response()->json([
            'status' => 'update success',
            'message' => 'User updated successfully',
            'data' => $user,
        ], 200);
    }
}
