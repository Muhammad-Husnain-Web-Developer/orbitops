<?php

namespace Database\Seeders;

use App\Actions\Workspaces\AddWorkspaceMember;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ClientStatus;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\WorkspaceRole;
use App\Events\ActivityRecorded;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Expense;
use App\Models\Invitation;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ClientCommentNotification;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\InvoicePaidNotification;
use App\Notifications\MentionNotification;
use App\Notifications\ProjectUpdateNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCompletedNotification;
use App\Support\CurrentWorkspace;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Builds a fully populated demo: three workspaces, a realistic team, clients,
 * projects, tasks, time, invoices, expenses, files, activity and notifications.
 *
 * Demo logins (password "password"):
 *   demo@orbitops.app   — owner of Acme Studio & Nova Labs, member of PixelFoundry
 *   client@orbitops.app — client portal user for Northstar Media
 */
class DemoSeeder extends Seeder
{
    protected string $password;

    public function __construct(
        protected CurrentWorkspace $current,
        protected CreateWorkspace $createWorkspace,
        protected AddWorkspaceMember $addMember,
    ) {}

    public function run(): void
    {
        mt_srand(2026);

        // Seeding should not queue a realtime broadcast for every historical activity.
        Event::fake([ActivityRecorded::class]);

        // Remove stored files left behind by workspaces that no longer exist (e.g. after migrate:fresh).
        $existing = Workspace::pluck('id')->map(fn ($id) => "workspaces/{$id}")->all();
        collect(Storage::disk('local')->directories('workspaces'))
            ->reject(fn ($directory) => in_array($directory, $existing, true))
            ->each(fn ($directory) => Storage::disk('local')->deleteDirectory($directory));

        $this->password = Hash::make('password');

        $owner = $this->user('Muhammad Rahman', 'demo@orbitops.app', 'Founder & Creative Director');

        $team = [
            'sarah' => $this->user('Sarah Chen', 'sarah@acmestudio.test', 'Lead Product Designer'),
            'james' => $this->user('James Okafor', 'james@acmestudio.test', 'Engineering Lead'),
            'emma' => $this->user('Emma Walsh', 'emma@acmestudio.test', 'Project Manager'),
            'priya' => $this->user('Priya Patel', 'priya@acmestudio.test', 'Frontend Engineer'),
            'lucas' => $this->user('Lucas Silva', 'lucas@acmestudio.test', 'Brand Designer'),
            'daniel' => $this->user('Daniel Kim', 'daniel@acmestudio.test', 'Backend Engineer'),
        ];

        $acme = $this->createWorkspace->handle($owner, [
            'name' => 'Acme Studio',
            'industry' => 'Creative Agency',
            'accent' => 'violet',
            'invoice_prefix' => 'ACM',
            'default_tax_rate' => 8,
            'plan' => 'growth',
            'is_demo' => true,
        ]);
        $this->current->runAs($acme, fn () => $this->seedAcme($acme, $owner, $team));

        $nova = $this->createWorkspace->handle($owner, [
            'name' => 'Nova Labs',
            'industry' => 'Software Company',
            'accent' => 'cyan',
            'invoice_prefix' => 'NOVA',
            'plan' => 'scale',
            'is_demo' => true,
        ]);
        $this->current->runAs($nova, fn () => $this->seedNova($nova, $owner));

        $noah = $this->user('Noah Bennett', 'noah@pixelfoundry.test', 'Studio Director');
        $pixel = $this->createWorkspace->handle($noah, [
            'name' => 'PixelFoundry',
            'industry' => 'Design Studio',
            'accent' => 'rose',
            'invoice_prefix' => 'PF',
            'plan' => 'starter',
            'is_demo' => true,
        ]);
        $this->current->runAs($pixel, fn () => $this->seedPixelFoundry($pixel, $noah, $owner));

        $owner->switchWorkspace($acme);
    }

    /*
    |--------------------------------------------------------------------------
    | Acme Studio — the flagship demo workspace
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, User>  $team
     */
    protected function seedAcme(Workspace $workspace, User $owner, array $team): void
    {
        // The founder runs the studio, so only part of their week is available for project work.
        $this->addMember->handle($workspace, $owner, WorkspaceRole::Owner, attributes: ['title' => $owner->title, 'joined_at' => now()->subYears(2), 'weekly_capacity' => 20]);

        $roles = ['sarah' => WorkspaceRole::Admin, 'james' => WorkspaceRole::Manager, 'emma' => WorkspaceRole::Manager, 'priya' => WorkspaceRole::Member, 'lucas' => WorkspaceRole::Member, 'daniel' => WorkspaceRole::Member];
        // A studio that grew over the last two years; join dates prorate utilization capacity.
        $joinedDaysAgo = ['sarah' => 600, 'james' => 430, 'emma' => 260, 'lucas' => 200, 'priya' => 150, 'daniel' => 115];
        foreach ($roles as $key => $role) {
            $this->addMember->handle($workspace, $team[$key], $role, attributes: [
                'title' => $team[$key]->title,
                'joined_at' => now()->subDays($joinedDaysAgo[$key]),
                'last_active_at' => now()->subMinutes(mt_rand(3, 60 * 30)),
                'weekly_capacity' => $key === 'emma' ? 32 : 40,
            ]);
        }

        Invitation::create(['email' => 'olivia.grant@acmestudio.test', 'role' => 'member', 'invited_by' => $owner->id, 'created_at' => now()->subDay()]);

        $clients = $this->createClients([
            ['Northstar Media', 'Media & Publishing', 'Hannah Brooks', 'hannah@northstarmedia.test', 'New York', 'United States', 14],
            ['Vertex Labs', 'Biotech', 'Ryan Cole', 'ryan.cole@vertexlabs.test', 'Boston', 'United States', 11],
            ['Apex Commerce', 'E-commerce', 'Maya Johnson', 'maya@apexcommerce.test', 'Austin', 'United States', 13],
            ['Lumen Health', 'Healthcare', 'Dr. Omar Haddad', 'omar.haddad@lumenhealth.test', 'Toronto', 'Canada', 9],
            ['Bluepeak Ventures', 'Venture Capital', 'Claire Dubois', 'claire@bluepeak.test', 'London', 'United Kingdom', 4],
            ['Cobalt Robotics', 'Hardware', 'Kenji Watanabe', 'kenji@cobaltrobotics.test', 'San Francisco', 'United States', 10],
            ['Harbor & Co.', 'Hospitality', 'Isabella Rossi', 'isabella@harborandco.test', 'Lisbon', 'Portugal', 1, ClientStatus::Lead],
            ['Fieldnote Coffee', 'Food & Beverage', 'Tom Becker', 'tom@fieldnote.test', 'Portland', 'United States', 16, ClientStatus::Inactive],
        ], $owner);

        // Client portal login for Northstar Media.
        $hannah = $this->user('Hannah Brooks', 'client@orbitops.app', 'Head of Digital, Northstar Media');
        $this->addMember->handle($workspace, $hannah, WorkspaceRole::Client, $clients['Northstar Media']->id, ['title' => 'Head of Digital', 'last_active_at' => now()->subHours(1)]);

        $people = ['owner' => $owner, ...$team];

        $projects = [];

        $projects['web'] = $this->createProject($clients['Northstar Media'], $people['emma'], [
            'name' => 'Website Redesign', 'code' => 'WEB', 'color' => 'violet', 'status' => ProjectStatus::Active, 'priority' => 'high',
            'billing_type' => 'fixed', 'budget' => 84000, 'start' => 62, 'due' => -24,
            'description' => 'A complete redesign of northstarmedia.com: new information architecture, a modular design system and a faster, accessible build on a headless CMS.',
        ], [$people['sarah'], $people['priya'], $people['james'], $people['lucas']], [
            ['Discovery & Strategy', 'completed', 55, true, 'approved'],
            ['Wireframes', 'completed', 34, true, 'approved'],
            ['Visual Design', 'in_progress', 6, true, 'pending'],
            ['Development', 'pending', -16, false, null],
            ['Launch', 'pending', -24, true, null],
        ], [
            ['Stakeholder interviews', 'done', 0, 'medium', 'emma'],
            ['Audit existing site analytics', 'done', 0, 'medium', 'james'],
            ['Competitor landscape review', 'done', 0, 'low', 'sarah'],
            ['Define information architecture', 'done', 0, 'high', 'sarah'],
            ['Sitemap v2', 'done', 1, 'medium', 'sarah'],
            ['Content inventory', 'done', 1, 'low', 'emma'],
            ['Homepage wireframe', 'done', 1, 'high', 'sarah'],
            ['Service page wireframes', 'done', 1, 'medium', 'sarah'],
            ['Blog template wireframe', 'done', 1, 'low', 'lucas'],
            ['Mobile navigation pattern', 'done', 1, 'medium', 'priya'],
            ['Moodboard & visual direction', 'done', 2, 'medium', 'lucas'],
            ['Typography scale', 'done', 2, 'medium', 'lucas'],
            ['Color system tokens', 'done', 2, 'medium', 'sarah'],
            ['Homepage Design', 'done', 2, 'urgent', 'sarah'],
            ['About page design', 'done', 2, 'medium', 'lucas'],
            ['Case study template design', 'done', 2, 'medium', 'sarah'],
            ['Custom icon set', 'done', 2, 'low', 'lucas'],
            ['Design QA round 1', 'done', 2, 'medium', 'sarah'],
            ['Set up repository & environments', 'done', 3, 'high', 'james'],
            ['Configure CI pipeline', 'done', 3, 'medium', 'james'],
            ['Component library scaffold', 'done', 3, 'high', 'priya'],
            ['Header & footer components', 'done', 3, 'medium', 'priya'],
            ['Accessibility review of designs', 'done', 2, 'high', 'priya'],
            ['Client review: visual design', 'done', 2, 'high', 'emma'],
            ['Image optimisation pipeline', 'done', 3, 'medium', 'james'],
            ['Build homepage sections', 'in_progress', 3, 'high', 'priya'],
            ['CMS content modeling', 'in_progress', 3, 'high', 'james'],
            ['Responsive QA on tablet', 'review', 3, 'medium', 'sarah'],
            ['Integrate newsletter signup', 'todo', 3, 'medium', 'priya'],
            ['SEO metadata & sitemap.xml', 'todo', 3, 'medium', 'james'],
            ['Performance budget & Lighthouse pass', 'backlog', 3, 'medium', 'priya'],
            ['Launch checklist & redirects', 'backlog', 4, 'high', 'emma'],
        ], clientVisible: ['Homepage Design', 'About page design', 'Client review: visual design', 'Build homepage sections', 'Responsive QA on tablet', 'Launch checklist & redirects', 'Case study template design']);

        $projects['app'] = $this->createProject($clients['Apex Commerce'], $people['james'], [
            'name' => 'Mobile App', 'code' => 'APP', 'color' => 'blue', 'status' => ProjectStatus::Active, 'priority' => 'urgent',
            'billing_type' => 'fixed', 'budget' => 142000, 'start' => 75, 'due' => -38,
            'description' => 'Native-feeling iOS and Android shopping app with saved carts, wishlists and one-tap checkout.',
        ], [$people['priya'], $people['daniel'], $people['sarah']], [
            ['Product discovery', 'completed', 60, false, null],
            ['UX flows & prototypes', 'completed', 30, true, 'approved'],
            ['MVP build', 'in_progress', -14, false, null],
            ['Beta & App Store launch', 'pending', -38, true, null],
        ], [
            ['Map core shopping journeys', 'done', 0, 'high', 'sarah'],
            ['User interviews with 8 shoppers', 'done', 0, 'medium', 'sarah'],
            ['Clickable checkout prototype', 'done', 1, 'high', 'sarah'],
            ['Design product detail screen', 'done', 1, 'medium', 'sarah'],
            ['Auth & onboarding flow', 'done', 2, 'high', 'daniel'],
            ['Product catalog API integration', 'done', 2, 'high', 'daniel'],
            ['Cart state & persistence', 'in_progress', 2, 'urgent', 'priya'],
            ['Apple Pay & Google Pay', 'in_progress', 2, 'high', 'daniel'],
            ['Push notification service', 'todo', 2, 'medium', 'daniel'],
            ['Wishlist screen', 'review', 2, 'medium', 'priya'],
            ['Order tracking timeline', 'todo', 2, 'medium', 'priya'],
            ['Crash reporting & analytics', 'backlog', 3, 'low', 'daniel'],
            ['App Store screenshots', 'backlog', 3, 'low', 'lucas'],
            ['TestFlight beta group', 'backlog', 3, 'medium', 'james'],
        ], overdue: ['Cart state & persistence']);

        $projects['dash'] = $this->createProject($clients['Vertex Labs'], $people['james'], [
            'name' => 'SaaS Dashboard', 'code' => 'DASH', 'color' => 'cyan', 'status' => ProjectStatus::Active, 'priority' => 'high',
            'billing_type' => 'hourly', 'budget' => 96000, 'hourly_rate' => 125, 'start' => 48, 'due' => -45,
            'description' => 'Analytics dashboard for Vertex Labs researchers: experiment tracking, live charts and role-based sharing.',
        ], [$people['daniel'], $people['priya'], $people['sarah']], [
            ['Data model & API', 'completed', 20, false, null],
            ['Dashboard UI', 'in_progress', -10, true, null],
            ['Sharing & permissions', 'pending', -30, false, null],
        ], [
            ['Experiment schema design', 'done', 0, 'high', 'daniel'],
            ['Ingestion pipeline for lab results', 'done', 0, 'high', 'daniel'],
            ['REST API endpoints', 'done', 0, 'medium', 'daniel'],
            ['Dashboard layout & navigation', 'done', 1, 'medium', 'sarah'],
            ['Live chart components', 'in_progress', 1, 'high', 'priya'],
            ['Saved views & filters', 'todo', 1, 'medium', 'priya'],
            ['CSV export', 'todo', 1, 'low', 'daniel'],
            ['Role-based sharing', 'backlog', 2, 'high', 'daniel'],
            ['Audit log for experiments', 'backlog', 2, 'medium', 'daniel'],
            ['Empty & error states', 'review', 1, 'medium', 'sarah'],
        ]);

        $projects['brand'] = $this->createProject($clients['Lumen Health'], $people['sarah'], [
            'name' => 'Brand System', 'code' => 'BRND', 'color' => 'emerald', 'status' => ProjectStatus::Active, 'priority' => 'medium',
            'billing_type' => 'fixed', 'budget' => 38000, 'start' => 40, 'due' => -9,
            'description' => 'A calm, trustworthy identity for Lumen Health: logo, typography, colour, illustration and patient-facing templates.',
        ], [$people['lucas'], $people['sarah']], [
            ['Brand strategy', 'completed', 28, true, 'approved'],
            ['Identity concepts', 'completed', 12, true, 'approved'],
            ['Guidelines & templates', 'in_progress', -9, true, 'pending'],
        ], [
            ['Brand workshop', 'done', 0, 'high', 'sarah'],
            ['Positioning statement', 'done', 0, 'medium', 'sarah'],
            ['Logo concepts (3 routes)', 'done', 1, 'high', 'lucas'],
            ['Refine chosen logo route', 'done', 1, 'high', 'lucas'],
            ['Colour & accessibility testing', 'done', 1, 'medium', 'lucas'],
            ['Illustration style', 'in_progress', 2, 'medium', 'lucas'],
            ['Brand guidelines document', 'review', 2, 'high', 'sarah'],
            ['Patient leaflet templates', 'todo', 2, 'medium', 'lucas'],
            ['Social media templates', 'todo', 2, 'low', 'lucas'],
        ], clientVisible: ['Brand guidelines document', 'Logo concepts (3 routes)']);

        $projects['portal'] = $this->createProject($clients['Bluepeak Ventures'], $people['owner'], [
            'name' => 'Investor Portal', 'code' => 'PORT', 'color' => 'amber', 'status' => ProjectStatus::Planning, 'priority' => 'medium',
            'billing_type' => 'fixed', 'budget' => 68000, 'start' => -7, 'due' => -70,
            'description' => 'Secure portal for Bluepeak limited partners: fund performance, documents and capital call notices.',
        ], [$people['james'], $people['daniel']], [
            ['Discovery', 'pending', -14, false, null],
            ['Prototype', 'pending', -35, true, null],
        ], [
            ['Kickoff workshop agenda', 'todo', 0, 'medium', 'owner'],
            ['Security requirements review', 'todo', 0, 'high', 'james'],
            ['Document vault research', 'backlog', 0, 'low', 'daniel'],
        ]);

        $projects['shop'] = $this->createProject($clients['Apex Commerce'], $people['james'], [
            'name' => 'E-commerce Replatform', 'code' => 'SHOP', 'color' => 'rose', 'status' => ProjectStatus::OnHold, 'priority' => 'low',
            'billing_type' => 'hourly', 'budget' => 52000, 'hourly_rate' => 115, 'start' => 95, 'due' => -20,
            'description' => 'Migrating Apex Commerce from a legacy storefront to a headless commerce stack. Paused while the mobile app ships.',
        ], [$people['daniel']], [
            ['Migration plan', 'completed', 70, false, null],
            ['Catalog migration', 'pending', -10, false, null],
        ], [
            ['Platform comparison', 'done', 0, 'medium', 'james'],
            ['Data migration plan', 'done', 0, 'high', 'daniel'],
            ['Product import scripts', 'todo', 1, 'medium', 'daniel'],
            ['URL redirect map', 'backlog', 1, 'medium', 'daniel'],
        ]);

        $projects['launch'] = $this->createProject($clients['Cobalt Robotics'], $people['emma'], [
            'name' => 'Product Launch Campaign', 'code' => 'LNCH', 'color' => 'blue', 'status' => ProjectStatus::Completed, 'priority' => 'high',
            'billing_type' => 'fixed', 'budget' => 46000, 'start' => 150, 'due' => 70,
            'description' => 'Launch site, motion teaser and press kit for the Cobalt R2 warehouse robot.',
        ], [$people['lucas'], $people['priya']], [
            ['Campaign concept', 'completed', 130, true, 'approved'],
            ['Launch assets', 'completed', 80, true, 'approved'],
        ], [
            ['Campaign concept deck', 'done', 0, 'high', 'lucas'],
            ['Launch landing page', 'done', 1, 'high', 'priya'],
            ['Motion teaser (30s)', 'done', 1, 'medium', 'lucas'],
            ['Press kit', 'done', 1, 'medium', 'emma'],
        ], completedDaysAgo: 68);

        $projects['report'] = $this->createProject($clients['Northstar Media'], $people['sarah'], [
            'name' => 'Annual Report 2026', 'code' => 'RPT', 'color' => 'emerald', 'status' => ProjectStatus::Completed, 'priority' => 'medium',
            'billing_type' => 'fixed', 'budget' => 28000, 'start' => 200, 'due' => 120,
            'description' => 'Interactive annual report with data visualisations and a print-ready PDF edition.',
        ], [$people['lucas'], $people['priya']], [
            ['Editorial design', 'completed', 140, false, null],
        ], [
            ['Data visualisation set', 'done', 0, 'medium', 'lucas'],
            ['Interactive microsite', 'done', 0, 'high', 'priya'],
            ['Print-ready PDF', 'done', 0, 'medium', 'lucas'],
        ], completedDaysAgo: 118);

        $projects['cafe'] = $this->createProject($clients['Fieldnote Coffee'], $people['sarah'], [
            'name' => 'Brand & Storefront', 'code' => 'FNC', 'color' => 'amber', 'status' => ProjectStatus::Completed, 'priority' => 'medium',
            'billing_type' => 'fixed', 'budget' => 96000, 'start' => 182, 'due' => 100,
            'description' => 'Brand refresh, packaging system and a Shopify storefront for a specialty coffee roaster.',
        ], [$people['james'], $people['emma'], $people['priya'], $people['daniel']], [
            ['Brand refresh', 'completed', 150, true, 'approved'],
            ['Storefront launch', 'completed', 102, true, 'approved'],
        ], [
            ['Brand workshop', 'done', 0, 'high', 'sarah'],
            ['Packaging system', 'done', 0, 'medium', 'sarah'],
            ['Shopify theme build', 'done', 1, 'high', 'priya'],
            ['Subscription checkout', 'done', 1, 'high', 'daniel'],
            ['Launch QA', 'done', 1, 'medium', 'james'],
        ], completedDaysAgo: 100);

        $this->seedConversation($projects, $people, $hannah, $clients);
        $this->seedTime($workspace, $people, $projects);
        $invoices = $this->seedInvoices($clients, $projects, $people['owner']);
        $this->seedExpenses($people, $projects);
        $this->seedFiles($workspace, $people, $projects, $clients);
        $this->seedActivity($people, $hannah, $projects, $clients, $invoices);
        $this->seedNotifications($owner, $people, $hannah, $projects, $invoices);
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @return array<string, Client>
     */
    protected function createClients(array $rows, User $creator): array
    {
        return collect($rows)->mapWithKeys(function ($row) use ($creator) {
            [$name, $industry, $contact, $email, $city, $country, $monthsAgo] = $row;
            $created = now()->subMonths($monthsAgo)->subDays(mt_rand(0, 20));

            $client = Client::create([
                'name' => $name,
                'industry' => $industry,
                'status' => $row[7] ?? ClientStatus::Active,
                'contact_name' => $contact,
                'email' => $email,
                'phone' => '+1 ('.mt_rand(200, 989).') 555-0'.mt_rand(100, 199),
                'website' => 'https://'.Str::slug(Str::before($name, ' &')).'.test',
                'city' => $city,
                'country' => $country,
                'created_by' => $creator->id,
            ]);
            $client->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();

            return [$name => $client];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<User>  $members
     * @param  list<array<int, mixed>>  $milestones  [name, status, dueDaysAgo, requiresApproval, approvalStatus]
     * @param  list<array<int, mixed>>  $tasks  [title, status, milestoneIndex, priority, assigneeKey]
     * @param  list<string>  $clientVisible
     * @param  list<string>  $overdue
     */
    protected function createProject(Client $client, User $owner, array $attributes, array $members, array $milestones, array $tasks, array $clientVisible = [], array $overdue = [], ?int $completedDaysAgo = null): Project
    {
        $start = now()->subDays($attributes['start']);

        $project = Project::create([
            ...Arr::except($attributes, ['start', 'due']),
            'client_id' => $client->id,
            'owner_id' => $owner->id,
            'start_date' => $start->toDateString(),
            'due_date' => now()->subDays($attributes['due'])->toDateString(),
            'completed_at' => $completedDaysAgo ? now()->subDays($completedDaysAgo) : null,
        ]);
        $project->forceFill(['created_at' => $start->copy()->subDays(5), 'updated_at' => now()->subDays(mt_rand(0, 3))])->saveQuietly();
        $project->members()->sync(collect($members)->push($owner)->unique('id')->pluck('id'));

        $milestoneModels = collect($milestones)->values()->map(function ($row, $index) use ($project) {
            [$name, $status, $dueDaysAgo, $requiresApproval, $approval] = $row;

            return Milestone::create([
                'project_id' => $project->id,
                'name' => $name,
                'due_date' => now()->subDays($dueDaysAgo)->toDateString(),
                'status' => $status,
                'position' => $index,
                'requires_approval' => $requiresApproval,
                'approval_status' => $approval,
                'approved_at' => $approval === 'approved' ? now()->subDays(max(1, $dueDaysAgo - 2)) : null,
                'completed_at' => $status === 'completed' ? now()->subDays(max(1, $dueDaysAgo)) : null,
            ]);
        });

        $positions = [];
        $people = $this->peopleIndex();
        $assigned = collect();

        foreach ($tasks as $index => [$title, $status, $milestoneIndex, $priority, $assigneeKey]) {
            $status = TaskStatus::from($status);
            $milestone = $milestoneModels[$milestoneIndex] ?? $milestoneModels->last();
            $positions[$status->value] = ($positions[$status->value] ?? 0) + 1;
            $assignee = $people[$assigneeKey] ?? $owner;
            $assigned->push($assignee->id);

            $due = match (true) {
                in_array($title, $overdue, true) => now()->subDays(2),
                $status === TaskStatus::Done => ($milestone->due_date ?? now())->copy()->subDays(mt_rand(0, 6)),
                $status === TaskStatus::Backlog => null,
                default => now()->addDays(mt_rand(1, 18)),
            };

            $created = $start->copy()->addDays(min($index * 2, max(1, $attributes['start'] - 2)));

            $task = Task::create([
                'project_id' => $project->id,
                'milestone_id' => $milestone?->id,
                'title' => $title,
                'description' => $this->taskDescription($title),
                'status' => $status,
                'priority' => TaskPriority::from($priority),
                'assignee_id' => $assignee->id,
                'creator_id' => $owner->id,
                'due_date' => $due?->toDateString(),
                'position' => $positions[$status->value],
                'estimate_minutes' => Arr::random([480, 720, 960, 1440, 1920]),
                'visible_to_client' => in_array($title, $clientVisible, true),
            ]);

            $completedAt = $status === TaskStatus::Done
                ? Carbon::parse($due ?? now())->min(now()->subHours(mt_rand(1, 20)))
                : null;

            $task->forceFill([
                'created_at' => $created->min(now()->subDay()),
                'updated_at' => $completedAt ?? now()->subHours(mt_rand(1, 72)),
                'completed_at' => $completedAt,
            ])->saveQuietly();
        }

        $project->members()->syncWithoutDetaching($assigned->unique()->all());

        return $project;
    }

    /**
     * @return array<string, User>
     */
    protected function peopleIndex(): array
    {
        return User::all()->keyBy(fn ($user) => $user->email === 'demo@orbitops.app' ? 'owner' : Str::lower(Str::before($user->name, ' ')))->all();
    }

    protected function taskDescription(string $title): string
    {
        $templates = [
            "Scope, deliver and document {$title}.\n\n- Confirm requirements with the project lead\n- Share progress in the task thread\n- Attach final files before moving to Review",
            "Goal: {$title}.\n\nAcceptance criteria:\n- Matches the agreed design and copy\n- Works on mobile, tablet and desktop\n- Meets WCAG 2.2 AA",
            "{$title} — keep the client in the loop on decisions and note any scope changes here before starting.",
        ];

        return $templates[mt_rand(0, count($templates) - 1)];
    }

    /**
     * @param  array<string, Project>  $projects
     * @param  array<string, User>  $people
     * @param  array<string, Client>  $clients
     */
    protected function seedConversation(array $projects, array $people, User $hannah, array $clients): void
    {
        $taskComments = [
            'Homepage Design' => [
                ['sarah', 'Uploaded v3 with the new hero treatment and tightened grid. @Muhammad can you take a look before the client review?', 3 * 24 * 60],
                ['owner', 'Looks sharp. Let\'s tighten the spacing on the stats row and ship it.', 3 * 24 * 60 - 45],
                ['sarah', 'Done — spacing updated and exported to the shared folder.', 2 * 24 * 60],
            ],
            'Build homepage sections' => [
                ['priya', 'Hero and features sections are in. Working on the testimonial carousel next.', 26 * 60],
                ['james', 'Nice. Remember to respect prefers-reduced-motion on the carousel autoplay.', 25 * 60],
                ['priya', '@James good call — autoplay now pauses for reduced motion and on hover.', 4 * 60],
            ],
            'Cart state & persistence' => [
                ['daniel', 'Edge case: carts created as a guest need to merge on login. Pairing with @Priya tomorrow.', 30 * 60],
            ],
            'Brand guidelines document' => [
                ['lucas', 'Draft guidelines are up for review — 42 pages including the illustration section.', 20 * 60],
            ],
        ];

        foreach ($taskComments as $title => $comments) {
            $task = Task::where('title', $title)->first();

            foreach ($comments as [$who, $body, $minutesAgo]) {
                $this->comment($task, $people[$who], $body, $minutesAgo);
            }
        }

        $thread = [
            [$hannah, 'Thanks for the walkthrough yesterday — the whole team loved the new homepage direction.', 2 * 24 * 60],
            [$people['sarah'], 'Great to hear! We\'ve uploaded the final visual design for the remaining templates. Could you review the Visual Design milestone when you have a moment?', 2 * 24 * 60 - 90],
            [$hannah, 'Will do. One question: can we add a press section to the About page?', 26 * 60],
            [$people['emma'], 'Absolutely — I\'ve added it to the backlog and we\'ll scope it into the next sprint.', 25 * 60],
            [$hannah, 'Perfect. I\'ll get the press logos over to you this week.', 3 * 60],
        ];

        foreach ($thread as [$author, $body, $minutesAgo]) {
            $this->comment($projects['web'], $author, $body, $minutesAgo);
        }

        $this->comment($projects['brand'], $people['sarah'], 'Final logo files and the colour palette are in the Files tab.', 5 * 24 * 60);

        $notes = [
            'Northstar Media' => ['Prefers async updates through the portal; weekly sync on Thursdays at 10:00 ET.', 'Phase 2 (newsletter platform) budget expected to be approved in Q1.'],
            'Apex Commerce' => ['Maya is the decision maker; loop in their CTO for anything touching payments.'],
            'Vertex Labs' => ['Billed hourly against a 300h cap — flag at 80% usage.'],
            'Harbor & Co.' => ['Intro call went well. Sending a proposal for a booking site + brand refresh.'],
        ];

        foreach ($notes as $client => $bodies) {
            foreach ($bodies as $i => $body) {
                $this->comment($clients[$client], $people['owner'], $body, (10 - $i * 4) * 24 * 60);
            }
        }
    }

    protected function comment($commentable, User $author, string $body, int $minutesAgo): Comment
    {
        $comment = $commentable->morphMany(Comment::class, 'commentable')->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);
        $comment->forceFill(['created_at' => now()->subMinutes($minutesAgo), 'updated_at' => now()->subMinutes($minutesAgo)])->saveQuietly();

        return $comment;
    }

    /**
     * Twelve weeks of realistic timesheets for everyone on the team.
     *
     * @param  array<string, User>  $people
     * @param  array<string, Project>  $projects
     */
    protected function seedTime(Workspace $workspace, array $people, array $projects): void
    {
        $assignments = [
            'owner' => ['portal', 'web', 'brand'],
            'sarah' => ['web', 'app', 'brand', 'dash', 'cafe'],
            'james' => ['app', 'web', 'dash', 'shop', 'cafe'],
            'emma' => ['web', 'launch', 'app', 'cafe'],
            'priya' => ['web', 'app', 'dash', 'launch', 'cafe'],
            'lucas' => ['brand', 'launch', 'web', 'report'],
            'daniel' => ['dash', 'app', 'shop', 'cafe'],
        ];

        $descriptions = [
            'web' => ['Homepage build', 'Design review with Hannah', 'CMS modeling', 'Responsive QA', 'Template design'],
            'app' => ['Cart & checkout', 'Payments integration', 'Sprint planning', 'Wishlist screen', 'API integration'],
            'dash' => ['Chart components', 'Ingestion pipeline', 'Saved views', 'API endpoints'],
            'brand' => ['Illustration exploration', 'Guidelines layout', 'Logo refinements', 'Colour testing'],
            'portal' => ['Discovery prep', 'Security requirements'],
            'shop' => ['Migration planning', 'Platform research'],
            'launch' => ['Motion teaser', 'Landing page', 'Press kit'],
            'report' => ['Data visualisation', 'Print layout'],
            'cafe' => ['Brand workshop', 'Packaging design', 'Shopify theme build', 'Subscription checkout', 'Launch QA'],
        ];

        // Hour ceilings keep each project's budget burn believable across the whole history.
        $caps = ['web' => 660, 'app' => 620, 'dash' => 200, 'brand' => 200, 'launch' => 360, 'report' => 230, 'shop' => 330, 'portal' => 6, 'cafe' => 760];
        $logged = array_fill_keys(array_keys($caps), 0.0);

        $tasksByProject = Task::get(['id', 'project_id', 'title'])->groupBy('project_id');
        $joined = DB::table('memberships')->where('workspace_id', $workspace->id)->pluck('joined_at', 'user_id');
        $rows = [];

        // Newest day first (then person): recent weeks are always complete and the
        // ceilings only trim the distant past.
        for ($day = 0; $day <= 182; $day++) {
            $date = now()->subDays($day)->startOfDay();

            if ($date->isWeekend()) {
                continue;
            }

            foreach ($assignments as $key => $projectKeys) {
                $user = $people[$key];

                // Nobody logs time before they joined the studio.
                if ($joined[$user->id] && $date->lt(Carbon::parse($joined[$user->id])->startOfDay())) {
                    continue;
                }

                $available = collect($projectKeys)->filter(function ($projectKey) use ($projects, $date, $caps, &$logged) {
                    $project = $projects[$projectKey];

                    if ($logged[$projectKey] >= $caps[$projectKey]) {
                        return false;
                    }

                    // The planning-stage portal only has discovery prep logged in the last week.
                    if ($project->status === ProjectStatus::Planning) {
                        return $date->gte(now()->subDays(6));
                    }

                    return $project->start_date->lte($date)
                        && ($project->completed_at === null || $project->completed_at->gte($date));
                })->values();

                if ($available->isEmpty() || mt_rand(1, 100) <= 6) {
                    continue;
                }

                $cursor = $date->copy()->setTime(9, mt_rand(0, 3) * 15);
                $target = $key === 'owner' ? mt_rand(2, 4) * 60 : mt_rand(55, 78) * 6;
                $minutesToday = 0;

                while ($minutesToday < $target) {
                    $projectKey = $available->random();
                    $project = $projects[$projectKey];
                    $minutes = min($target - $minutesToday, Arr::random([45, 60, 90, 120, 150, 180]));
                    $start = $cursor->copy();
                    $end = $start->copy()->addMinutes($minutes);

                    if ($end->isFuture()) {
                        break;
                    }

                    $rows[] = [
                        'workspace_id' => $workspace->id,
                        'user_id' => $user->id,
                        'project_id' => $project->id,
                        'task_id' => $tasksByProject->get($project->id)?->random()->id,
                        'description' => Arr::random($descriptions[$projectKey]),
                        'started_at' => $start,
                        'ended_at' => $end,
                        'duration_seconds' => $minutes * 60,
                        'billable' => mt_rand(1, 100) > 12,
                        'created_at' => $end,
                        'updated_at' => $end,
                    ];

                    $minutesToday += $minutes;
                    $logged[$projectKey] += $minutes / 60;
                    $cursor = $end->copy()->addMinutes(Arr::random([0, 15, 30, 60]));
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('time_entries')->insert($chunk);
        }

        // A timer running right now, so the time tracker demo is live.
        DB::table('time_entries')->insert([
            'workspace_id' => $workspace->id,
            'user_id' => $people['owner']->id,
            'project_id' => $projects['web']->id,
            'task_id' => Task::where('title', 'Build homepage sections')->value('id'),
            'description' => 'Reviewing homepage build',
            'started_at' => now()->subMinutes(42)->subSeconds(17),
            'ended_at' => null,
            'duration_seconds' => 0,
            'billable' => true,
            'created_at' => now()->subMinutes(42),
            'updated_at' => now()->subMinutes(42),
        ]);
    }

    /**
     * Twelve months of invoices with a realistic mix of paid, sent, overdue and draft.
     *
     * @param  array<string, Client>  $clients
     * @param  array<string, Project>  $projects
     * @return Collection<int, Invoice>
     */
    protected function seedInvoices(array $clients, array $projects, User $owner): Collection
    {
        // Each invoice gets the history it would have had: created, sent, reminders, payments.
        $log = function (string $event, string $description, Invoice $invoice, CarbonInterface $at, array $properties = []) use ($owner) {
            Activity::record($event, $description, $invoice, $properties, $owner)
                ->forceFill(['created_at' => $at, 'updated_at' => $at])
                ->saveQuietly();
        };

        $catalogue = [
            'web' => [['Discovery & UX research', 1, 9800], ['Information architecture & wireframes', 1, 12400], ['Visual design — homepage & templates', 96, 120], ['Frontend development sprint', 120, 115]],
            'app' => [['Product discovery workshop', 1, 14500], ['UX flows & interactive prototype', 1, 18600], ['iOS & Android development sprint', 160, 125], ['QA & device testing', 60, 95]],
            'dash' => [['Backend API development', 120, 125], ['Dashboard UI development', 110, 125], ['Data pipeline engineering', 80, 125]],
            'brand' => [['Brand strategy & workshop', 1, 9600], ['Identity concepts — 3 routes', 1, 12800], ['Brand guidelines & templates', 1, 14200]],
            'shop' => [['Replatform assessment', 1, 8400], ['Migration planning', 96, 115]],
            'launch' => [['Campaign concept', 1, 9500], ['Launch landing page', 1, 14800], ['Motion teaser (30s)', 1, 11200], ['Press kit design', 1, 6400]],
            'report' => [['Editorial design', 1, 9800], ['Interactive microsite', 1, 12600], ['Print production', 1, 4800]],
            'cafe' => [['Brand refresh & packaging system', 1, 26400], ['Shopify storefront build', 1, 38600], ['Subscription checkout', 1, 14400], ['Launch support & QA', 1, 9800]],
        ];

        // [project, months ago issued, item indexes, status override]
        $schedule = [
            ['cafe', 5.6, [0], null], ['cafe', 4.4, [1], null], ['cafe', 3.4, [2, 3], null],
            ['report', 11.5, [0], null], ['report', 10.8, [1, 2], null], ['launch', 10.2, [0], null],
            ['shop', 9.6, [0], null], ['launch', 9.1, [1], null], ['launch', 8.4, [2, 3], null],
            ['shop', 7.9, [1], null], ['app', 7.2, [0], null], ['brand', 6.6, [0], null],
            ['app', 6.1, [1], null], ['web', 5.7, [0], null], ['dash', 5.2, [0], null],
            ['app', 4.6, [2], null], ['web', 4.1, [1], null], ['dash', 3.6, [0, 2], null],
            ['brand', 3.1, [1], null], ['app', 2.6, [2, 3], null], ['web', 2.2, [2], null],
            ['dash', 1.8, [1], 'overdue'], ['app', 1.5, [2], 'overdue'], ['brand', 1.1, [2], null],
            ['dash', 0.9, [2], null], ['web', 0.42, [3], 'sent'], ['dash', 0.3, [0], 'sent'], ['app', 0.2, [3], 'sent'],
            ['web', 0.1, [3], 'draft'], ['brand', 0.05, [2], 'draft'], ['shop', 4.9, [1], 'cancelled'],
        ];

        usort($schedule, fn ($a, $b) => $b[1] <=> $a[1]);

        $invoices = collect();
        $workspace = $this->current->get();

        foreach ($schedule as [$projectKey, $monthsAgo, $items, $status]) {
            $project = $projects[$projectKey];
            $issued = now()->subDays((int) round($monthsAgo * 30.4));
            $due = $issued->copy()->addDays(14);
            $status = $status ? InvoiceStatus::from($status) : InvoiceStatus::Paid;

            $invoice = Invoice::create([
                'client_id' => $project->client_id,
                'project_id' => $project->id,
                'number' => $workspace->nextInvoiceNumber(),
                'status' => $status,
                'issue_date' => $issued->toDateString(),
                'due_date' => $due->toDateString(),
                'currency' => 'USD',
                'tax_rate' => $projectKey === 'brand' ? 13 : 8,
                'discount_type' => 'percent',
                'discount_value' => in_array($projectKey, ['report', 'launch'], true) ? 5 : 0,
                'notes' => 'Thank you for working with Acme Studio. Please include the invoice number with your payment.',
                'terms' => 'Payment due within 14 days. Late payments may incur a 1.5% monthly fee.',
                'sent_at' => $status === InvoiceStatus::Draft ? null : $issued->copy()->setTime(10, 30),
            ]);

            foreach ($items as $position => $index) {
                [$description, $quantity, $price] = $catalogue[$projectKey][$index];
                $invoice->items()->create([
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'position' => $position,
                ]);
            }

            $invoice->recalculate();

            if ($status === InvoiceStatus::Paid) {
                $paidAt = $issued->copy()->addDays(mt_rand(4, 24))->setTime(mt_rand(9, 17), mt_rand(0, 59));
                $invoice->forceFill(['amount_paid' => $invoice->total, 'paid_at' => $paidAt->min(now()->subHours(3))])->saveQuietly();
            }

            $invoice->forceFill(['created_at' => $issued, 'updated_at' => $invoice->paid_at ?? $issued])->saveQuietly();
            $invoices->push($invoice);

            $log('invoice.created', 'created invoice', $invoice, $issued->copy()->setTime(9, 40));

            if ($invoice->sent_at) {
                $log('invoice.sent', 'sent invoice', $invoice, $invoice->sent_at);
            }

            if ($status === InvoiceStatus::Overdue) {
                $log('invoice.sent', 'sent a reminder for', $invoice, $due->copy()->addDays(3)->setTime(11, 5), ['to' => $invoice->client->email]);
            }

            if ($status === InvoiceStatus::Paid) {
                $log('invoice.payment', 'recorded a '.Money::format($invoice->total, 'USD').' payment on', $invoice, $invoice->paid_at, [
                    'amount' => (float) $invoice->total,
                    'method' => 'bank_transfer',
                    'reference' => 'TRX-'.mt_rand(10000, 99999),
                    'paid_on' => $invoice->paid_at->toDateString(),
                ]);
                $log('invoice.paid', 'marked as paid', $invoice, $invoice->paid_at->copy()->addSecond());
            }
        }

        // One client paying in instalments, so partial payments show up in the demo.
        $partial = $invoices->where('status', InvoiceStatus::Sent)->sortBy('issue_date')->first();
        $deposit = round((float) $partial->total * 0.4, -2);
        $paidOn = $partial->sent_at->copy()->addDays(5)->min(now()->subHours(6));
        $partial->forceFill(['amount_paid' => $deposit])->saveQuietly();
        $log('invoice.payment', 'recorded a '.Money::format($deposit, 'USD').' payment on', $partial, $paidOn, [
            'amount' => $deposit,
            'method' => 'card',
            'reference' => 'Deposit',
            'paid_on' => $paidOn->toDateString(),
        ]);

        return $invoices;
    }

    /**
     * @param  array<string, User>  $people
     * @param  array<string, Project>  $projects
     */
    protected function seedExpenses(array $people, array $projects): void
    {
        $recurring = [
            ['Figma', 'Figma Organization — 6 seats', 270, ExpenseCategory::Software],
            ['Adobe', 'Creative Cloud for teams', 285, ExpenseCategory::Software],
            ['Google', 'Google Workspace', 108, ExpenseCategory::Software],
            ['Vercel', 'Hosting & preview deployments', 60, ExpenseCategory::Software],
            ['Second Home', 'Studio desks (6)', 2100, ExpenseCategory::Office],
        ];

        for ($month = 11; $month >= 0; $month--) {
            foreach ($recurring as $i => [$vendor, $description, $amount, $category]) {
                $date = now()->subMonths($month)->startOfMonth()->addDays($i * 2 + 1);

                if ($date->isFuture()) {
                    continue;
                }

                $this->expense(null, $people['owner'], $vendor, $description, $amount * (1 + mt_rand(0, 6) / 100), $category, $date, $month === 0 ? ExpenseStatus::Pending : ExpenseStatus::Approved);
            }
        }

        $oneOff = [
            ['web', 'priya', 'Unsplash+', 'Stock photography licence', 320, ExpenseCategory::Marketing, 40, ExpenseStatus::Approved, true],
            ['web', 'sarah', 'Maze', 'Usability testing — 12 participants', 540, ExpenseCategory::Software, 28, ExpenseStatus::Approved, true],
            ['web', 'james', 'Cloudflare', 'Image CDN (annual)', 240, ExpenseCategory::Software, 9, ExpenseStatus::Pending, false],
            ['app', 'sarah', 'Respondent', 'Shopper interview incentives', 600, ExpenseCategory::Marketing, 70, ExpenseStatus::Reimbursed, true],
            ['app', 'james', 'Apple', 'Device lab — iPhone 17 & iPad', 1890, ExpenseCategory::Hardware, 52, ExpenseStatus::Approved, false],
            ['app', 'daniel', 'Delta Air Lines', 'On-site sprint review in Austin', 486, ExpenseCategory::Travel, 18, ExpenseStatus::Approved, true],
            ['app', 'daniel', 'Hilton Austin', 'Hotel — 2 nights', 412, ExpenseCategory::Travel, 17, ExpenseStatus::Reimbursed, true],
            ['brand', 'lucas', 'Studio Ilse', 'Freelance illustration — 8 spot illustrations', 2400, ExpenseCategory::Contractors, 22, ExpenseStatus::Approved, true],
            ['brand', 'lucas', 'Klim Type Foundry', 'Typeface licence', 780, ExpenseCategory::Software, 33, ExpenseStatus::Approved, true],
            ['dash', 'daniel', 'AWS', 'Staging environment', 312, ExpenseCategory::Software, 12, ExpenseStatus::Pending, true],
            ['launch', 'lucas', 'Artlist', 'Music licence for teaser', 199, ExpenseCategory::Marketing, 95, ExpenseStatus::Approved, true],
            ['launch', 'emma', 'Mosaic Print', 'Press kit printing', 860, ExpenseCategory::Marketing, 88, ExpenseStatus::Approved, true],
            ['dash', 'priya', 'Uber', 'Client workshop travel', 64, ExpenseCategory::Travel, 4, ExpenseStatus::Pending, false],
            [null, 'owner', 'Config Conference', 'Two tickets — Config 2026', 1100, ExpenseCategory::Marketing, 120, ExpenseStatus::Approved, false],
            [null, 'owner', 'Apple', 'MacBook Pro for new hire', 3199, ExpenseCategory::Hardware, 64, ExpenseStatus::Approved, false],
            [null, 'emma', 'Blue Bottle', 'Team offsite coffee', 86, ExpenseCategory::Office, 2, ExpenseStatus::Pending, false],
            [null, 'sarah', 'LinkedIn', 'Job post — Senior Designer', 495, ExpenseCategory::Marketing, 26, ExpenseStatus::Rejected, false],
        ];

        foreach ($oneOff as [$projectKey, $who, $vendor, $description, $amount, $category, $daysAgo, $status, $billable]) {
            $this->expense($projectKey ? $projects[$projectKey] : null, $people[$who], $vendor, $description, $amount, $category, now()->subDays($daysAgo), $status, $billable);
        }
    }

    protected function expense(?Project $project, User $user, string $vendor, string $description, float $amount, ExpenseCategory $category, CarbonInterface $date, ExpenseStatus $status, bool $billable = false): void
    {
        $expense = Expense::create([
            'project_id' => $project?->id,
            'user_id' => $user->id,
            'category' => $category,
            'vendor' => $vendor,
            'description' => $description,
            'amount' => round($amount, 2),
            'spent_on' => $date->toDateString(),
            'status' => $status,
            'billable' => $billable,
        ]);

        // Most expenses carry a receipt; a couple of small pending ones are still missing theirs.
        if (! in_array($vendor, ['Blue Bottle', 'Uber'], true)) {
            $path = "workspaces/{$this->current->id()}/receipts/".Str::uuid().'.pdf';
            Storage::disk('local')->put($path, $this->pdf("Receipt - {$vendor} - ".Money::format(round($amount, 2), 'USD')));
            $expense->forceFill([
                'receipt_path' => $path,
                'receipt_name' => Str::slug($vendor).'-receipt-'.$date->format('Y-m-d').'.pdf',
            ]);
        }

        $expense->forceFill(['created_at' => $date, 'updated_at' => $date])->saveQuietly();
    }

    /**
     * Real, downloadable files so the file manager and portal have content.
     *
     * @param  array<string, User>  $people
     * @param  array<string, Project>  $projects
     * @param  array<string, Client>  $clients
     */
    protected function seedFiles(Workspace $workspace, array $people, array $projects, array $clients): void
    {
        $files = [
            ['Homepage-Hero-v3.png', 'web', 'sarah', true, 1, fn () => $this->png(1600, 900, [124, 92, 255], [34, 211, 238])],
            ['Wireframes-Round-2.zip', 'web', 'sarah', false, 20, fn () => $this->zip(['README.txt' => "Wireframes round 2 — homepage, services, case study, blog.\n"])],
            ['Sitemap-and-IA.csv', 'web', 'emma', false, 30, fn () => "Section,Page,Template,Owner\nHome,Home,home,Sarah\nWork,Case studies,case-study,Sarah\nAbout,About us,about,Lucas\nAbout,Press,press,Emma\nInsights,Blog,article,Priya\n"],
            ['Kickoff-Notes.md', 'app', 'james', false, 70, fn () => "# Apex Mobile — kickoff\n\n- Launch target: before holiday season\n- Must-have: saved carts, wallet payments, order tracking\n- Weekly demo every Friday\n"],
            ['App-Store-Screenshots.png', 'app', 'lucas', true, 6, fn () => $this->png(1290, 2796, [59, 130, 246], [16, 185, 129])],
            ['Q3-Analytics-Report.pdf', 'dash', 'daniel', true, 14, fn () => $this->pdf('Vertex Labs — Q3 analytics report')],
            ['Brand-Guidelines-v2.pdf', 'brand', 'lucas', true, 3, fn () => $this->pdf('Lumen Health — Brand guidelines v2')],
            ['Logo-Final-Pack.zip', 'brand', 'lucas', true, 8, fn () => $this->zip(['lumen-logo.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="28" fill="#10b981"/></svg>'])],
            ['Press-Kit.pdf', 'launch', 'emma', true, 85, fn () => $this->pdf('Cobalt R2 — Press kit')],
        ];

        foreach ($files as [$name, $projectKey, $who, $visible, $daysAgo, $contents]) {
            $project = $projects[$projectKey];
            $path = "workspaces/{$workspace->id}/files/".Str::uuid().'-'.$name;
            $body = $contents();
            Storage::disk('local')->put($path, $body);

            $attachment = Attachment::create([
                'project_id' => $project->id,
                'client_id' => $project->client_id,
                'uploaded_by' => $people[$who]->id,
                'name' => $name,
                'disk' => 'local',
                'path' => $path,
                'mime_type' => Storage::disk('local')->mimeType($path),
                'size' => strlen($body),
                'visible_to_client' => $visible,
            ]);
            $attachment->forceFill(['created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays($daysAgo)])->saveQuietly();
        }

        $contract = "workspaces/{$workspace->id}/files/".Str::uuid().'-Northstar-MSA-2026.pdf';
        Storage::disk('local')->put($contract, $this->pdf('Master services agreement — Northstar Media'));
        Attachment::create([
            'client_id' => $clients['Northstar Media']->id,
            'uploaded_by' => $people['owner']->id,
            'name' => 'Northstar-MSA-2026.pdf',
            'path' => $contract,
            'mime_type' => 'application/pdf',
            'size' => Storage::disk('local')->size($contract),
        ]);

        // Attach the hero image to its task too.
        $hero = Attachment::where('name', 'Homepage-Hero-v3.png')->first();
        $hero->forceFill(['attachable_type' => 'task', 'attachable_id' => Task::where('title', 'Homepage Design')->value('id')])->saveQuietly();
    }

    /**
     * @param  array<string, User>  $people
     * @param  array<string, Project>  $projects
     * @param  array<string, Client>  $clients
     * @param  Collection<int, Invoice>  $invoices
     */
    protected function seedActivity(array $people, User $hannah, array $projects, array $clients, Collection $invoices): void
    {
        $task = fn (string $title) => Task::where('title', $title)->first();
        $milestone = fn (string $name, Project $project) => Milestone::where('project_id', $project->id)->where('name', $name)->first();
        $events = [
            [2, 'owner', 'project.created', 'created project', $projects['portal']],
            [18, 'sarah', 'task.completed', 'completed task', $task('Homepage Design')],
            [61, $hannah, 'milestone.approved', 'approved milestone', $milestone('Wireframes', $projects['web'])],
            [95, 'priya', 'comment.created', 'commented on', $task('Build homepage sections')],
            [140, 'james', 'task.moved', 'moved to In Progress', $task('CMS content modeling'), ['to' => 'In Progress']],
            [300, 'daniel', 'time.logged', 'logged 3h 20m on', $projects['dash']],
            [380, 'lucas', 'file.uploaded', 'uploaded', Attachment::where('name', 'Brand-Guidelines-v2.pdf')->first()],
            [26 * 60, 'owner', 'member.invited', 'invited Olivia Grant to the workspace', null],
            [27 * 60, 'priya', 'task.moved', 'moved to Review', $task('Wishlist screen'), ['to' => 'Review']],
            [31 * 60, 'sarah', 'task.created', 'created task', $task('Responsive QA on tablet')],
            [2 * 1440, 'owner', 'client.created', 'added client', $clients['Harbor & Co.']],
            [2 * 1440 + 120, 'james', 'task.assigned', 'assigned Daniel to', $task('Apple Pay & Google Pay')],
            [2 * 1440 + 300, 'emma', 'milestone.completed', 'completed milestone', $milestone('Wireframes', $projects['web'])],
            [3 * 1440, 'sarah', 'file.uploaded', 'uploaded', Attachment::where('name', 'Homepage-Hero-v3.png')->first()],
            [3 * 1440 + 200, 'daniel', 'task.completed', 'completed task', $task('Product catalog API integration')],
            [5 * 1440, 'lucas', 'task.completed', 'completed task', $task('Colour & accessibility testing')],
            [6 * 1440, 'emma', 'project.updated', 'updated the timeline of', $projects['app']],
            [7 * 1440, 'james', 'task.completed', 'completed task', $task('Set up repository & environments')],
            [8 * 1440, 'priya', 'task.completed', 'completed task', $task('Component library scaffold')],
            [9 * 1440, 'owner', 'expense.approved', 'approved expense', Expense::where('vendor', 'Studio Ilse')->first()],
            [10 * 1440, 'sarah', 'milestone.completed', 'completed milestone', $milestone('Identity concepts', $projects['brand'])],
            [12 * 1440, 'owner', 'project.created', 'created project', $projects['dash']],
        ];

        foreach ($events as $event) {
            [$minutesAgo, $who, $name, $description, $subject] = $event;
            $causer = $who instanceof User ? $who : $people[$who];
            $at = now()->subMinutes($minutesAgo);

            $activity = Activity::record($name, $description, $subject, $event[5] ?? [], $causer);
            $activity->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        }
    }

    /**
     * @param  array<string, User>  $people
     * @param  array<string, Project>  $projects
     * @param  Collection<int, Invoice>  $invoices
     */
    protected function seedNotifications(User $owner, array $people, User $hannah, array $projects, Collection $invoices): void
    {
        $task = fn (string $title) => Task::where('title', $title)->first();
        $comment = Comment::where('body', 'like', 'Uploaded v3%')->first();
        $clientMessage = Comment::where('user_id', $hannah->id)->latest('id')->first();
        $approved = Milestone::where('project_id', $projects['web']->id)->where('name', 'Wireframes')->first();
        $overdue = $invoices->firstWhere('status', InvoiceStatus::Overdue);
        $paid = $invoices->where('status', InvoiceStatus::Paid)->sortByDesc('paid_at')->first();

        $notifications = [
            [new ClientCommentNotification($clientMessage, $projects['web']), 3 * 60, false],
            [new TaskCompletedNotification($task('Homepage Design'), $people['sarah']), 18, false],
            [new ProjectUpdateNotification($approved, $hannah), 61, false],
            [new InvoiceOverdueNotification($overdue), 9 * 60, false],
            [new TaskAssignedNotification($task('Kickoff workshop agenda'), $people['emma']), 22 * 60, false],
            [new MentionNotification($comment), 3 * 1440, true],
            [new InvoicePaidNotification($paid), 29 * 60, true],
            [new TaskCompletedNotification($task('Product catalog API integration'), $people['daniel']), 3 * 1440 + 200, true],
        ];

        foreach ($notifications as [$notification, $minutesAgo, $read]) {
            $at = now()->subMinutes($minutesAgo);

            $owner->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => $notification::class,
                'data' => $notification->toArray($owner),
                'read_at' => $read ? $at->copy()->addMinutes(30) : null,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Secondary workspaces — prove tenant isolation and role differences
    |--------------------------------------------------------------------------
    */

    protected function seedNova(Workspace $workspace, User $owner): void
    {
        $ava = $this->user('Ava Thompson', 'ava@novalabs.test', 'Product Engineer');
        $this->addMember->handle($workspace, $ava, WorkspaceRole::Member, attributes: ['title' => $ava->title]);

        $clients = $this->createClients([
            ['Helix Bio', 'Biotech', 'Grace Liu', 'grace@helixbio.test', 'San Diego', 'United States', 6],
            ['Quanta Freight', 'Logistics', 'Marcus Reid', 'marcus@quantafreight.test', 'Rotterdam', 'Netherlands', 3],
            ['Pinecone Studios', 'Gaming', 'Leo Fischer', 'leo@pinecone.test', 'Berlin', 'Germany', 2],
        ], $owner);

        $this->createProject($clients['Helix Bio'], $owner, [
            'name' => 'Lab Inventory Platform', 'code' => 'LAB', 'color' => 'cyan', 'status' => ProjectStatus::Active, 'priority' => 'high',
            'billing_type' => 'hourly', 'budget' => 30000, 'hourly_rate' => 140, 'start' => 30, 'due' => -40,
            'description' => 'Barcode-driven inventory system for Helix Bio laboratories.',
        ], [$ava], [['Build', 'in_progress', -20, false, null]], [
            ['Barcode scanning prototype', 'done', 0, 'high', 'ava'],
            ['Inventory data model', 'done', 0, 'high', 'owner'],
            ['Low-stock alerts', 'in_progress', 0, 'medium', 'ava'],
            ['Supplier reorder flow', 'todo', 0, 'medium', 'ava'],
        ]);

        $this->createProject($clients['Quanta Freight'], $owner, [
            'name' => 'Fleet Tracking API', 'code' => 'FLT', 'color' => 'blue', 'status' => ProjectStatus::Planning, 'priority' => 'medium',
            'billing_type' => 'fixed', 'budget' => 24000, 'start' => -3, 'due' => -60,
            'description' => 'Realtime vehicle telemetry API and partner webhooks.',
        ], [$ava], [['Architecture', 'pending', -15, false, null]], [
            ['Telemetry ingestion spike', 'todo', 0, 'high', 'ava'],
            ['Webhook delivery design', 'backlog', 0, 'medium', 'owner'],
        ]);

        $invoice = Invoice::create([
            'client_id' => $clients['Helix Bio']->id, 'number' => $workspace->nextInvoiceNumber(), 'status' => InvoiceStatus::Sent,
            'issue_date' => now()->subDays(9)->toDateString(), 'due_date' => now()->addDays(21)->toDateString(), 'currency' => 'USD', 'sent_at' => now()->subDays(9),
        ]);
        $invoice->items()->create(['description' => 'Platform development — September', 'quantity' => 64, 'unit_price' => 140]);
        $invoice->recalculate();

        Activity::record('project.created', 'created project', Project::where('code', 'FLT')->first(), causer: $owner)
            ->forceFill(['created_at' => now()->subHours(5)])->saveQuietly();
    }

    protected function seedPixelFoundry(Workspace $workspace, User $owner, User $demo): void
    {
        $this->addMember->handle($workspace, $owner, WorkspaceRole::Owner, attributes: ['title' => $owner->title]);
        $this->addMember->handle($workspace, $demo, WorkspaceRole::Member, attributes: ['title' => 'Design Consultant']);

        $clients = $this->createClients([
            ['Atlas Outdoor', 'Retail', 'Ella Novak', 'ella@atlasoutdoor.test', 'Denver', 'United States', 5],
            ['Mira Skincare', 'Beauty', 'Sofia Mendes', 'sofia@mira.test', 'Barcelona', 'Spain', 2],
        ], $owner);

        $this->createProject($clients['Atlas Outdoor'], $owner, [
            'name' => 'Packaging Refresh', 'code' => 'PACK', 'color' => 'rose', 'status' => ProjectStatus::Active, 'priority' => 'medium',
            'billing_type' => 'fixed', 'budget' => 12000, 'start' => 20, 'due' => -25,
            'description' => 'New packaging system for the Atlas Outdoor tent and backpack ranges.',
        ], [$demo], [['Concepts', 'in_progress', -10, true, 'pending']], [
            ['Packaging audit', 'done', 0, 'medium', 'noah'],
            ['Dieline templates', 'in_progress', 0, 'medium', 'owner'],
            ['Sustainable materials research', 'todo', 0, 'low', 'noah'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function user(string $name, string $email, string $title): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'title' => $title,
            'password' => $this->password,
            'timezone' => 'UTC',
            'theme' => 'dark',
        ]);

        $user->forceFill(['email_verified_at' => now()->subMonths(6)])->save();

        return $user;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $from
     * @param  array{0: int, 1: int, 2: int}  $to
     */
    protected function png(int $width, int $height, array $from, array $to): string
    {
        $scale = 4;
        $w = intdiv($width, $scale);
        $h = intdiv($height, $scale);
        $image = imagecreatetruecolor($w, $h);

        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $color = imagecolorallocate($image, ...array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $t), $from, $to));
            imageline($image, 0, $y, $w, $y, $color);
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    protected function pdf(string $title): string
    {
        $text = str_replace(['(', ')'], ['\\(', '\\)'], $title);
        $stream = "BT /F1 24 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    /**
     * @param  array<string, string>  $entries
     */
    protected function zip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'orbitops');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();
        $contents = (string) file_get_contents($path);
        @unlink($path);

        return $contents;
    }
}
