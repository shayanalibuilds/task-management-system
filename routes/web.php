<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MarkAllReadController;
use App\Http\Controllers\Members\InviteMemberController;
use App\Http\Controllers\Members\LeaveOrganizationController;
use App\Http\Controllers\MyTasksController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Projects\DeleteColumnController;
use App\Http\Controllers\Projects\DeleteProjectController;
use App\Http\Controllers\Projects\DeleteTaskController;
use App\Http\Controllers\Projects\ListProjectsController;
use App\Http\Controllers\Projects\ProjectSettingsController;
use App\Http\Controllers\Projects\ReorderColumnsController;
use App\Http\Controllers\Projects\ShowProjectController;
use App\Http\Controllers\Projects\StoreColumnController;
use App\Http\Controllers\Projects\StoreCommentController;
use App\Http\Controllers\Projects\StoreProjectController;
use App\Http\Controllers\Projects\StoreSubtaskController;
use App\Http\Controllers\Projects\StoreTaskController;
use App\Http\Controllers\Projects\SubtaskController;
use App\Http\Controllers\Projects\TaskLabelController;
use App\Http\Controllers\Projects\UpdateColumnController;
use App\Http\Controllers\Projects\UpdateProjectController;
use App\Http\Controllers\Projects\UpdateTaskController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'org.member'])->prefix('{organization:slug}')->group(function (): void {
    Route::get('/', DashboardController::class)->name('org.dashboard');

    Route::get('inbox', InboxController::class)->name('inbox.index');
    Route::post('inbox/mark-all-read', MarkAllReadController::class)->name('inbox.mark_all');

    Route::get('settings/notifications', [NotificationSettingsController::class, 'edit'])
        ->name('settings.notifications');
    Route::patch('settings/notifications', [NotificationSettingsController::class, 'update'])
        ->name('settings.notifications.update');

    Route::get('inbox', InboxController::class)->name('inbox.index');
    Route::post('inbox/mark-all-read', MarkAllReadController::class)->name('inbox.mark_all');

    Route::get('settings/notifications', [NotificationSettingsController::class, 'edit'])
        ->name('settings.notifications');
    Route::patch('settings/notifications', [NotificationSettingsController::class, 'update'])
        ->name('settings.notifications.update');

    Route::get('my-tasks', MyTasksController::class)->name('my-tasks.show');
    Route::get('calendar', CalendarController::class)->name('calendar.show');

    Route::post('members/invites', InviteMemberController::class)
        ->name('members.invites.store');

    Route::post('members/leave', LeaveOrganizationController::class)
        ->name('members.leave');

    Route::get('projects', ListProjectsController::class)->name('projects.index');
    Route::post('projects', StoreProjectController::class)->name('projects.store');
    Route::get('projects/{project}', ShowProjectController::class)
        ->name('projects.show')
        ->scopeBindings();
    Route::get('projects/{project}/settings', ProjectSettingsController::class)
        ->name('projects.settings')
        ->scopeBindings();
    Route::patch('projects/{project}', UpdateProjectController::class)
        ->name('projects.update')
        ->scopeBindings();
    Route::delete('projects/{project}', DeleteProjectController::class)
        ->name('projects.destroy')
        ->scopeBindings();
    Route::post('projects/{project}/columns', StoreColumnController::class)
        ->name('projects.columns.store')
        ->scopeBindings();
    Route::patch('projects/{project}/columns/reorder', ReorderColumnsController::class)
        ->name('projects.columns.reorder')
        ->scopeBindings();
    Route::patch('projects/{project}/columns/{column}', UpdateColumnController::class)
        ->name('projects.columns.update')
        ->scopeBindings();
    Route::delete('projects/{project}/columns/{column}', DeleteColumnController::class)
        ->name('projects.columns.destroy')
        ->scopeBindings();

    Route::post('projects/{project}/tasks', StoreTaskController::class)
        ->name('projects.tasks.store')
        ->scopeBindings();
    Route::patch('projects/{project}/tasks/{task}', UpdateTaskController::class)
        ->name('projects.tasks.update')
        ->scopeBindings();
    Route::delete('projects/{project}/tasks/{task}', DeleteTaskController::class)
        ->name('projects.tasks.destroy')
        ->scopeBindings();

    Route::post('projects/{project}/tasks/{task}/comments', StoreCommentController::class)
        ->name('projects.tasks.comments.store')
        ->scopeBindings();

    Route::post('projects/{project}/tasks/{task}/subtasks', StoreSubtaskController::class)
        ->name('projects.tasks.subtasks.store')
        ->scopeBindings();
    Route::patch('projects/{project}/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'update'])
        ->name('projects.tasks.subtasks.update')
        ->scopeBindings();
    Route::delete('projects/{project}/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'destroy'])
        ->name('projects.tasks.subtasks.destroy')
        ->scopeBindings();

    Route::post('projects/{project}/tasks/{task}/labels', [TaskLabelController::class, 'store'])
        ->name('projects.tasks.labels.attach')
        ->scopeBindings();
    Route::post('projects/{project}/tasks/{task}/labels/new', [TaskLabelController::class, 'create'])
        ->name('projects.tasks.labels.create')
        ->scopeBindings();
    Route::delete('projects/{project}/tasks/{task}/labels/{label}', [TaskLabelController::class, 'destroy'])
        ->name('projects.tasks.labels.detach')
        ->scopeBindings();
});
