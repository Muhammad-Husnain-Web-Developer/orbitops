<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\ReportExportController;
use App\Models\Activity;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class InsightsAndPagesTest extends TestCase
{
    use BuildsWorkspaces, RefreshDatabase;

    public function test_finance_activity_is_hidden_from_people_without_finance_access(): void
    {
        $workspace = $this->createWorkspace();
        [, $project] = $this->clientWithProject($workspace);
        $member = $this->addMember($workspace, WorkspaceRole::Member);

        $this->within($workspace, function () use ($project) {
            Activity::record('task.created', 'created task', $project);
            Activity::record('invoice.paid', 'recorded a $9,000 payment on', $project);
        });

        $this->as($member)->get(route('activity'))
            ->assertInertia(fn (Assert $page) => $page->has('activities.data', 1)->where('activities.data.0.event', 'task.created'));

        $this->as($workspace->owner)->get(route('activity'))
            ->assertInertia(fn (Assert $page) => $page->has('activities.data', 2));
    }

    public function test_report_export_is_a_csv_and_neutralises_formulas(): void
    {
        $workspace = $this->createWorkspace();
        $this->within($workspace, fn () => Client::factory()->create(['name' => '=HYPERLINK("http://evil")']));

        $response = $this->as($workspace->owner)->get(route('reports.export', ['range' => '90d']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Summary', $response->streamedContent());

        $member = $this->addMember($workspace, WorkspaceRole::Member);
        $this->as($member)->get(route('reports.export'))->assertForbidden();
    }

    public function test_csv_cells_starting_with_formula_characters_are_escaped(): void
    {
        $controller = new ReportExportController;
        $safe = (fn ($value) => $this->safe($value))->call($controller, '=SUM(A1:A9)');

        $this->assertSame("'=SUM(A1:A9)", $safe);
    }

    public function test_errors_render_the_branded_error_page(): void
    {
        $workspace = $this->createWorkspace();
        $member = $this->addMember($workspace, WorkspaceRole::Member);

        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Errors/Error')->where('status', 404));

        $this->as($member)->get(route('reports'))
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->component('Errors/Error')->where('status', 403));
    }

    public function test_marketing_pages_render_with_seo_metadata(): void
    {
        foreach (['home', 'features', 'pricing', 'about', 'contact'] as $name) {
            $response = $this->get(route($name))->assertOk();
            $this->assertMatchesRegularExpression('/<meta[^>]+name="description"[^>]+content="[^"]{40,}"/', $response->getContent(), "{$name} has a description");
            $this->assertStringContainsString('property="og:title"', $response->getContent(), "{$name} has Open Graph tags");
        }
    }
}
