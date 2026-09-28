<?php

declare(strict_types=1);

namespace App\Actions\Labels;

use App\Models\Label;
use App\Models\Task;

final class DetachLabel
{
    /**
     * @param  array{task: Task, label: Label}  $input
     */
    public function handle(array $input): void
    {
        $input['task']->labels()->detach($input['label']->id);
    }
}
