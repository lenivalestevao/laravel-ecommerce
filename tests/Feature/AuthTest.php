<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register(): void
    {
        $this->post(route('register'), [
            'name' => 'New Person', 'email' => 'new@example.com',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertRedirect(route('account.index'));

        $this->assertAuthenticated();
        $this->assertFalse(User::where('email', 'new@example.com')->firstOrFail()->is_admin);
    }

    public function test_registration_cannot_make_an_admin(): void
    {
        $this->post(route('register'), [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'is_admin' => 1,
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ]);

        $this->assertFalse(User::where('email', 'sneaky@example.com')->firstOrFail()->is_admin);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'password']);

        $this->post(route('login'), ['email' => 'a@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login'), ['email' => 'a@example.com', 'password' => 'password'])->assertRedirect(route('account.index'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_admin_lands_on_the_dashboard_after_login(): void
    {
        User::factory()->admin()->create(['email' => 'boss@example.com', 'password' => 'password']);

        $this->post(route('login'), ['email' => 'boss@example.com', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_account_requires_login(): void
    {
        $this->get(route('account.index'))->assertRedirect(route('login'));
    }

    public function test_customer_can_update_their_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.update'), ['name' => 'Renamed', 'email' => $user->email])
            ->assertSessionHas('success');

        $this->assertSame('Renamed', $user->fresh()->name);
    }

    public function test_password_reset_link_is_sent_without_revealing_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('success');
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertSessionHas('success');

        Notification::assertSentToTimes($user, \Illuminate\Auth\Notifications\ResetPassword::class, 1);
    }
}
