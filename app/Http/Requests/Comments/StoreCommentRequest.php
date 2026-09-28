<?php

declare(strict_types=1);

namespace App\Http\Requests\Comments;

use App\Enums\OrganizationRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCommentRequest extends FormRequest
{
    /**
     * Members and above discuss; viewers read only.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        $user = $this->user();

        return $project instanceof Project && $user !== null
            && $project->organization->roleFor($user)?->isAtLeast(OrganizationRole::Member) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array{body: string}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{body: string} $validated */
        $validated = parent::validated($key, $default);

        return ['body' => $validated['body']];
    }
}
