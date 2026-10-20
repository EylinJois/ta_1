<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Michael Anderson',  'email' => 'admin@lms.test',              'is_active' => 1],
            ['name' => 'Sarah Johnson',     'email' => 'sarah.johnson@lms.test',      'is_active' => 1],
            ['name' => 'David Thompson',    'email' => 'david.thompson@lms.test',     'is_active' => 1],
            ['name' => 'Emily Carter',      'email' => 'emily.carter@lms.test',       'is_active' => 1],
            ['name' => 'James Wilson',      'email' => 'james.wilson@lms.test',       'is_active' => 1],
            ['name' => 'Olivia Martinez',   'email' => 'olivia.martinez@lms.test',    'is_active' => 1],
            ['name' => 'Daniel Brooks',     'email' => 'daniel.brooks@lms.test',      'is_active' => 1],
            ['name' => 'Sophia Nguyen',     'email' => 'sophia.nguyen@lms.test',      'is_active' => 1],
            ['name' => 'Ethan Walker',      'email' => 'ethan.walker@lms.test',       'is_active' => 0],
            ['name' => 'Isabella Reed',     'email' => 'isabella.reed@lms.test',      'is_active' => 0],
            ['name' => 'Matthew Collins',   'email' => 'matthew.collins@lms.test',    'is_active' => 0],
            ['name' => 'Ava Mitchell',      'email' => 'ava.mitchell@lms.test',       'is_active' => 0],
            ['name' => 'Christopher Hayes', 'email' => 'christopher.hayes@lms.test',  'is_active' => 0],
            ['name' => 'Mia Foster',        'email' => 'mia.foster@lms.test',         'is_active' => 0],
            ['name' => 'Andrew Bennett',    'email' => 'andrew.bennett@lms.test',     'is_active' => 0],
            ['name' => 'Charlotte Price',   'email' => 'charlotte.price@lms.test',    'is_active' => 0],
            ['name' => 'Joshua Sanders',    'email' => 'joshua.sanders@lms.test',     'is_active' => 0],
            ['name' => 'Amelia Griffin',    'email' => 'amelia.griffin@lms.test',     'is_active' => 0],
            ['name' => 'Ryan Cooper',       'email' => 'ryan.cooper@lms.test',        'is_active' => 0],
            ['name' => 'Grace Henderson',   'email' => 'grace.henderson@lms.test',    'is_active' => 0],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'              => $user['name'],
                    'img'               => null,
                    'is_active'         => $user['is_active'],
                    'password'          => Hash::make('12345678'),
                    'email_verified_at' => null,
                    'remember_token'    => null,
                ]
            );
        }
    }
}