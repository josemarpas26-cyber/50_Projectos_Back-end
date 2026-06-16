<?php
declare(strict_types=1);

// ─────────────────────────────────────────────
// PORQUÊ CSRF existe:
// Sem protecção, um site malicioso pode fazer o browser do utilizador
// enviar um POST para o teu servidor sem ele saber.
// Ex: o utilizador está logado no teu site. Visita evil.com.
// evil.com tem um <form action="https://teusite.com/transferir" method="POST">
// que submete automaticamente. O browser envia os cookies de sessão — autenticado.
//
// A solução: incluir no form um token secreto que só o teu servidor conhece.
// evil.com não consegue ler esse token (same-origin policy), logo não o pode incluir.
// O servidor rejeita qualquer POST sem o token correcto.
// ─────────────────────────────────────────────

/**
 * Garante que a sessão está iniciada e gera um token CSRF se não existir.
 * Deve ser chamada no início de qualquer página com form.
 */
function csrfIniciar(): void {
    if (session_status() === PHP_SESSION_NONE){
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'lax');
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}


/**
 * Devolve o token CSRF actual para incluir no form como hidden input.
 */
function csrfToken(): string{
    csrfIniciar();
    return $_SESSION['csrf_token'];
}

/**
 * Verifica se o token enviado no POST coincide com o da sessão.
 * Em caso de falha, termina imediatamente — não há recuperação.
 *
 * PORQUÊ hash_equals e não ===:
 * === em PHP tem tempo de execução variável — pode vazar informação
 * sobre o token via timing attack (medir microssegundos de diferença).
 * hash_equals garante tempo constante independentemente de onde divergem.
 *
 * @throws \RuntimeException
 */
function csrfVerificar(string $tokenEnviado): void{
    csrfIniciar();

    $tokenEsperado = $_SESSION['csrf_token'] ?? '';

    if ($tokenEsperado === '' || !hash_equals($tokenEsperado, $tokenEnviado)){
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'erro' => 'Token inválido ou sessão expirada']);
        exit;
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}