<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Task;
use App\Models\User;

final class TaskPolicy
{
    /**
     * Members and above shape the board; viewers read only.
     */
    public function manage(User $user, Task $task): bool
    {
        return $task->project->organization->roleFor($user)?->isAtLeast(OrganizationRole::Member) ?? false;
    }
}
