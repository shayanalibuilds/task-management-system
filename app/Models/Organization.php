<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationRole;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property array<int, array<string, mixed>>|null $labels
 * @property string|null $webhook_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'slug', 'labels', 'webhook_url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
        ];
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<OrganizationInvite, $this>
     */
    public function invites(): HasMany
    {
        return $this->hasMany(OrganizationInvite::class);
    }

    /**
     * @return HasMany<Label, $this>
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /**
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships', 'organization_id', 'user_id')
            ->using(Membership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Members allowed to run the organization.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', OrganizationRole::Owner->value);
    }

    /**
     * The member's role, or null when they do not belong here.
     */
    public function roleFor(User $user): ?OrganizationRole
    {
        $membership = $this->memberships()
            ->where('user_id', $user->getKey())
            ->first();

        return $membership?->role;
    }

    public function hasMember(int $userId): bool
    {
        return $this->memberships()
            ->where('user_id', $userId)
            ->exists();
    }
}
