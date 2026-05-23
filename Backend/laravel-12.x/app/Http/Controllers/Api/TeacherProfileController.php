<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherProfileController extends Controller
{
    // Get a teacher profile by ID
    public function show($id)
    {
        $user = User::where('id', $id)->where('role', 'teacher')->firstOrFail();
        return response()->json($user);
    }

    // Update a teacher profile (admin or self)
    public function update(Request $request, $id)
    {
        $user = User::where('id', $id)->where('role', 'teacher')->firstOrFail();
        $this->authorize('update', $user); // Policy check (optional)

        $fields = [
            'profile_picture', 'position', 'subjects_teaching', 'it_specialization', 'educational_background',
            'skills_technologies', 'certifications', 'years_experience', 'professional_summary', 'contact_info',
            'office_schedule', 'department', 'programming_languages', 'frameworks_tools', 'database_experience',
            'software_expertise', 'research_interests', 'current_projects', 'achievements', 'portfolio_links',
        ];

        $data = $request->only($fields);

        // Validate required fields
        $validator = Validator::make($data, [
            'position' => 'required|string',
            'subjects_teaching' => 'required',
            'it_specialization' => 'required|string',
            'educational_background' => 'required',
            'skills_technologies' => 'required',
            'years_experience' => 'required|integer|min:0',
            'professional_summary' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($data);
        return response()->json(['message' => 'Profile updated', 'user' => $user]);
    }
}
