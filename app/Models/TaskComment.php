<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $body
 * @property array<int, int>|null $mentions
 * @property Carbon $created_at
 * @property User $author
 */
final class TaskComment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['task_id', 'user_id', 'body', 'mentions'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mentions' => 'array',
        ];
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
