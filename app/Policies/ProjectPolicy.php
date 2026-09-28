<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\User;

final class ProjectPolicy
{
    /**
     * Open projects reach the whole organization; restricted ones only
     * reach included members, plus owners and admins.
     */
    public function view(User $user, Project $project): bool
    {
        $role = $project->organization->roleFor($user);

        if ($role === null) {
            return false;
        }

        if ($project->visibility === ProjectVisibility::Open) {
            return true;
        }

        if ($role->canManage()) {
            return true;
        }

        return $project->isIncluded($user);
    }

    /**
     * Owners and admins run projects; members and viewers do not.
     */
    public function manage(User $user, Project $project): bool
    {
        return $project->organization->roleFor($user)?->canManage() ?? false;
    }
}
