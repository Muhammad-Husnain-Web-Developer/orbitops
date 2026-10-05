<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo login
    |--------------------------------------------------------------------------
    |
    | Shows one-click "explore the demo" buttons that sign visitors into the
    | seeded demo accounts. Disable this for a real production deployment.
    |
    */

    'demo_login' => (bool) env('ORBITOPS_DEMO_LOGIN', true),

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | Placeholder plan configuration for the marketing site and billing
    | settings. Values are illustrative and not real commercial pricing.
    |
    */

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'tagline' => 'For individuals and freelancers.',
            'monthly' => 0,
            'yearly' => 0,
            'limits' => ['members' => 2, 'projects' => 5, 'storage_gb' => 2],
            'features' => ['Up to 5 active projects', 'Clients, tasks & time tracking', 'Invoices & expenses', 'Client portal for 1 client', 'Community support'],
        ],
        'growth' => [
            'name' => 'Growth',
            'tagline' => 'For small teams shipping client work.',
            'monthly' => 29,
            'yearly' => 24,
            'limits' => ['members' => 15, 'projects' => 50, 'storage_gb' => 100],
            'features' => ['Unlimited clients', 'Up to 50 active projects', 'Kanban, milestones & approvals', 'Client portals for every client', 'Reports & team utilization', 'Roles & permissions', 'Priority email support'],
            'recommended' => true,
        ],
        'scale' => [
            'name' => 'Scale',
            'tagline' => 'For growing companies with many teams.',
            'monthly' => 79,
            'yearly' => 66,
            'limits' => ['members' => null, 'projects' => null, 'storage_gb' => 1000],
            'features' => ['Everything in Growth', 'Unlimited members & projects', 'Multiple workspaces', 'Advanced reporting', 'API access & webhooks', 'Audit-ready activity log', 'Dedicated success manager'],
        ],
    ],

];
