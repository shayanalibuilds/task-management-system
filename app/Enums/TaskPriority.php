<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskPriority: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * Sentence-case label used in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'No priority',
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    /**
     * Tailwind classes for the priority chip.
     */
    public function classes(): string
    {
        return match ($this) {
            self::None => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
            self::Low => 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-400',
            self::Medium => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
            self::High => 'bg-orange-50 text-orange-700 dark:bg-orange-950 dark:text-orange-400',
            self::Urgent => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400',
        };
    }
}
