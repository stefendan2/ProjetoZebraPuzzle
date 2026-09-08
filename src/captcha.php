<?php

declare(strict_types=1);

function captcha_gerar_codigo(int $tamanho = 6): string
{
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $codigo = '';

    for ($indice = 0; $indice < $tamanho; $indice++) {
        $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }

    return $codigo;
}

function captcha_criar(): string
{
    $codigo = captcha_gerar_codigo();
    $_SESSION['captcha_login'] = [
        'codigo' => $codigo,
        'expira_em' => time() + (int) app_config('captcha_ttl'),
    ];

    return $codigo;
}

function captcha_validar(?string $resposta): bool
{
    $captcha = $_SESSION['captcha_login'] ?? null;
    unset($_SESSION['captcha_login']);

    if (!is_array($captcha)
        || !is_string($captcha['codigo'] ?? null)
        || !isset($captcha['expira_em'])
        || (int) $captcha['expira_em'] < time()
        || !is_string($resposta)
    ) {
        return false;
    }

    return hash_equals($captcha['codigo'], mb_strtoupper(trim($resposta), 'UTF-8'));
}

function captcha_renderizar_imagem(string $codigo): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'A extensão GD não está disponível.';
        return;
    }

    $largura = 220;
    $altura = 70;
    $imagem = imagecreatetruecolor($largura, $altura);
    $fundo = imagecolorallocate($imagem, 242, 245, 249);
    $texto = imagecolorallocate($imagem, 24, 32, 45);
    imagefill($imagem, 0, 0, $fundo);

    for ($linha = 0; $linha < 8; $linha++) {
        $cor = imagecolorallocate(
            $imagem,
            random_int(120, 205),
            random_int(120, 205),
            random_int(120, 205)
        );
        imageline(
            $imagem,
            random_int(0, $largura),
            random_int(0, $altura),
            random_int(0, $largura),
            random_int(0, $altura),
            $cor
        );
    }

    for ($ponto = 0; $ponto < 180; $ponto++) {
        $cor = imagecolorallocate(
            $imagem,
            random_int(90, 220),
            random_int(90, 220),
            random_int(90, 220)
        );
        imagesetpixel($imagem, random_int(0, $largura - 1), random_int(0, $altura - 1), $cor);
    }

    $fonte = 5;
    $x = (int) (($largura - imagefontwidth($fonte) * strlen($codigo)) / 2);
    $y = (int) (($altura - imagefontheight($fonte)) / 2);
    imagestring($imagem, $fonte, $x, $y, $codigo, $texto);

    header('Content-Type: image/png');
    imagepng($imagem);
    imagedestroy($imagem);
}
