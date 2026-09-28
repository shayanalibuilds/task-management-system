<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Projects\CreateProject;
use App\Enums\ColumnCategory;
use App\Enums\OrganizationRole;
use App\Enums\ProjectVisibility;
use App\Enums\TaskPriority;
use App\Models\Label;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the Northwind Studio demo workspace.
 *
 * Every account below is documented in the README so new operators can
 * log in and explore a workspace that is already alive with work.
 */
final class NorthwindSeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->user('Dana Fields', 'owner@example.com', 'password-owner-12');
        $admin = $this->user('Ravi Chandra', 'admin@example.com', 'password-admin-12');
        $member = $this->user('Mila Novak', 'member@example.com', 'password-member-12');
        $viewer = $this->user('Tom Olsen', 'viewer@example.com', 'password-viewer-12');

        $org = Organization::query()->create([
            'name' => 'Northwind Studio',
            'slug' => 'northwind',
        ]);

        foreach ([
            [$owner, OrganizationRole::Owner],
            [$admin, OrganizationRole::Admin],
            [$member, OrganizationRole::Member],
            [$viewer, OrganizationRole::Viewer],
        ] as [$user, $role]) {
            Membership::query()->create([
                'organization_id' => $org->id,
                'user_id' => $user->id,
                'role' => $role->value,
            ]);

            $user->forceFill(['current_organization_id' => $org->id])->save();
        }

        $action = new CreateProject;

        $website = $action->handle([
            'organization' => $org,
            'name' => 'Website Relaunch',
            'color' => '#4F46E5',
            'icon' => 'rocket',
            'visibility' => ProjectVisibility::Open,
            'creator' => $owner,
        ]);

        $app = $action->handle([
            'organization' => $org,
            'name' => 'Mobile App',
            'color' => '#059669',
            'icon' => 'smartphone',
            'visibility' => ProjectVisibility::Open,
            'creator' => $admin,
        ]);

        $ops = $action->handle([
            'organization' => $org,
            'name' => 'Ops Playbook',
            'color' => '#D97706',
            'icon' => 'book',
            'visibility' => ProjectVisibility::Restricted,
            'creator' => $owner,
        ]);

        foreach ([
            [$website, 'Blocked', ColumnCategory::InFlight, 2500.0],
            [$app, 'In review', ColumnCategory::Done, 2500.0],
        ] as [$project, $name, $category, $position]) {
            ProjectColumn::query()->create([
                'project_id' => $project->id,
                'name' => $name,
                'category' => $category,
                'position' => $position,
            ]);
        }

        $labels = collect([
            ['name' => 'Bug', 'color' => '#DC2626'],
            ['name' => 'Design', 'color' => '#7C3AED'],
            ['name' => 'Research', 'color' => '#0891B2'],
            ['name' => 'Growth', 'color' => '#16A34A'],
        ])->map(fn (array $label): Label => Label::query()->create([...$label, 'organization_id' => $org->id]));

        $today = now()->startOfDay();

        $task = fn (Project $project, string $columnName, array $attributes): Task => Task::query()->create([
            'project_id' => $project->id,
            'column_id' => $project->columns->pluck('id', 'name')->get($columnName, 0),
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'priority' => $attributes['priority'] ?? TaskPriority::None,
            'assignee_id' => $attributes['assignee'] ?? null,
            'start_on' => $attributes['start_on'] ?? null,
            'due_on' => $attributes['due_on'] ?? null,
            'position' => $attributes['position'],
            'created_by' => $attributes['creator'],
        ]);

        // Website Relaunch — a busy, realistic board.
        $hero = $task($website, 'To do', [
            'title' => 'Design the new marketing hero section',
            'description' => 'Explore three directions for the hero: product shot, illustrated, and typography-led. Ship the strongest one behind the relaunch flag.',
            'priority' => TaskPriority::High,
            'assignee' => $member->id,
            'due_on' => $today->copy()->addDays(6)->toDateString(),
            'position' => 1000.0,
            'creator' => $admin->id,
        ]);

        $task($website, 'To do', [
            'title' => 'Audit current site for broken links',
            'priority' => TaskPriority::Low,
            'assignee' => $viewer->id,
            'start_on' => $today->copy()->addDays(2)->toDateString(),
            'due_on' => $today->copy()->addDays(9)->toDateString(),
            'position' => 2000.0,
            'creator' => $owner->id,
        ]);

        $pricingPage = $task($website, 'To do', [
            'title' => 'Draft copy for the open-source landing page',
            'description' => 'Copy must explain self-hosting in the first screen: free, open source, your infrastructure.',
            'priority' => TaskPriority::Medium,
            'assignee' => $owner->id,
            'position' => 3000.0,
            'creator' => $member->id,
        ]);

        $task($website, 'In progress', [
            'title' => 'Migrate blog content into the new CMS',
            'priority' => TaskPriority::Medium,
            'assignee' => $admin->id,
            'start_on' => $today->copy()->subDays(4)->toDateString(),
            'due_on' => $today->copy()->addDay()->toDateString(),
            'position' => 1000.0,
            'creator' => $owner->id,
        ]);

        $lighthouse = $task($website, 'In progress', [
            'title' => 'Fix Lighthouse regressions on mobile',
            'description' => 'CLS and LCP both slipped after the last deploy. Profile the fonts and the hero image pipeline.',
            'priority' => TaskPriority::Urgent,
            'assignee' => $member->id,
            'due_on' => $today->copy()->subDays(2)->toDateString(),
            'position' => 2000.0,
            'creator' => $admin->id,
        ]);

        $task($website, 'Blocked', [
            'title' => 'Wait on legal review for the new footer',
            'priority' => TaskPriority::Medium,
            'assignee' => $owner->id,
            'due_on' => $today->copy()->addDays(4)->toDateString(),
            'position' => 1000.0,
            'creator' => $admin->id,
        ]);

        $task($website, 'Done', [
            'title' => 'Point staging DNS at the new cluster',
            'priority' => TaskPriority::None,
            'assignee' => $admin->id,
            'due_on' => $today->copy()->subDays(6)->toDateString(),
            'position' => 1000.0,
            'creator' => $owner->id,
        ]);

        $task($website, 'Done', [
            'title' => 'Publish the refreshed brand style guide',
            'priority' => TaskPriority::Low,
            'assignee' => $member->id,
            'due_on' => $today->copy()->subDays(9)->toDateString(),
            'position' => 2000.0,
            'creator' => $owner->id,
        ]);

        // Mobile App.
        $onboarding = $task($app, 'To do', [
            'title' => 'Prototype the three-step onboarding flow',
            'priority' => TaskPriority::High,
            'assignee' => $member->id,
            'due_on' => $today->copy()->addDays(3)->toDateString(),
            'position' => 1000.0,
            'creator' => $owner->id,
        ]);

        $task($app, 'To do', [
            'title' => 'Scope push notification permissions copy',
            'priority' => TaskPriority::None,
            'position' => 2000.0,
            'creator' => $admin->id,
        ]);

        $offline = $task($app, 'In progress', [
            'title' => 'Cache task lists for offline editing',
            'description' => 'Local-first sync: optimistic writes queue in SQLite and flush when connectivity returns.',
            'priority' => TaskPriority::High,
            'assignee' => $admin->id,
            'start_on' => $today->copy()->subDays(2)->toDateString(),
            'due_on' => $today->toDateString(),
            'position' => 1000.0,
            'creator' => $member->id,
        ]);

        $beta = $task($app, 'In review', [
            'title' => 'Ship beta build to the internal testers',
            'priority' => TaskPriority::Urgent,
            'assignee' => $owner->id,
            'due_on' => $today->copy()->subDay()->toDateString(),
            'position' => 1000.0,
            'creator' => $admin->id,
        ]);

        $task($app, 'Done', [
            'title' => 'Export adaptive launcher icons',
            'priority' => TaskPriority::Low,
            'assignee' => $member->id,
            'position' => 1000.0,
            'creator' => $owner->id,
        ]);

        // Ops Playbook — restricted, so the viewer never sees it.
        $runbook = $task($ops, 'To do', [
            'title' => 'Document the incident escalation ladder',
            'priority' => TaskPriority::Medium,
            'assignee' => $admin->id,
            'due_on' => $today->copy()->addDays(7)->toDateString(),
            'position' => 1000.0,
            'creator' => $owner->id,
        ]);

        $backup = $task($ops, 'In progress', [
            'title' => 'Verify nightly database backups restore cleanly',
            'priority' => TaskPriority::High,
            'assignee' => $owner->id,
            'due_on' => $today->toDateString(),
            'position' => 1000.0,
            'creator' => $admin->id,
        ]);

        $task($ops, 'Done', [
            'title' => 'Rotate the deployment credentials',
            'priority' => TaskPriority::Low,
            'assignee' => $admin->id,
            'position' => 1000.0,
            'creator' => $owner->id,
        ]);

        foreach ([
            [$hero, [['Sketch three directions', true], ['Pick the strongest direction', true], ['Build responsive layout', false], ['Hand off to engineering', false]]],
            [$lighthouse, [['Profile fonts', true], ['Compress hero imagery', false], ['Re-run Lighthouse above 95', false]]],
            [$offline, [['Write the sync spec', true], ['Implement the write queue', false]]],
        ] as [$parent, $items]) {
            foreach ($items as $index => [$title, $completed]) {
                Subtask::query()->create([
                    'task_id' => $parent->id,
                    'title' => $title,
                    'completed' => $completed,
                    'position' => (1000.0 + $index * 1000.0),
                ]);
            }
        }

        $labelIds = $labels->pluck('id', 'name');

        $hero->labels()->attach([$labelIds->get('Bug'), $labelIds->get('Design')]);
        $lighthouse->labels()->attach([$labelIds->get('Bug')]);
        $onboarding->labels()->attach([$labelIds->get('Design'), $labelIds->get('Research')]);
        $pricingPage->labels()->attach([$labelIds->get('Growth')]);
        $runbook->labels()->attach([$labelIds->get('Research')]);

        TaskComment::query()->create([
            'task_id' => $hero->id,
            'user_id' => $admin->id,
            'body' => "@[Mila Novak](user:{$member->id}) the second direction matches what we tested with customers — let's commit to it.",
            'mentions' => [$member->id],
        ]);

        TaskComment::query()->create([
            'task_id' => $hero->id,
            'user_id' => $member->id,
            'body' => 'Agreed. I will have the responsive layout ready for review tomorrow.',
            'mentions' => [],
        ]);

        TaskComment::query()->create([
            'task_id' => $lighthouse->id,
            'user_id' => $owner->id,
            'body' => "@[Ravi Chandra](user:{$admin->id}) can you check whether the analytics bundle is the CLS offender?",
            'mentions' => [$admin->id],
        ]);

        foreach ([
            [$member, Notification::TYPE_MENTION, $hero, $owner, 'Dana Fields mentioned you on "Design the new marketing hero section"', false],
            [$admin, Notification::TYPE_MENTION, $lighthouse, $owner, 'Dana Fields mentioned you on "Fix Lighthouse regressions on mobile"', false],
            [$member, Notification::TYPE_ASSIGNMENT, $hero, $admin, 'Ravi Chandra assigned you "Design the new marketing hero section"', true],
            [$admin, Notification::TYPE_ASSIGNMENT, $offline, $member, 'Mila Novak assigned you "Cache task lists for offline editing"', false],
            [$owner, Notification::TYPE_DUE_REMINDER, $beta, null, '"Ship beta build to the internal testers" is due tomorrow', false],
            [$admin, Notification::TYPE_DUE_REMINDER, $backup, null, '"Verify nightly database backups restore cleanly" is due today', false],
            [$member, Notification::TYPE_COMMENT, $hero, $admin, 'Ravi Chandra commented on "Design the new marketing hero section"', true],
        ] as [$recipient, $type, $taskModel, $actor, $message, $read]) {
            Notification::query()->create([
                'organization_id' => $org->id,
                'user_id' => $recipient->id,
                'type' => $type,
                'task_id' => $taskModel->id,
                'actor_id' => $actor?->id,
                'message' => $message,
                'read_at' => $read ? now()->subHour() : null,
            ]);
        }

        OrganizationInvite::query()->create([
            'organization_id' => $org->id,
            'email' => 'pending@example.com',
            'role' => OrganizationRole::Member->value,
            'token' => 'northwind-pending-invite-token',
            'invited_by' => $owner->id,
        ]);
    }

    private function user(string $name, string $email, string $password): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
    }
}
