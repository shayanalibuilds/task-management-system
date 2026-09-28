<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_id
 * @property string $title
 * @property bool $completed
 * @property float $position
 */
final class Subtask extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['task_id', 'title', 'completed', 'position'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'position' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
