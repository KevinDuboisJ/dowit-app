<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\UserRequest;
use App\Services\TeamService;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function store(UserRequest $userRequest, TeamService $teamService)
    {
        // The request is already validated at this point
        $validatedData = $userRequest->validated();

        $user = DB::transaction(function () use ($validatedData, $teamService) {
            // Create or update the user
            $user = User::updateOrCreate(
                ['edb_id' => $validatedData['edb_id']],
                $validatedData
            );

            // Assign user to the correct teams
            $teamService->autoAssignTeamsToUsers(collect([$user]));

            return $user;
        });

        if ($user->wasRecentlyCreated) {
            // Return successfull response
            return response()->json([
                'status' => 'updateOrCreate success',
                'message' => 'User created successfully',
                'data' => $user,
            ], 200);
        } else {
            // Return successfull response
            return response()->json([
                'status' => 'updateOrCreate success',
                'message' => 'User updated successfully',
                'data' => $user,
            ], 200);
        }
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
