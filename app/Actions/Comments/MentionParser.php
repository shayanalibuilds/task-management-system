<?php

declare(strict_types=1);

namespace App\Actions\Comments;

use App\Models\Organization;
use App\Models\User;

/**
 * Server-side mention parsing. The editor inserts @[Name](user:ID) tokens
 * so a mention can never be faked by re-typing someone's name.
 */
final readonly class MentionParser
{
    private const string PATTERN = '/@\[([^\]]+)\]\(user:(\d+)\)/';

    /**
     * @return list<int>
     */
    public static function extract(string $body, Organization $organization): array
    {
        preg_match_all(self::PATTERN, $body, $matches);

        /** @var list<int> $ids */
        $ids = array_map(intval(...), array_unique($matches[2]));

        if ($ids === []) {
            return [];
        }

        $memberIds = $organization->users()
            ->whereIn('users.id', $ids)
            ->pluck('users.id')
            ->all();

        return array_values(array_map(intval(...), $memberIds));
    }

    /**
     * Whether the member exists, for editor-side validation.
     */
    public static function isMember(Organization $organization, int $userId): bool
    {
        return User::query()
            ->whereKey($userId)
            ->whereHas('organizations', fn ($query) => $query->whereKey($organization->id))
            ->exists();
    }
}
