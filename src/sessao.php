<?php

declare(strict_types=1);

function usuario_logado(?string $tipo = null): bool
{
    $autenticacao = $_SESSION['autenticacao'] ?? null;
    if (!is_array($autenticacao) || !isset($autenticacao['id'], $autenticacao['tipo'])) {
        return false;
    }

    return $tipo === null || hash_equals($tipo, (string) $autenticacao['tipo']);
}

function usuario_id(): ?int
{
    return usuario_logado() ? (int) $_SESSION['autenticacao']['id'] : null;
}

function usuario_tipo(): ?string
{
    return usuario_logado() ? (string) $_SESSION['autenticacao']['tipo'] : null;
}

function autenticar_usuario(int $id, string $tipo): void
{
    if (!in_array($tipo, ['jogador', 'administrador'], true)) {
        throw new InvalidArgumentException('Tipo de conta inválido.');
    }

    session_regenerate_id(true);
    $_SESSION['autenticacao'] = ['id' => $id, 'tipo' => $tipo];
    unset($_SESSION['login_pendente'], $_SESSION['captcha_login']);
}

function login_pendente_definir(int $jogadorId): void
{
    $_SESSION['login_pendente'] = [
        'jogador_id' => $jogadorId,
        'expira_em' => time() + (int) app_config('captcha_ttl'),
    ];
    unset($_SESSION['captcha_login']);
}

function login_pendente_id(): ?int
{
    $pendente = $_SESSION['login_pendente'] ?? null;
    if (!is_array($pendente)
        || !isset($pendente['jogador_id'], $pendente['expira_em'])
        || (int) $pendente['expira_em'] < time()
    ) {
        login_pendente_limpar();
        return null;
    }

    return (int) $pendente['jogador_id'];
}

function login_pendente_limpar(): void
{
    unset($_SESSION['login_pendente'], $_SESSION['captcha_login']);
}

function encerrar_sessao_usuario(): void
{
    unset(
        $_SESSION['autenticacao'],
        $_SESSION['login_pendente'],
        $_SESSION['captcha_login'],
        $_SESSION['csrf_token'],
        $_SESSION['_formularios'],
        $_SESSION['_verificacao_email_desenvolvimento'],
        $_SESSION['_desafios_em_andamento'],
        $_SESSION['_resultado_desafio'],
        $_SESSION['_fase7_reenvios']
    );
    session_regenerate_id(true);
}

/** @return array{jogador_id:int, desafio_id:int, dia:string, tema_id:int, inicio_unix:float}|null */
function tentativa_desafio_obter(int $jogadorId, int $desafioId): ?array
{
    $tentativa = $_SESSION['_desafios_em_andamento'][(string) $jogadorId][(string) $desafioId] ?? null;
    if (!is_array($tentativa)
        || (int) ($tentativa['jogador_id'] ?? 0) !== $jogadorId
        || (int) ($tentativa['desafio_id'] ?? 0) !== $desafioId
        || !is_string($tentativa['dia'] ?? null)
        || (int) ($tentativa['tema_id'] ?? 0) < 1
        || !is_float($tentativa['inicio_unix'] ?? null)
        || $tentativa['inicio_unix'] <= 0
    ) {
        return null;
    }

    return [
        'jogador_id' => $jogadorId,
        'desafio_id' => $desafioId,
        'dia' => $tentativa['dia'],
        'tema_id' => (int) $tentativa['tema_id'],
        'inicio_unix' => $tentativa['inicio_unix'],
    ];
}

/** Reconhece apenas conclusões próprias já autorizadas, sem reutilizar o token para novas mutações. */
function registrar_token_reenvio_conclusao(int $tentativaId, string $token): void
{
    if ($tentativaId < 1 || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        throw new InvalidArgumentException('Token de conclusão inválido.');
    }
    $itens = $_SESSION['_fase7_reenvios'] ?? [];
    $itens[(string) $tentativaId] = hash('sha256', $token);
    $_SESSION['_fase7_reenvios'] = array_slice($itens, -20, null, true);
}

function reenvio_conclusao_valido(int $tentativaId, ?string $token): bool
{
    $hash = $_SESSION['_fase7_reenvios'][(string) $tentativaId] ?? null;
    return is_string($hash) && is_string($token)
        && preg_match('/^[a-f0-9]{64}$/', $token) === 1
        && hash_equals($hash, hash('sha256', $token));
}

/** @return array{jogador_id:int, desafio_id:int, dia:string, tema_id:int, inicio_unix:float} */
function iniciar_tentativa_desafio(
    int $jogadorId,
    int $desafioId,
    string $dia,
    int $temaId,
    ?float $agora = null
): array {
    $existente = tentativa_desafio_obter($jogadorId, $desafioId);
    if ($existente !== null) {
        return $existente;
    }
    if ($jogadorId < 1 || $desafioId < 1 || $temaId < 1 || !classificar_data_iso($dia)) {
        throw new InvalidArgumentException('Não foi possível iniciar uma tentativa com contexto inválido.');
    }

    $tentativa = [
        'jogador_id' => $jogadorId,
        'desafio_id' => $desafioId,
        'dia' => $dia,
        'tema_id' => $temaId,
        'inicio_unix' => $agora ?? microtime(true),
    ];
    $_SESSION['_desafios_em_andamento'][(string) $jogadorId][(string) $desafioId] = $tentativa;
    return $tentativa;
}

function tempo_decorrido_tentativa(array $tentativa, ?float $agora = null): int
{
    $inicio = $tentativa['inicio_unix'] ?? null;
    if (!is_float($inicio) || $inicio <= 0) {
        throw new InvalidArgumentException('O cronômetro da tentativa é inválido.');
    }
    $fim = $agora ?? microtime(true);
    if ($fim < $inicio) {
        throw new RuntimeException('O relógio do servidor não permite calcular o tempo da tentativa.');
    }
    return (int) round(($fim - $inicio) * 1000);
}

/** @return array<string, mixed>|null */
function encerrar_tentativa_desafio(int $jogadorId, int $desafioId): ?array
{
    $tentativa = tentativa_desafio_obter($jogadorId, $desafioId);
    unset($_SESSION['_desafios_em_andamento'][(string) $jogadorId][(string) $desafioId]);
    if (($_SESSION['_desafios_em_andamento'][(string) $jogadorId] ?? []) === []) {
        unset($_SESSION['_desafios_em_andamento'][(string) $jogadorId]);
    }
    return $tentativa;
}

/** @param array<string, mixed> $resultado */
function resultado_desafio_guardar(array $resultado): void
{
    $_SESSION['_resultado_desafio'] = $resultado;
}

/** @return array<string, mixed>|null */
function resultado_desafio_obter(int $jogadorId, ?string $dia = null): ?array
{
    $resultado = $_SESSION['_resultado_desafio'] ?? null;
    if (!is_array($resultado) || (int) ($resultado['jogador_id'] ?? 0) !== $jogadorId) {
        return null;
    }
    if ($dia !== null && (!isset($resultado['desafio_dia']) || $resultado['desafio_dia'] !== $dia)) {
        unset($_SESSION['_resultado_desafio']);
        return null;
    }
    return $resultado;
}

function exigir_login_jogador(): void
{
    if (!usuario_logado('jogador')) {
        flash_adicionar('erro', 'Entre como jogador para acessar esta página.');
        redirecionar('/login');
    }
}

function exigir_login_admin(): void
{
    if (!usuario_logado('administrador')) {
        flash_adicionar('erro', 'Entre como administrador para acessar esta página.');
        redirecionar('/login');
    }
}

function redirecionar(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}
