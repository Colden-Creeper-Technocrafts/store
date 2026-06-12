<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlushTablesCommand extends Command
{
    protected $signature = 'store:flush
                            {tables?* : Table names or group shortcuts (carts, orders, coupons, otp, products, all)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Truncate selected tables (for dev/staging use)';

    // Ordered to respect foreign-key dependencies (children before parents)
    private array $allTables = [
        'cart_items',
        'carts',
        'coupon_usages',
        'coupons',
        'order_status_histories',
        'order_items',
        'orders',
        'otp_verifications',
        'product_variant_value',
        'product_variants',
        'product_tag',
        'product_images',
        'products',
    ];

    private array $groups = [
        'carts'    => ['carts', 'cart_items'],
        'orders'   => ['order_status_histories', 'order_items', 'orders'],
        'coupons'  => ['coupon_usages', 'coupons'],
        'otp'      => ['otp_verifications'],
        'products' => ['product_variant_value', 'product_variants', 'product_tag', 'product_images', 'products'],
    ];

    public function handle(): int
    {
        $requested = $this->argument('tables');

        // Interactive picker when nothing is passed
        if (empty($requested)) {
            $choices  = array_merge(['--- GROUPS ---'], array_keys($this->groups), ['all', '--- TABLES ---'], $this->allTables);
            $selected = $this->choice(
                'Select tables or groups to flush (comma-separated numbers)',
                $choices,
                null,
                null,
                true,
            );
            $requested = array_filter($selected, fn($v) => !str_starts_with($v, '---'));
        }

        // Resolve groups → table names
        $tables = [];
        foreach ($requested as $item) {
            if ($item === 'all') {
                $tables = array_merge($tables, $this->allTables);
            } elseif (array_key_exists($item, $this->groups)) {
                $tables = array_merge($tables, $this->groups[$item]);
            } elseif (in_array($item, $this->allTables)) {
                $tables[] = $item;
            } else {
                $this->error("Unknown table or group: {$item}");
                $this->line('Valid groups: ' . implode(', ', array_keys($this->groups)) . ', all');
                $this->line('Valid tables: ' . implode(', ', $this->allTables));
                return self::FAILURE;
            }
        }

        // Deduplicate, preserving FK-safe order
        $tables = array_values(array_unique($tables));

        $this->newLine();
        $this->line('<fg=yellow>Tables to be truncated:</>');
        foreach ($tables as $t) {
            $this->line("  <fg=white>·</> {$t}");
        }
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Are you sure? This cannot be undone.', false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $failed = [];

        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
                $this->line("  <fg=green>✓</> {$table}");
            } catch (\Throwable $e) {
                $failed[] = $table;
                $this->line("  <fg=red>✗</> {$table} — {$e->getMessage()}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->newLine();

        if ($failed) {
            $this->warn('Finished with errors on: ' . implode(', ', $failed));
            return self::FAILURE;
        }

        $this->info('Done. ' . count($tables) . ' table(s) truncated.');
        return self::SUCCESS;
    }
}
