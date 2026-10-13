<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DadosFicticios;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Tira a senha pública das contas de demonstração NO SITE NO AR (12/10/2026).
 *
 * O PROBLEMA: o repositório é público e o README §3 mostra a senha
 * "facilmed2026" do admin, das clínicas e dos usuários do seed. Como o
 * docker/entrypoint.sh roda o seeder no Render, essas contas existem no site
 * - e qualquer pessoa que lesse o README entrava como administrador e via o
 * e-mail de todo mundo que se cadastrou.
 *
 * O QUE ESTE COMANDO FAZ (o entrypoint chama a cada boot, depois do seeder):
 *  - só age no site no ar (APP_ENV=production). Na máquina de cada um e nos
 *    testes não faz nada - lá "facilmed2026" continua valendo;
 *  - com DEMO_PASSWORD no Render: as contas de demonstração passam a usar
 *    essa senha (a variável manda: para trocar a senha, troque a variável e
 *    publique de novo);
 *  - sem DEMO_PASSWORD: a conta que ainda estiver com a senha pública ganha
 *    uma senha aleatória que ninguém sabe, e o log avisa para preencher a
 *    variável. É melhor ninguém entrar do que todo mundo entrar;
 *  - toda conta que teve a senha trocada perde as sessões abertas, o
 *    "lembrar de mim" e os links de troca de senha pendentes. Quem já tinha
 *    entrado com a senha antiga cai fora na hora.
 *
 * Não mexe em conta criada pelo site nem no admin de ADMIN_EMAIL: só nos
 * e-mails de DadosFicticios::emailsDasContas(). Nunca escreve a senha no log.
 */
class ProtegerDemonstracao extends Command
{
    protected $signature = 'facilmed:proteger-demonstracao';

    protected $description = 'No site no ar, troca a senha pública das contas de demonstração pela DEMO_PASSWORD.';

    public function handle(): int
    {
        if (! app()->isProduction()) {
            $this->line('PointMed: fora do site no ar, as contas de demonstração continuam com a senha do README.');

            return self::SUCCESS;
        }

        $temVariavel = (string) config('app.senha_demonstracao') !== '';
        $senha = DadosFicticios::senha();
        $trocadas = 0;

        foreach (User::whereIn('email', DadosFicticios::emailsDasContas())->get() as $user) {
            $precisaTrocar = $temVariavel
                ? ! Hash::check($senha, $user->password)
                : Hash::check(DadosFicticios::SENHA, $user->password);

            if (! $precisaTrocar) {
                continue;
            }

            DB::transaction(function () use ($user, $senha) {
                $user->forceFill([
                    'password'       => $senha,   // o cast 'hashed' do User guarda só o hash
                    'remember_token' => null,
                ])->save();

                DB::table('sessions')->where('user_id', $user->id)->delete();
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            });

            $trocadas++;
        }

        if (! $temVariavel) {
            $this->warn('PointMed: AVISO - DEMO_PASSWORD vazia no Render. As contas de demonstração '
                . 'ficaram com uma senha aleatória (ninguém entra nelas). Preencha a variável e publique de novo.');
        }

        $this->info("PointMed: contas de demonstração protegidas ({$trocadas} com a senha trocada agora).");

        return self::SUCCESS;
    }
}
