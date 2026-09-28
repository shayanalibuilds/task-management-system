<?php

declare(strict_types=1);

namespace App\Actions\Labels;

use App\Models\Label;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

final class AttachLabel
{
    /**
     * @param  array{task: Task, label: Label}  $input
     */
    public function handle(array $input): void
    {
        DB::transaction(function () use ($input): void {
            $attached = $input['task']->labels()
                ->whereKey($input['label']->id)
                ->exists();

            if (! $attached) {
                $input['task']->labels()->attach($input['label']->id);
            }
        });
    }
}
