<?php

declare(strict_types=1);

namespace App\Actions\Subtasks;

use App\Models\Subtask;

final class ToggleSubtask
{
    /**
     * @param  array{subtask: Subtask}  $input
     */
    public function handle(array $input): Subtask
    {
        $subtask = $input['subtask'];
        $subtask->update(['completed' => ! $subtask->completed]);

        return $subtask;
    }
}
