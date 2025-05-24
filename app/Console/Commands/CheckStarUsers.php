<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;

class CheckStarUsers extends Command
{
    protected $signature = 'check:star-users';
    protected $description = 'Check users and mark as star if their total purchases reach 200 million';

    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $total = Transaction::where('user_id', $user->id)
                ->where('transaction_type', 'purchase')
                ->sum('amount');

            if ($total >= 200_000_000 && !$user->star) {
                $user->star = true;
                $user->save();

                $this->info("User ID {$user->id} is now a STAR ⭐");
            }
        }

        $this->info('Check complete.');
    }
}
