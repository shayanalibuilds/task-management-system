<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot between organizations and users carrying the member's role.
 *
 * @property int $organization_id
 * @property int $user_id
 * @property OrganizationRole $role
 */
final class Membership extends Pivot
{
    /**
     * @var string
     */
    protected $table = 'memberships';

    /**
     * @var list<string>
     */
    protected $fillable = ['organization_id', 'user_id', 'role', 'email_prefs'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'email_prefs' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
