<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * One command to get a fresh copy of the shop running: .env, app key, database, tables and demo data.
 * It is safe to run more than once: it never wipes data unless you pass --fresh.
 */
class InstallShop extends Command
{
    protected $signature = 'shop:install
        {--fresh : Drop every table and start over (deletes all data)}
        {--no-seed : Do not add the demo categories, products and coupons}
        {--admin-email= : Create (or update) an admin account with this email}
        {--admin-password= : Password for that admin account}';

    protected $description = 'Set up the shop: .env file, app key, database, tables and demo data';

    public function handle(): int
    {
        $this->components->info('Installing '.config('shop.name', 'the shop').'...');

        if (! $this->ensureEnvironmentFile()) {
            return self::FAILURE;
        }

        $this->ensureAppKey();

        if (! $this->ensureDatabase()) {
            return self::FAILURE;
        }

        $this->call($this->option('fresh') ? 'migrate:fresh' : 'migrate', ['--force' => true]);

        $this->seedDemoData();
        $credentials = $this->ensureAdmin();
        $this->linkStorage();

        $this->summary($credentials);

        return self::SUCCESS;
    }

    private function ensureEnvironmentFile(): bool
    {
        $env = $this->laravel->environmentFilePath();

        if (is_file($env)) {
            return true;
        }

        $example = $this->laravel->basePath('.env.example');

        if (! is_file($example)) {
            $this->components->error('There is no .env file and no .env.example to copy it from.');

            return false;
        }

        copy($example, $env);
        $this->components->info('Created the .env file.');

        return true;
    }

    private function ensureAppKey(): void
    {
        if (config('app.key')) {
            return;
        }

        $this->call('key:generate', ['--force' => true]);
    }

    /**
     * SQLite: create the file. MySQL and MariaDB: create the database. Others: just check we can connect.
     */
    private function ensureDatabase(): bool
    {
        $connection = config('database.default');
        $settings = config("database.connections.{$connection}");
        $driver = $settings['driver'] ?? $connection;

        if ($driver === 'sqlite') {
            $file = $settings['database'] ?? '';

            if ($file !== ':memory:' && $file !== '' && ! is_file($file)) {
                if (! is_dir(dirname($file))) {
                    mkdir(dirname($file), 0775, true);
                }

                touch($file);
                $this->components->info('Created the SQLite database file.');
            }

            return true;
        }

        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable $e) {
            if (in_array($driver, ['mysql', 'mariadb'], true) && $this->createServerDatabase($connection, $settings)) {
                return true;
            }

            $this->components->error('Could not connect to the database: '.$e->getMessage());
            $this->line('Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in your .env file.');

            return false;
        }
    }

    private function createServerDatabase(string $connection, array $settings): bool
    {
        $name = $settings['database'] ?? null;

        if (! $name) {
            return false;
        }

        try {
            config(["database.connections.{$connection}.database" => null]);
            DB::purge($connection);

            $quoted = str_replace('`', '``', $name);
            DB::connection($connection)->statement("CREATE DATABASE IF NOT EXISTS `{$quoted}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            config(["database.connections.{$connection}.database" => $name]);
            DB::purge($connection);
            DB::connection($connection)->getPdo();

            $this->components->info("Created the database \"{$name}\".");

            return true;
        } catch (Throwable) {
            config(["database.connections.{$connection}.database" => $name]);
            DB::purge($connection);

            return false;
        }
    }

    private function seedDemoData(): void
    {
        if ($this->option('no-seed')) {
            return;
        }

        // Only seed an empty shop, so running the installer twice never duplicates or fails.
        if (User::query()->exists() || Product::query()->exists() || Category::query()->exists()) {
            $this->components->warn('The shop already has data, so the demo data was skipped.');

            return;
        }

        $this->call('db:seed', ['--force' => true]);
    }

    /**
     * @return array{email: string, password: string|null}|null  password is only set when we generated it
     */
    private function ensureAdmin(): ?array
    {
        $email = $this->option('admin-email');

        if ($email) {
            $password = $this->option('admin-password') ?: Str::password(14, symbols: false);
            $generated = ! $this->option('admin-password');

            $user = User::firstOrNew(['email' => $email]);
            $user->name = $user->name ?: 'Store Admin';
            $user->password = $password;
            $user->is_admin = true;
            $user->save();

            return ['email' => $email, 'password' => $generated ? $password : null];
        }

        if (User::where('is_admin', true)->exists()) {
            return null;
        }

        $password = Str::password(14, symbols: false);

        User::create([
            'name' => 'Store Admin',
            'email' => 'admin@example.com',
            'password' => $password,
            'is_admin' => true,
        ]);

        return ['email' => 'admin@example.com', 'password' => $password];
    }

    private function linkStorage(): void
    {
        if (file_exists(public_path('storage'))) {
            return;
        }

        try {
            $this->callSilently('storage:link');
        } catch (Throwable) {
            $this->components->warn('Could not create the public/storage link. Run "php artisan storage:link" yourself if you upload images.');
        }
    }

    private function summary(?array $credentials): void
    {
        $this->newLine();
        $this->components->info('The shop is ready.');

        $this->line('  Start it with:  <options=bold>php artisan serve</>  then open '.config('app.url'));
        $this->line('  Admin panel:    '.rtrim(config('app.url'), '/').'/admin');

        if ($credentials) {
            $this->newLine();
            $this->line('  Admin login:    '.$credentials['email']);

            if ($credentials['password']) {
                $this->line('  Password:       '.$credentials['password'].'   (shown once, write it down)');
            }
        } elseif (! $this->option('no-seed') && User::where('email', 'admin@example.com')->exists()) {
            $this->newLine();
            $this->line('  Demo admin:     admin@example.com / password');
            $this->components->warn('Change the demo passwords before going live.');
        }
    }
}
