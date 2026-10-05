<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientNoteController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceStatusController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Portal\PortalApprovalController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalFileController;
use App\Http\Controllers\Portal\PortalInvoiceController;
use App\Http\Controllers\Portal\PortalMessageController;
use App\Http\Controllers\Portal\PortalProjectController;
use App\Http\Controllers\Portal\PortalTaskController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMessageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Settings\ApiTokenController;
use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\DangerZoneController;
use App\Http\Controllers\Settings\IntegrationController;
use App\Http\Controllers\Settings\MemberSettingsController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\WorkspaceSettingsController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\TimerController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketing site
|--------------------------------------------------------------------------
*/

Route::controller(MarketingController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/features', 'features')->name('features');
    Route::get('/pricing', 'pricing')->name('pricing');
    Route::get('/about', 'about')->name('about');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/changelog', 'changelog')->name('changelog');
    Route::get('/docs', 'docs')->name('docs');
    Route::get('/privacy', 'privacy')->name('privacy');
    Route::get('/terms', 'terms')->name('terms');
});

Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
Route::post('/demo/{persona?}', DemoLoginController::class)->middleware(['guest', 'throttle:20,1'])->whereIn('persona', ['team', 'client'])->name('demo.login');

Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');

/*
|--------------------------------------------------------------------------
| Authenticated area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'workspace'])->group(function () {
    Route::get('/onboarding/workspace', [OnboardingController::class, 'create'])->name('onboarding.workspace');
    Route::post('/onboarding/workspace', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');

    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::put('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    /*
    | Internal workspace (team members only)
    */
    Route::middleware('team')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/search', SearchController::class)->middleware('throttle:search')->name('search');
        Route::get('/lookups', LookupController::class)->name('lookups');

        Route::resource('clients', ClientController::class)->except(['create', 'edit']);
        Route::post('/clients/{client}/notes', [ClientNoteController::class, 'store'])->name('clients.notes.store');

        Route::resource('projects', ProjectController::class)->except(['create', 'edit']);
        Route::post('/projects/{project}/milestones', [MilestoneController::class, 'store'])->name('milestones.store');
        Route::put('/milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
        Route::delete('/milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');
        Route::post('/projects/{project}/messages', [ProjectMessageController::class, 'store'])->name('projects.messages.store');

        Route::resource('tasks', TaskController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
        Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

        Route::get('/calendar', CalendarController::class)->name('calendar');

        Route::get('/time', [TimeEntryController::class, 'index'])->name('time.index');
        Route::post('/time', [TimeEntryController::class, 'store'])->name('time.store');
        Route::put('/time/{timeEntry}', [TimeEntryController::class, 'update'])->name('time.update');
        Route::delete('/time/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('time.destroy');
        Route::post('/timer/start', [TimerController::class, 'start'])->name('timer.start');
        Route::post('/timer/stop', [TimerController::class, 'stop'])->name('timer.stop');

        Route::resource('invoices', InvoiceController::class);
        Route::controller(InvoiceStatusController::class)->group(function () {
            Route::post('/invoices/{invoice}/send', 'send')->name('invoices.send');
            Route::post('/invoices/{invoice}/payments', 'recordPayment')->name('invoices.payments.store');
            Route::post('/invoices/{invoice}/cancel', 'cancel')->name('invoices.cancel');
            Route::post('/invoices/{invoice}/duplicate', 'duplicate')->name('invoices.duplicate');
        });

        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::patch('/expenses/{expense}/status', [ExpenseController::class, 'status'])->name('expenses.status');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('/expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');

        Route::get('/files', [FileController::class, 'index'])->name('files.index');
        Route::post('/files', [FileController::class, 'store'])->name('files.store');
        Route::patch('/files/{attachment}', [FileController::class, 'update'])->name('files.update');
        Route::delete('/files/{attachment}', [FileController::class, 'destroy'])->name('files.destroy');
        Route::get('/files/{attachment}/download', [FileController::class, 'download'])->name('files.download');

        Route::get('/reports', ReportController::class)->name('reports');
        Route::get('/activity', ActivityController::class)->name('activity');

        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team/invitations', [TeamInvitationController::class, 'store'])->name('team.invitations.store');
        Route::post('/team/invitations/{invitation}/resend', [TeamInvitationController::class, 'resend'])->name('team.invitations.resend');
        Route::delete('/team/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('team.invitations.destroy');
        Route::patch('/team/members/{user}', [TeamMemberController::class, 'update'])->name('team.members.update');
        Route::delete('/team/members/{user}', [TeamMemberController::class, 'destroy'])->name('team.members.destroy');

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::redirect('/', '/settings/general')->name('index');
            Route::get('/general', [ProfileController::class, 'edit'])->name('general');
            Route::post('/general', [ProfileController::class, 'update'])->name('general.update');
            Route::get('/workspace', [WorkspaceSettingsController::class, 'edit'])->name('workspace');
            Route::post('/workspace', [WorkspaceSettingsController::class, 'update'])->name('workspace.update');
            Route::get('/members', [MemberSettingsController::class, 'index'])->name('members');
            Route::get('/roles', [RoleController::class, 'index'])->name('roles');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::get('/billing', [BillingController::class, 'index'])->name('billing');
            Route::put('/billing', [BillingController::class, 'update'])->name('billing.update');
            Route::get('/notifications', [NotificationSettingsController::class, 'edit'])->name('notifications');
            Route::put('/notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');
            Route::get('/security', [SecurityController::class, 'edit'])->name('security');
            Route::delete('/security/sessions', [SecurityController::class, 'destroyOtherSessions'])->name('security.sessions.destroy');
            Route::get('/appearance', [AppearanceController::class, 'edit'])->name('appearance');
            Route::put('/appearance', [AppearanceController::class, 'update'])->name('appearance.update');
            Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations');
            Route::get('/api', [ApiTokenController::class, 'index'])->name('api');
            Route::post('/api/tokens', [ApiTokenController::class, 'store'])->name('api.tokens.store');
            Route::delete('/api/tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api.tokens.destroy');
            Route::get('/danger', [DangerZoneController::class, 'index'])->name('danger');
            Route::post('/danger/leave', [DangerZoneController::class, 'leave'])->name('danger.leave');
            Route::post('/danger/transfer', [DangerZoneController::class, 'transfer'])->name('danger.transfer');
            Route::delete('/danger/workspace', [DangerZoneController::class, 'destroy'])->name('danger.destroy');
        });
    });

    /*
    | Client portal (client users only)
    */
    Route::middleware('portal')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/', PortalDashboardController::class)->name('dashboard');
        Route::get('/projects', [PortalProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
        Route::get('/tasks', PortalTaskController::class)->name('tasks');
        Route::get('/files', [PortalFileController::class, 'index'])->name('files');
        Route::get('/files/{attachment}/download', [PortalFileController::class, 'download'])->name('files.download');
        Route::get('/invoices', [PortalInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/messages', [PortalMessageController::class, 'index'])->name('messages');
        Route::post('/projects/{project}/messages', [PortalMessageController::class, 'store'])->name('messages.store');
        Route::get('/approvals', [PortalApprovalController::class, 'index'])->name('approvals');
        Route::post('/milestones/{milestone}/review', [PortalApprovalController::class, 'review'])->name('approvals.review');
    });
});
