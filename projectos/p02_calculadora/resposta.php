<?php
declare(strict_types=1);

/**
 * CAMADA DE RESPOSTA HTTP
 * ────────────────────────────────────────────────────────
 * Toda a saída passa por aqui. Centralizar a resposta
 * garante que os headers são sempre consistentes e que
 * nunca há output acidental antes dos headers.
 */

function responder_sucesso(array $dados, int $codigo = 200): void
{
    enviar_json([
        'sucesso' => true,
        'dados'   => $dados,
    ], $codigo);
}

function responder_erro(string $mensagem, int $codigo = 400): void
{
    enviar_json([
        'sucesso' => false,
        'erro'    => $mensagem,
    ], $codigo);
}


function enviar_json(array $payload, int $codigo = 200): void
{

    // ── HEADERS ──────────────────────────────────────────────
    // header() DEVE ser chamado antes de qualquer output.
    // Um único echo antes disto → "headers already sent" → bug.

    // Código de status HTTP
    http_response_code($codigo);

    // Content-Type: informar o cliente que a resposta é JSON.
    // O charset é opcional para JSON, mas UTF-8 é recomendado.
    header('Content-Type: application/json; charset=utf-8');

    // CORS: permite que páginas de outros domínios chamem esta API.
    // '*' = qualquer origem. Em produção, especifica o domínio.
    header('Access-Control-Allow-Origin: *');

    // Evita cache de respostas de erro
    if ($codigo >= 400) {
        header('Cache-Control: no-store');
    }

    // ── SERIALIZAR PARA JSON ─────────────────────────────────
    // JSON_UNESCAPED_UNICODE:  "café" em vez de "caf\u00e9"
    // JSON_UNESCAPED_SLASHES: "a/b" em vez de "a\/b"
    // JSON_PRETTY_PRINT:      indentação (útil em desenvolvimento)
    // JSON_THROW_ON_ERROR:    lança excepção em vez de retornar false
    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_PRETTY_PRINT
        | JSON_THROW_ON_ERROR
    );

    // exit(0): termina o script com código 0 (sucesso).
    // never return type exige que a função NUNCA retorne normalmente.
    exit(0);
    
}