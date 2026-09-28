<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationRole;
use Database\Factories\OrganizationInviteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A pending email invitation to join an organization.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $email
 * @property OrganizationRole $role
 * @property string $token
 * @property int|null $invited_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class OrganizationInvite extends Model
{
    /** @use HasFactory<OrganizationInviteFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['organization_id', 'email', 'role', 'token', 'invited_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
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
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
