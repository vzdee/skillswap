<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $hasSkillsSchema = Schema::hasTable('skills')
            && Schema::hasTable('user_skill')
            && Schema::hasColumn('users', 'skills_onboarding_completed_at');
        $hasAvailabilitySchema = Schema::hasTable('user_availabilities')
            && Schema::hasColumn('users', 'availability_onboarding_completed_at');

        return view('dashboard', [
            'skillsCatalog' => $hasSkillsSchema
                ? Skill::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'availabilityDays' => UserSetupController::availabilityDays(),
            'availabilityBlocks' => UserSetupController::availabilityBlocks(),
            'requiresSkillsOnboarding' => $hasSkillsSchema && $user->skills_onboarding_completed_at === null,
            'requiresAvailabilityOnboarding' => $hasAvailabilitySchema && $user->availability_onboarding_completed_at === null,
        ]);
    }
}
