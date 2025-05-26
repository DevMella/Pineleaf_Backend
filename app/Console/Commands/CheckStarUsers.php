<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class CheckStarUsers extends Command
{
    protected $signature = 'check:star-users';
    protected $description = 'Mark users as star if their total purchases reach 200 million';

    public function handle()
    {
        try {
            $this->info('🚀 Starting star user check...');

            $starUsersCount = 0;
            $processedUsers = 0;

            // Chunk users 100 at a time to save memory
            User::chunk(100, function ($users) use (&$starUsersCount, &$processedUsers) {
                foreach ($users as $user) {
                    $processedUsers++;
                    
                    $total = Transaction::where('user_id', $user->id)
                        ->where('transaction_type', 'purchase')
                        ->sum('amount');

                    $this->line("Processing User ID {$user->id} - Total: ₦" . number_format($total, 2) . " - Star: " . ($user->star ? 'Yes' : 'No'));

                    if ($total >= 200_000_000 && !$user->star) {
                        // Debug: Show before update
                        $this->line("Before update - User ID {$user->id}: star = {$user->star}");
                        
                        // Try direct DB update first
                        $updated = DB::table('users')
                            ->where('id', $user->id)
                            ->update(['star' => 1]);
                        
                        // Refresh the model to check if update worked
                        $user->refresh();
                        
                        $this->line("After update - User ID {$user->id}: star = {$user->star}, DB updated = {$updated}");
                        
                        if ($updated && $user->star == 1) {
                            $this->info("⭐ User ID {$user->id} marked as STAR (Total: ₦" . number_format($total, 2) . ")");
                            $starUsersCount++;
                        } else {
                            $this->error("❌ Failed to update User ID {$user->id}. Updated: {$updated}, Current star value: {$user->star}");
                            Log::error("Failed to update User ID {$user->id} to star status");
                            
                            // Try alternative update method
                            $this->line("Trying alternative update method...");
                            $user->star = 1;
                            $saved = $user->save();
                            $this->line("Save result: " . ($saved ? 'Success' : 'Failed'));
                        }
                    }
                }
            });

            $message = "✅ Star check complete. Processed {$processedUsers} users. {$starUsersCount} users marked as star.";
            $this->info($message);

            return Command::SUCCESS;

        } catch (Exception $e) {
            $errorMessage = "❌ Error during star user check: " . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage, ['exception' => $e]);
            
            return Command::FAILURE;
        }
    }
}