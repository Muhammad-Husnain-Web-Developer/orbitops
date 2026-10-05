<?php

namespace App\Http\Controllers;

use Inertia\Response;

class MarketingController extends Controller
{
    public function home(): Response
    {
        return $this->page('Marketing/Home',
            'OrbitOps — Run your business. Not your spreadsheets.',
            'OrbitOps brings clients, projects, teams, time, invoices and business operations into one intelligent workspace for agencies, studios and service businesses.',
            ['plans' => $this->plans()],
        );
    }

    public function features(): Response
    {
        return $this->page('Marketing/Features',
            'Features — OrbitOps',
            'Workspaces, clients, projects, Kanban, time tracking, invoicing, expenses, client portals, reports and activity logs in one multi-tenant workspace.',
        );
    }

    public function pricing(): Response
    {
        return $this->page('Marketing/Pricing',
            'Pricing — OrbitOps',
            'Simple plans for freelancers, small teams and growing companies. Start free and upgrade when your team grows.',
            ['plans' => $this->plans()],
        );
    }

    public function about(): Response
    {
        return $this->page('Marketing/About',
            'About — OrbitOps',
            'OrbitOps exists to give service businesses one calm, connected place to run client work — from first proposal to final invoice.',
        );
    }

    public function contact(): Response
    {
        return $this->page('Marketing/Contact',
            'Contact — OrbitOps',
            'Talk to the OrbitOps team about demos, pricing, migrations and partnerships.',
        );
    }

    public function changelog(): Response
    {
        return $this->page('Marketing/Changelog',
            'Changelog & roadmap — OrbitOps',
            'What shipped recently in OrbitOps and what is coming next.',
        );
    }

    public function docs(): Response
    {
        return $this->page('Marketing/Docs',
            'Documentation — OrbitOps',
            'Get started with OrbitOps: workspaces, roles, projects, time tracking, invoicing and the client portal.',
        );
    }

    public function privacy(): Response
    {
        return $this->page('Marketing/Legal',
            'Privacy policy — OrbitOps',
            'How OrbitOps collects, uses and protects workspace data.',
            ['document' => 'privacy'],
        );
    }

    public function terms(): Response
    {
        return $this->page('Marketing/Legal',
            'Terms of service — OrbitOps',
            'The terms that govern use of OrbitOps.',
            ['document' => 'terms'],
        );
    }

    /**
     * Render a marketing page. SEO tags are rendered server-side in the root view
     * (for crawlers) and mirrored as a prop so client-side navigation keeps them current.
     *
     * @param  array<string, mixed>  $props
     */
    protected function page(string $component, string $title, string $description, array $props = []): Response
    {
        $seo = [
            'title' => $title,
            'description' => $description,
            'canonical' => url()->current(),
            'image' => asset('og-image.png'),
        ];

        return inertia($component, [
            ...$props,
            'seo' => $seo,
            'social' => config('orbitops.social'),
            'demoEnabled' => (bool) config('orbitops.demo_login'),
        ])
            ->withViewData(['meta' => $seo]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function plans(): array
    {
        return config('orbitops.plans');
    }
}
