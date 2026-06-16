<?php
declare(strict_types=1);

/**
 * Rate limiting simples com sessões.
 * Máximo de 5 submissões por janela de 60 segundos.
 *
 * PORQUÊ sessões para rate limiting:
 * Simples e sem dependências — suficiente para P06.
 * Em produção usarias Redis (P42) para partilhar estado entre workers.
 */
function verificarRateLimit(): void
{
    $agora  = time();
    $janela = 60;   // segundos
    $limite = 5;    // submissões por janela

    // Inicializar estrutura se não existir
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = ['contagem' => 0, 'inicio' => $agora];
    }

    $rl = &$_SESSION['rate_limit']; // referência para modificar directamente

    // Se a janela expirou, reiniciar
    if ($agora - $rl['inicio'] > $janela) {
        $rl = ['contagem' => 0, 'inicio' => $agora];
    }

    $rl['contagem']++;

    if ($rl['contagem'] > $limite) {
        $restante = $janela - ($agora - $rl['inicio']);
        http_response_code(429); // Too Many Requests
        echo json_encode([
            'sucesso' => false,
            'erro'    => "Muitas tentativas. Aguarda {$restante} segundos.",
            'codigo'  => 429,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Processa o POST do formulário.
 * Orquestra: rate limit → CSRF → sanitizar → validar → agir.
 *
 * @return array{sucesso: bool, erros?: array<string,string>, dados?: array}
 */
function processarPost(): array
{
    // 1. Rate limiting — antes de qualquer processamento
    verificarRateLimit();

    // 2. CSRF — verificar token antes de tocar nos dados
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    csrfVerificar($tokenEnviado);

    // 3. Sanitizar — limpar input
    $dadosLimpos = sanitizarFormulario($_POST);

    // 4. Validar — verificar regras
    $resultado = validarFormulario($dadosLimpos);

    if (!$resultado->valido()) {
        return [
            'sucesso' => false,
            'erros'   => $resultado->erros(),
        ];
    }

    // 5. Agir — aqui enviarias email, guardarias em BD, etc.
    // Por agora: simular sucesso e guardar na sessão para o redirect
    $_SESSION['form_sucesso'] = [
        'nome'      => $dadosLimpos['nome'],
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    return ['sucesso' => true, 'dados' => $dadosLimpos];
}