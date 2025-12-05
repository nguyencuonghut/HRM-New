<?php

namespace App\Console\Commands;

use App\Models\LeaveBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'leave:reset-carried-forward',
    description: 'Reset carried forward leave balances after Q1 ends (April 1st)'
)]
class ResetCarriedForwardLeave extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(?int $year = null)
    {
        $year = $year ?? now()->year;

        // This command should only run after March 31st
        if (now()->month < 4 && $year == now()->year) {
            $this->warn('This command should only run after Q1 ends (April 1st)');

            if (!$this->confirm('Do you want to continue anyway?')) {
                return 1;
            }
        }

        $this->info("Resetting carried forward balances for year {$year}...");

        DB::beginTransaction();
        try {
            // Get all balances with carried forward
            $balances = LeaveBalance::where('year', $year)
                ->where('carried_forward', '>', 0)
                ->get();

            if ($balances->isEmpty()) {
                $this->info('No carried forward balances to reset.');
                return 0;
            }

            $progressBar = $this->output->createProgressBar($balances->count());
            $resetCount = 0;

            foreach ($balances as $balance) {
                // Check if employee used the carried forward days
                $carriedForwardUsed = max(0, $balance->carried_forward - $balance->remaining_days);

                // Reset: remove unused carried forward from total
                $balance->total_days -= ($balance->carried_forward - $carriedForwardUsed);
                $balance->remaining_days = max(0, $balance->remaining_days - $balance->carried_forward);
                $balance->carried_forward = 0;
                $balance->save();

                $resetCount++;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info("✓ Reset {$resetCount} carried forward leave balances");
            $this->info('Carried forward leave balances reset successfully!');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to reset carried forward: {$e->getMessage()}");
            return 1;
        }
    }
}
