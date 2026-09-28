<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects emails that already belong to a member of the organization.
 */
final readonly class NotAnOrganizationMember implements ValidationRule
{
    public function __construct(private int $organizationId) {}

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $userId = User::query()
            ->where('email', (string) $value)
            ->value('id');

        if ($userId === null) {
            return;
        }

        $member = DB::table('memberships')
            ->where('organization_id', $this->organizationId)
            ->where('user_id', $userId)
            ->exists();

        if ($member) {
            $fail('That person is already a member of this organization.');
        }
    }
}
