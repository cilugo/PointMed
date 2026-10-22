<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 13/10/2026 (item que tinha ficado de fora na junção de 08/10, pedido do
 * Sidney): a unidade ganha GALERIA DE FOTOS e o SITE da clínica/hospital.
 *
 *  - locais.site: endereço do site (botão "Visitar o site" na página do local).
 *    Só http/https (SalvarUnidadeRequest) — nunca "javascript:".
 *  - local_fotos: até 6 fotos por unidade (Local::MAX_FOTOS). A IMAGEM fica na
 *    tabela `fotos` (a mesma da foto de perfil, porque o Render apaga arquivo
 *    enviado); aqui fica só a referência, no mesmo formato de users.foto:
 *      "foto:<chave>"  → enviada pela clínica (App\Support\FotoDePerfil)
 *      "imgs/inst/..." → foto de demonstração, arquivo em public/
 *    Apagar a linha daqui apaga a imagem da tabela `fotos` (model LocalFoto).
 *
 * As 6 unidades de demonstração ganham a foto que a home já mostrava
 * (public/imgs/inst) como primeira foto da galeria. Isto fica na migration, e
 * não só no seeder, porque no site no ar o seeder não roda de novo.
 */
return new class extends Migration
{
    private const FOTOS_DE_DEMONSTRACAO = [
        'Santa Clara - Taubaté'     => 'imgs/inst/santaclara.jpg',
        'Vida Plena - Centro'       => 'imgs/inst/vidaplena.jpg',
        'SpSaúde - Jardim Satélite' => 'imgs/inst/spsaude.jpg',
        'Aurora - Vila Ema'         => 'imgs/inst/aurora.jpg',
        'São Lucas - Jacareí'       => 'imgs/inst/saolucas.jpg',
        'Esperança - Caçapava'      => 'imgs/inst/esperanca.jpg',
    ];

    public function up(): void
    {
        Schema::table('locais', function (Blueprint $table) {
            $table->string('site', 255)->nullable()->after('telefone');
        });

        Schema::create('local_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->constrained('locais')->cascadeOnDelete();
            $table->string('foto', 100);                 // "foto:<chave>" ou "imgs/..."
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['local_id', 'ordem']);
        });

        foreach (self::FOTOS_DE_DEMONSTRACAO as $nome => $arquivo) {
            $id = DB::table('locais')->where('nome', $nome)->value('id');
            if ($id) {
                DB::table('local_fotos')->insert([
                    'local_id' => $id, 'foto' => $arquivo, 'ordem' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // As imagens enviadas pela clínica ficam na tabela `fotos`; sem a
        // galeria, ninguém mais aponta para elas.
        $chaves = DB::table('local_fotos')->where('foto', 'like', 'foto:%')->pluck('foto')
            ->map(fn ($f) => substr($f, 5));
        DB::table('fotos')->whereIn('chave', $chaves)->delete();

        Schema::dropIfExists('local_fotos');
        Schema::table('locais', fn (Blueprint $table) => $table->dropColumn('site'));
    }
};
