<?php

declare(strict_types=1);

namespace App\Actions\Subtasks;

use App\Models\Subtask;

final class DeleteSubtask
{
    /**
     * @param  array{subtask: Subtask}  $input
     */
    public function handle(array $input): void
    {
        $input['subtask']->delete();
    }
}
