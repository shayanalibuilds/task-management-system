<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('title');
            $table->string('priority', 20)->default('none')->after('description');
            $table->foreignId('assignee_id')
                ->nullable()
                ->after('priority')
                ->constrained('users')
                ->nullOnDelete();
            $table->date('start_on')->nullable()->after('assignee_id');
            $table->date('due_on')->nullable()->after('start_on');
            $table->double('position')->default(1000.0)->after('due_on');

            $table->index(['column_id', 'position']);
            $table->index(['assignee_id', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['column_id', 'position']);
            $table->dropIndex(['assignee_id', 'due_on']);
            $table->dropConstrainedForeignId('assignee_id');
            $table->dropColumn(['description', 'priority', 'start_on', 'due_on', 'position']);
        });
    }
};
