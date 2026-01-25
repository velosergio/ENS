<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class Mango extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mango';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registrar un usuario con rol mango (pide correo y contraseña)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Registro de usuario mango');
        $this->newLine();

        $email = $this->ask('Correo electrónico');
        if (! $email) {
            $this->error('El correo es obligatorio.');

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (mín. 8 caracteres, mayúsculas, minúsculas y números)');
        if (! $password) {
            $this->error('La contraseña es obligatoria.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::default()],
            ],
            [
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'El correo no es válido.',
                'email.unique' => 'Ya existe un usuario con ese correo.',
                'password.required' => 'La contraseña es obligatoria.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'email' => $email,
            'password' => $password,
            'rol' => 'mango',
        ]);

        $this->info("Usuario mango creado correctamente: {$email}");

        return self::SUCCESS;
    }
}
