<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spk:reset-password {password=password123} {username?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset password untuk semua user atau user tertentu';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $password = $this->argument('password');
        $username = $this->argument('username');

        $query = User::query();
        if ($username) {
            $query->where('username', $username);
        }

        $users = $query->get();
        if ($users->isEmpty()) {
            $this->error($username ? "User '{$username}' tidak ditemukan." : 'Tidak ada user di database.');
            return self::FAILURE;
        }

        foreach ($users as $user) {
            $user->password = $password;
            $user->save();
        }

        $this->info("Berhasil mengubah password untuk {$users->count()} akun menjadi: '{$password}'");
        return self::SUCCESS;
    }
}

