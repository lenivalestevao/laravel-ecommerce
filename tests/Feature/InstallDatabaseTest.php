<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * php artisan shop:install against a real SQLite file that does not exist yet,
 * which is what a person sees right after cloning the project.
 */
class InstallDatabaseTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/shop-db-'.uniqid();
        mkdir($this->dir);

        $this->app->useEnvironmentPath($this->dir);
        config(['database.connections.sqlite.database' => $this->dir.'/nested/shop.sqlite']);
        DB::purge('sqlite');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        (new Filesystem)->deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_it_creates_the_database_file_and_all_the_tables(): void
    {
        $this->assertFileDoesNotExist($this->dir.'/nested/shop.sqlite');

        $this->artisan('shop:install')->assertSuccessful();

        $this->assertFileExists($this->dir.'/nested/shop.sqlite');

        foreach (['users', 'categories', 'products', 'coupons', 'orders', 'order_items'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        $this->assertGreaterThan(0, Product::count());
        $this->assertTrue(User::where('email', 'admin@example.com')->firstOrFail()->is_admin);
    }

    public function test_fresh_wipes_the_data_and_starts_over(): void
    {
        $this->artisan('shop:install')->assertSuccessful();
        $this->assertGreaterThan(0, Product::count());

        $this->artisan('shop:install', ['--fresh' => true, '--no-seed' => true])->assertSuccessful();

        $this->assertSame(0, Product::count());
        $this->assertSame(1, User::count()); // only the freshly made admin
    }
}
