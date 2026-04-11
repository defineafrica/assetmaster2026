<?php

namespace App\Console\Commands\Inventory;

use App\Services\Inventory\ReorderPointEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckReorderPoints extends Command
{
    protected $signature = 'snipeit:check-reorder-points 
                            {--dry-run : Run without generating requisitions}
                            {--urgency= : Filter by urgency (critical, high, medium)}';

    protected $description = 'Check items below reorder point and generate alerts or requisitions';

    public function handle(ReorderPointEngine $engine): int
    {
        $this->info('Checking reorder points...');

        $report = $engine->getReorderReport();
        
        $this->info("Items below reorder point: {$report['total_items_below_reorder']}");
        $this->info("Critical: {$report['critical_count']} | High: {$report['high_count']} | Medium: {$report['medium_count']}");
        
        $this->newLine();
        $this->info('Items requiring attention:');
        $this->table(
            ['Item', 'Type', 'Current', 'Reorder Point', 'Safety Stock', 'Urgency'],
            collect($report['items'])->map(function ($item) {
                return [
                    $item['item_name'],
                    $item['item_type'],
                    $item['current_quantity'],
                    $item['reorder_point'],
                    $item['safety_stock'],
                    $item['urgency'],
                ];
            })->toArray()
        );

        if ($this->option('urgency')) {
            $urgency = $this->option('urgency');
            $filtered = collect($report['items'])->where('urgency', $urgency);
            $this->info("\nFiltered by '{$urgency}': " . $filtered->count() . " items");
        }

        Log::info('Reorder points check completed', [
            'total_below_reorder' => $report['total_items_below_reorder'],
            'critical' => $report['critical_count'],
            'high' => $report['high_count'],
            'medium' => $report['medium_count'],
            'dry_run' => $this->option('dry-run'),
        ]);

        return Command::SUCCESS;
    }
}
