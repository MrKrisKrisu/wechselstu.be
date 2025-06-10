<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command {
    protected $signature   = 'app:create-user';
    protected $description = 'Create a new user';

    public function handle(): void {
        $username = $this->ask('Enter username');
        $email    = $this->ask('Enter email');
        $password = $this->secret('Enter password');

        $user = User::create([
                                 'name'     => $username,
                                 'email'    => $email,
                                 'password' => Hash::make($password),
                             ]);

        $this->info("User created successfully: {$user->name} ({$user->email})");
        $this->line('You can now log in with the new user credentials.');
    }
}
