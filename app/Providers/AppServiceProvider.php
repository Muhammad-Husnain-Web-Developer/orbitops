<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentWorkspace::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'workspace' => Workspace::class,
            'client' => Client::class,
            'project' => Project::class,
            'milestone' => Milestone::class,
            'task' => Task::class,
            'comment' => Comment::class,
            'attachment' => Attachment::class,
            'time_entry' => TimeEntry::class,
            'invoice' => Invoice::class,
            'expense' => Expense::class,
            'activity' => Activity::class,
        ]);

        Model::automaticallyEagerLoadRelationships();

        JsonResource::withoutWrapping();

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        Vite::prefetch(concurrency: 3);

        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(90)->by($request->user()?->id ?: $request->ip()));
    }
}
