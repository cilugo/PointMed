<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => DadosFicticios::EMAIL_ADMIN],
            [
                'name'     => 'Administrador PointMed',
                // 12/10/2026: era Hash::make('facilmed2026') fixo. Agora vem de
                // DadosFicticios::senha(): no site no ar, a DEMO_PASSWORD do Render.
                // O cast 'hashed' do User guarda só o hash.
                'password' => DadosFicticios::senha(),
                'tipo'     => User::TIPO_ADMIN,
                'telefone' => '(12) 3200-0000',
                'status'   => 'ativo',
            ]
        );

        $this->command->info('Admin: ' . DadosFicticios::EMAIL_ADMIN . ' / ' . DadosFicticios::textoDaSenha());
    }
}
