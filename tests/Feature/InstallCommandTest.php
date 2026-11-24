<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * php artisan shop:install, run against an in-memory database whose tables already exist.
 * The .env file is redirected to a temporary folder so the tests never touch the real one.
 */
class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $envDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envDir = sys_get_temp_dir().'/shop-env-'.uniqid();
        mkdir($this->envDir);
        $this->app->useEnvironmentPath($this->envDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->envDir);

        parent::tearDown();
    }

    public function test_it_installs_the_demo_shop_and_can_be_run_twice(): void
    {
        $this->artisan('shop:install')->assertSuccessful();

        $this->assertFileExists($this->envDir.'/.env');
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com', 'is_admin' => true]);
        $this->assertDatabaseHas('coupons', ['code' => 'WELCOME10']);

        $products = Product::count();
        $this->assertGreaterThan(0, $products);

        $this->artisan('shop:install')->assertSuccessful();

        $this->assertSame($products, Product::count());
        $this->assertSame(2, User::count());
    }

    public function test_it_generates_an_app_key_when_missing(): void
    {
        config(['app.key' => '']);

        $this->artisan('shop:install', ['--no-seed' => true])->assertSuccessful();

        $this->assertStringContainsString('APP_KEY=base64:', file_get_contents($this->envDir.'/.env'));
    }

    public function test_it_creates_an_admin_with_the_given_credentials(): void
    {
        $this->artisan('shop:install', [
            '--no-seed' => true,
            '--admin-email' => 'boss@example.com',
            '--admin-password' => 'secret-pass-1',
        ])->assertSuccessful();

        $boss = User::where('email', 'boss@example.com')->firstOrFail();

        $this->assertTrue($boss->is_admin);
        $this->assertTrue(Hash::check('secret-pass-1', $boss->password));
        $this->assertSame(0, Product::count());
    }

    public function test_it_makes_an_admin_with_a_random_password_when_there_is_none(): void
    {
        $this->artisan('shop:install', ['--no-seed' => true])
            ->expectsOutputToContain('Admin login:')
            ->assertSuccessful();

        $this->assertSame(1, User::where('is_admin', true)->count());
    }

    public function test_it_does_not_add_a_second_admin_on_the_next_run(): void
    {
        $this->artisan('shop:install', ['--no-seed' => true])->assertSuccessful();
        $this->artisan('shop:install', ['--no-seed' => true])->assertSuccessful();

        $this->assertSame(1, User::where('is_admin', true)->count());
    }

    public function test_demo_data_is_skipped_when_the_shop_already_has_data(): void
    {
        Product::factory()->create();

        $this->artisan('shop:install')->assertSuccessful();

        $this->assertSame(1, Product::count());
        $this->assertDatabaseMissing('users', ['email' => 'customer@example.com']);
    }
}
