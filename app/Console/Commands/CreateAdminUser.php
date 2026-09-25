<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('vanilindo:create-admin')]
#[Description('Create an administrator using private terminal prompts')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run this command in an interactive terminal so the password can stay private.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Name'));
        $email = Str::lower(trim((string) $this->ask('Email')));
        $password = (string) $this->secret('Password (at least 12 characters)', false);
        $confirmation = (string) $this->secret('Confirm password', false);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password): void {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $user->is_admin = true;
            $user->save();
        });

        $this->info('Admin user created.');

        return self::SUCCESS;
    }
}
