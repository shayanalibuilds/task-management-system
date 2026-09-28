<?php

declare(strict_types=1);

namespace App\Actions\Labels;

use App\Models\Label;
use App\Models\Organization;

final class CreateLabel
{
    /**
     * @param  array{organization: Organization, name: string, color: string}  $input
     */
    public function handle(array $input): Label
    {
        return Label::query()->create([
            'organization_id' => $input['organization']->id,
            'name' => $input['name'],
            'color' => $input['color'],
        ]);
    }
}
