<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectVisibility: string
{
    case Open = 'open';
    case Restricted = 'restricted';

    /**
     * Sentence-case label used in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Restricted => 'Restricted',
        };
    }

    /**
     * The ON-state copy for settings toggles.
     */
    public function description(): string
    {
        return match ($this) {
            self::Open => 'Visible to everyone in the organization',
            self::Restricted => 'Visible only to included members, owners and admins',
        };
    }
}
