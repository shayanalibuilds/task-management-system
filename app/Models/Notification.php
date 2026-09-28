<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An inbox row: mention, assignment, comment or due reminder.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $type
 * @property int|null $task_id
 * @property int|null $actor_id
 * @property string $message
 * @property Carbon|null $read_at
 */
final class Notification extends Model
{
    public const string TYPE_MENTION = 'mention';

    public const string TYPE_ASSIGNMENT = 'assignment';

    public const string TYPE_COMMENT = 'comment';

    public const string TYPE_DUE_REMINDER = 'due_reminder';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'type',
        'task_id',
        'actor_id',
        'message',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
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

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
