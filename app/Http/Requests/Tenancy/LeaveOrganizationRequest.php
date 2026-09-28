<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenancy;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

final class LeaveOrganizationRequest extends FormRequest
{
    /**
     * Members may leave, but the last owner cannot.
     */
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization
            && $this->user()?->can('leave', $organization) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
