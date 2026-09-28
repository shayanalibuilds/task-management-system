<?php

declare(strict_types=1);

namespace App\Enums;

enum ColumnCategory: string
{
    case NotStarted = 'not_started';
    case InFlight = 'in_flight';
    case Done = 'done';

    /**
     * Sentence-case label used in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InFlight => 'In flight',
            self::Done => 'Done',
        };
    }
}
