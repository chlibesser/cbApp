<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Domains\Identity\Services\ProfileService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * ProfileController - Handle profile management
 */
class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    /**
     * Get current user's profiles
     */
    public function index(Request $request)
    {
        $profiles = $this->profileService->getAccountProfiles($request->user());

        return response()->json([
            'profiles' => $profiles,
        ]);
    }

    /**
     * Switch to a different profile
     */
    public function switch(Request $request, $profileId)
    {
        $profile = $request->user()->profiles()->findOrFail($profileId);
        
        $success = $this->profileService->switchProfile($request->user(), $profile);

        if (!$success) {
            return response()->json([
                'message' => 'Unable to switch profile',
            ], 400);
        }

        return response()->json([
            'message' => 'Profile switched successfully',
            'profile' => $profile->load('tenant'),
        ]);
    }

    /**
     * Get current profile
     */
    public function current(Request $request)
    {
        $currentProfile = session('current_profile');

        if (!$currentProfile) {
            return response()->json([
                'message' => 'No profile selected',
            ], 404);
        }

        return response()->json([
            'profile' => $currentProfile->load(['tenant', 'roles']),
        ]);
    }
}