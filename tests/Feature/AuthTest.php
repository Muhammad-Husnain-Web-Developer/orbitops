<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use BuildsWorkspaces, RefreshDatabase;

    public function test_registration_creates_an_owner_and_their_workspace(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'workspace' => 'Analytical Engines',
            'password' => 'Correct-Horse-9',
            'password_confirmation' => 'Correct-Horse-9',
            'terms' => true,
        ])->assertRedirect();

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $workspace = Workspace::where('name', 'Analytical Engines')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame($workspace->id, $user->current_workspace_id);
        $this->assertSame(WorkspaceRole::Owner, $user->roleIn($workspace));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_requires_a_workspace_name_and_terms(): void
    {
        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'Correct-Horse-9', 'password_confirmation' => 'Correct-Horse-9'])
            ->assertSessionHasErrors(['workspace', 'terms']);

        $this->assertGuest();
    }

    public function test_unverified_users_are_asked_to_verify_before_using_the_app(): void
    {
        $workspace = $this->createWorkspace(User::factory()->unverified()->create());

        $this->as($workspace->owner)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_login_works_and_wrong_passwords_fail(): void
    {
        $workspace = $this->createWorkspace();

        $this->post('/login', ['email' => $workspace->owner->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $workspace->owner->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($workspace->owner);
    }

    public function test_two_factor_users_must_pass_the_challenge(): void
    {
        $workspace = $this->createWorkspace();
        $google = new Google2FA;
        $secret = $google->generateSecretKey();
        $workspace->owner->forceFill(['two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now()])->save();

        $this->post('/login', ['email' => $workspace->owner->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();

        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/two-factor-challenge', ['code' => $google->getCurrentOtp($secret)])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($workspace->owner);
    }

    public function test_demo_login_signs_into_the_seeded_personas_and_can_be_disabled(): void
    {
        $workspace = $this->createWorkspace(User::factory()->create(['email' => 'demo@orbitops.app']));

        $this->post(route('demo.login'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($workspace->owner);

        auth()->logout();
        config(['orbitops.demo_login' => false]);
        $this->post(route('demo.login'))->assertNotFound();
    }

    public function test_shared_demo_accounts_cannot_lock_other_visitors_out(): void
    {
        $workspace = $this->createWorkspace(User::factory()->create(['email' => 'demo@orbitops.app']));

        $this->as($workspace->owner)->putJson('/user/password', ['current_password' => 'password', 'password' => 'Another-Pass-9', 'password_confirmation' => 'Another-Pass-9'])->assertForbidden();
        $this->as($workspace->owner)->postJson(route('settings.general.update'), ['name' => 'x', 'email' => 'mine@example.com', 'timezone' => 'UTC'])->assertForbidden();
        $this->as($workspace->owner)->deleteJson(route('settings.danger.destroy'), ['name' => $workspace->name, 'password' => 'password'])->assertForbidden();

        $this->assertTrue(auth()->validate(['email' => 'demo@orbitops.app', 'password' => 'password']));
        $this->assertNotNull(Workspace::find($workspace->id));
    }
}
