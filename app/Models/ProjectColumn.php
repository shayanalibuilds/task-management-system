<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ColumnCategory;
use Database\Factories\ProjectColumnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property ColumnCategory $category
 * @property float $position
 */
final class ProjectColumn extends Model
{
    /** @use HasFactory<ProjectColumnFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['project_id', 'name', 'category', 'position'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ColumnCategory::class,
            'position' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'column_id');
    }
}
