<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserCredentials extends Command
{
    protected $signature = 'app:update-users';
    protected $description = 'Update user credentials for demo purposes';

    public function handle()
    {
        $this->info('Updating user credentials...');

        // 1. Admin: Rifai (usn: admin, pwd: admin)
        $admin = User::where('email', 'admin@smartorder.local')->orWhere('role', 'admin')->first();
        if ($admin) {
            $admin->name = 'Rifai';
            $admin->username = 'admin';
            $admin->password = Hash::make('admin');
            $admin->save();
            $this->info("Updated Admin: Rifai (admin/admin)");
        } else {
            // Create if not exists
             User::create([
                'name' => 'Rifai',
                'username' => 'admin',
                'email' => 'admin@smartorder.local',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]);
            $this->info("Created Admin: Rifai (admin/admin)");
        }

        // 2. Owner Resto Bali: Bambang (usn: bambangbali, pwd: bali)
        // Find by existing email or create
        $owner = User::where('email', 'owner@bali.local')
                     ->orWhere(function($q) {
                         $q->where('role', 'owner')->where('name', 'like', '%Bambang%');
                     })->first();

        if ($owner) {
            $owner->name = 'Bambang';
            $owner->username = 'bambangbali';
            $owner->password = Hash::make('bali');
            $owner->save();
            $this->info("Updated Owner: Bambang (bambangbali/bali)");
        } else {
             // Create if not exists (assuming warung_id 1 is Bali, but let's check)
             $warung = \App\Models\Warung::first(); // Just pick first one for now or handle logic
             if($warung) {
                User::create([
                    'name' => 'Bambang',
                    'username' => 'bambangbali',
                    'email' => 'owner@bali.local',
                    'password' => Hash::make('bali'),
                    'role' => 'owner',
                    'warung_id' => $warung->id
                ]);
                $this->info("Created Owner: Bambang (bambangbali/bali)");
             } else {
                 $this->error("No Warung found to attach Owner Bambang to.");
             }
        }

        // 3. Update all other users to have a username if missing
        $users = User::whereNull('username')->orWhere('username', '')->get();
        foreach ($users as $user) {
             $base = explode('@', $user->email)[0];
             $base = preg_replace('/[^a-z0-9]/', '', strtolower($base));
             if (empty($base)) $base = 'user' . $user->id;
             
             $username = $base;
             $counter = 1;
             while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                 $username = $base . $counter++;
             }
             $user->username = $username;
             // Set password to 'password' for demo convenience so we can show it
             $user->password = Hash::make('password');
             $user->save();
             $this->info("Updated User: {$user->name} ({$user->username}/password)");
        }

        $this->info('All users updated successfully.');
    }
}
