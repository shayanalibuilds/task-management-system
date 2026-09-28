<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Models\Task;

final class DeleteTask
{
    /**
     * @param  array{task: Task}  $input
     */
    public function handle(array $input): void
    {
        $input['task']->delete();
    }
}
