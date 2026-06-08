<?php
declare(strict_types=1);

/**
 * P02 — Calculadora HTTP
 * ──────────────────────
 * Este ficheiro é o ÚNICO ponto de entrada.
 * Responsabilidade: ler o pedido HTTP e orquestrar.
 * NÃO contém lógica de negócio — delega tudo.
 */


require_once __DIR__ . '/resposta.php';
require_once __DIR__ . '/validacao.php';
require_once __DIR__ . '/calculadora.php';

// ── MÉTODO HTTP ──────────────────────────────────────────
// $_SERVER é um superglobal — sempre disponível, não declarado.
// REQUEST_METHOD: 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'...
// Nunca confiar no valor sem validar — pode ser manipulado.

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ── LER OS DADOS CONFORME O MÉTODO ──────────────────────
// GET:  parâmetros na URL  → $_GET
// POST: parâmetros no body → $_POST
// Nunca usar $_REQUEST em produção — mistura GET+POST+COOKIE,
// tornando impossível saber de onde veio cada valor.

$dados = match($metodo) {
    'GET' => $_GET,
    'POST' => $_POST,
    default => responder_erro('Método não suportado', 405),
};

// ── VALIDAR E SANITIZAR ──────────────────────────────────
$resultado_validacao = validar_input($dados);

if (!$resultado_validacao['valido']) {
    responder_erro($resultado_validacao['erro'], 422);
}

[
    'a' => $a,
    'b' => $b,
    'op' => $op,
] = $resultado_validacao['dados'];

// ── CALCULAR O RESULTADO ─────────────────────────────────
try{
    $resultado = calcular($a, $b, $op);
} catch (DivisionByZeroError $e) {
    responder_erro('Divisão por zero não é permitida', 422);
}

// ── RESPONDER ────────────────────────────────────────────
responder_sucesso([
    'operacao'  => "$a $op $b",
    'resultado' => $resultado,
    'tipo'      => get_debug_type($resultado), // PHP 8: int ou float
]);