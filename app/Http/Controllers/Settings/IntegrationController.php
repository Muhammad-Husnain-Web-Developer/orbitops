<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Inertia\Response;

class IntegrationController extends Controller
{
    /**
     * Integrations on the roadmap. None are connected in this build, and the page
     * says so plainly rather than pretending a connection exists.
     */
    public function index(): Response
    {
        $this->authorize('update', $this->workspace());

        return inertia('Settings/Integrations', [
            'integrations' => [
                ['key' => 'slack', 'name' => 'Slack', 'category' => 'Communication', 'description' => 'Post project updates, approvals and payments to a channel.'],
                ['key' => 'google-calendar', 'name' => 'Google Calendar', 'category' => 'Scheduling', 'description' => 'Sync task due dates and milestones to your calendar.'],
                ['key' => 'stripe', 'name' => 'Stripe', 'category' => 'Payments', 'description' => 'Let clients pay invoices online by card or bank debit.'],
                ['key' => 'quickbooks', 'name' => 'QuickBooks', 'category' => 'Accounting', 'description' => 'Send invoices, payments and expenses to your books.'],
                ['key' => 'github', 'name' => 'GitHub', 'category' => 'Development', 'description' => 'Link pull requests to tasks and close them on merge.'],
                ['key' => 'figma', 'name' => 'Figma', 'category' => 'Design', 'description' => 'Embed live Figma frames in tasks and client approvals.'],
                ['key' => 'zapier', 'name' => 'Zapier', 'category' => 'Automation', 'description' => 'Connect OrbitOps to 6,000+ apps without code.'],
                ['key' => 'webhooks', 'name' => 'Webhooks', 'category' => 'Developers', 'description' => 'Receive signed events when records change in your workspace.'],
            ],
        ]);
    }
}
