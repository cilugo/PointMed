<?php

namespace App\Support;

use App\Models\Foto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Foto de perfil (01/10/2026): usuário, clínica, admin e o médico
 * (este, enviado pela clínica).
 *
 * 07/10/2026: a imagem fica NO BANCO (tabela `fotos`), não mais em
 * public/uploads/fotos — o Render apaga os arquivos enviados a cada deploy.
 * Ver a migration fotos_no_banco.
 *
 * O que vai em `users.foto` / `medicos.foto`:
 *  - "foto:<chave>"  foto enviada pelo site (linha da tabela `fotos`);
 *  - "imgs/..."      foto de demonstração do seeder, arquivo em public/.
 * Só esta classe sabe a diferença; as telas usam sempre `foto_url`.
 *
 * Segurança: o FormRequest só aceita imagem (jpg, png ou webp, até 2 MB) e o
 * tipo gravado é o tipo REAL do arquivo (fileinfo do PHP), nunca o nome que
 * veio do computador de quem enviou.
 */
final class FotoDePerfil
{
    /** Marca da foto que está no banco. */
    public const PREFIXO = 'foto:';

    /** Regras de validação, iguais em todo formulário com foto. */
    public const REGRAS = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    /** Grava a foto no banco, apaga a anterior e devolve o texto que vai em `foto`. */
    public static function salvar(UploadedFile $arquivo, ?string $anterior = null): string
    {
        $mime = $arquivo->getMimeType();
        $mime = in_array($mime, Foto::TIPOS, true) ? $mime : 'image/jpeg';

        [$conteudo, $mime] = self::regravar((string) $arquivo->getRealPath(), $mime);

        $foto = Foto::create([
            'chave'    => Str::random(40),
            'mime'     => $mime,
            'conteudo' => base64_encode($conteudo),
        ]);

        self::apagar($anterior);

        return self::PREFIXO . $foto->chave;
    }

    /** Lado maior da foto guardada, em pixels (foto de perfil não precisa de mais). */
    public const LADO_MAXIMO = 1024;

    /**
     * Regrava a imagem antes de guardar (12/10/2026, revisão de segurança).
     *
     * Foto de celular leva junto "metadados" (EXIF): modelo do aparelho, data e,
     * muitas vezes, a LOCALIZAÇÃO (GPS) de onde foi tirada. As fotos de clínica
     * e de médico são públicas. Redesenhar a imagem com o GD do PHP grava só os
     * pixels: os metadados ficam para trás. De quebra, a foto fica no máximo
     * com 1024 px (menos peso no banco) e um arquivo "disfarçado" de imagem não
     * passa adiante como veio.
     *
     * O celular costuma guardar a foto deitada e anotar no EXIF "gire 90°".
     * Como o EXIF sai, a rotação é aplicada antes (girarPeloExif).
     *
     * Sem a extensão GD (XAMPP com extension=gd desligada), guarda como veio.
     *
     * @return array{0: string, 1: string} [conteúdo, tipo]
     */
    private static function regravar(string $caminho, string $mime): array
    {
        $conteudo = (string) file_get_contents($caminho);

        if (! function_exists('imagecreatefromstring')) {
            return [$conteudo, $mime];
        }

        $imagem = @imagecreatefromstring($conteudo);
        if ($imagem === false) {
            return [$conteudo, $mime];
        }

        $imagem = self::girarPeloExif($imagem, $caminho, $mime);

        $largura = imagesx($imagem);
        $altura = imagesy($imagem);
        if (max($largura, $altura) > self::LADO_MAXIMO) {
            $proporcao = self::LADO_MAXIMO / max($largura, $altura);
            $menor = imagescale($imagem, max(1, (int) round($largura * $proporcao)), max(1, (int) round($altura * $proporcao)));
            if ($menor !== false) {
                $imagem = $menor;
            }
        }

        if ($mime === 'image/webp' && ! function_exists('imagewebp')) {
            $mime = 'image/jpeg';
        }

        ob_start();
        $ok = match ($mime) {
            'image/png'  => (function () use ($imagem) {
                imagealphablending($imagem, false);
                imagesavealpha($imagem, true);

                return imagepng($imagem, null, 6);
            })(),
            'image/webp' => imagewebp($imagem, null, 85),
            default      => imagejpeg($imagem, null, 85),
        };
        $nova = (string) ob_get_clean();

        return $ok && $nova !== '' ? [$nova, $mime] : [$conteudo, $mime];
    }

    /** Aplica a rotação que o celular anotou no EXIF (só JPEG; precisa da extensão exif). */
    private static function girarPeloExif(\GdImage $imagem, string $caminho, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $imagem;
        }

        $exif = @exif_read_data($caminho);
        $angulo = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,   // imagerotate gira no sentido anti-horário
            8 => 90,
            default => 0,
        };

        if ($angulo === 0) {
            return $imagem;
        }

        $girada = imagerotate($imagem, $angulo, 0);

        return $girada !== false ? $girada : $imagem;
    }

    /**
     * Apaga SÓ foto enviada pelo site (a que está no banco).
     * As fotos de demonstração do seeder (imgs/medicos) nunca são apagadas.
     */
    public static function apagar(?string $foto): void
    {
        if ($chave = self::chave($foto)) {
            Foto::where('chave', $chave)->delete();
        }
    }

    public static function url(?string $foto): ?string
    {
        if (! $foto) {
            return null;
        }

        $chave = self::chave($foto);

        return $chave ? route('foto.mostrar', $chave) : asset($foto);
    }

    /** "foto:abc..." → "abc..."; caminho de arquivo → null. */
    public static function chave(?string $foto): ?string
    {
        return $foto && str_starts_with($foto, self::PREFIXO) ? substr($foto, strlen(self::PREFIXO)) : null;
    }
}
