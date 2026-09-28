<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    /**
     * Sentence-case label used in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * Owners and admins run the workspace; members and viewers do not.
     */
    public function canManage(): bool
    {
        return $this->isAtLeast(self::Admin);
    }

    /**
     * Role hierarchy: owner > admin > member > viewer.
     */
    public function isAtLeast(self $minimum): bool
    {
        return $this->weight() >= $minimum->weight();
    }

    private function weight(): int
    {
        return match ($this) {
            self::Owner => 4,
            self::Admin => 3,
            self::Member => 2,
            self::Viewer => 1,
        };
    }
}
