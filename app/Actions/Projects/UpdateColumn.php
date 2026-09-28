<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\ColumnCategory;
use App\Models\ProjectColumn;

final class UpdateColumn
{
    /**
     * @param  array{column: ProjectColumn, name: string, category: ColumnCategory}  $input
     */
    public function handle(array $input): ProjectColumn
    {
        $input['column']->update([
            'name' => $input['name'],
            'category' => $input['category'],
        ]);

        return $input['column'];
    }
}
