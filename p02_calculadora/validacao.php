<?php
declare(strict_types=1);

/**
 * VALIDAÇÃO E SANITIZAÇÃO
 * ─────────────────────────────────────────────────────────
 * Regra fundamental de segurança:
 * NUNCA confiar em dados que vêm de fora (HTTP, CLI, ficheiros).
 *
 * Sanitizar  = limpar/normalizar o valor (trim, strtolower...)
 * Validar    = verificar se o valor é aceitável (é número? existe?)
 * Escapar    = preparar para OUTPUT seguro (htmlspecialchars para HTML,
 *              prepared statements para SQL)
 *
 * Esta ordem importa: sanitizar ANTES de validar.
 */

DEFINE('OPS_PERMITIDAS', ['+', '-', '*', '/', '%', '**']);

/**
 * @param  array $raw  Dados em bruto de $_GET ou $_POST
 * @return array{valido: bool, erro: string, dados: array}
 */
function validar_input(array $raw): array
{
    // ── 1. VERIFICAR PRESENÇA ────────────────────────────────
    // Usar ?? '' em vez de isset() + acesso directo.
    // filter_input() seria mais correcto em produção — ver nota.
    $a_raw  = trim((string)($raw['a']  ?? ''));
    $b_raw  = trim((string)($raw['b']  ?? ''));
    $op_raw = trim((string)($raw['op'] ?? ''));

    if ($a_raw === '' || $b_raw === '' || $op_raw === '') {
        return erro('Parâmetros obrigatórios: a, b, op');
    }

    // ── 2. VALIDAR NÚMEROS ───────────────────────────────────
    // is_numeric() aceita: "42", "3.14", "1e5", " 42 " (com espaços)
    // Usamos após trim() para não depender do espaço.
    // NOTA: is_numeric("0x1A") = false desde PHP 7 (era true no 5)
    if (!is_numeric($a_raw)) {
        return erro("'a' deve ser numérico, recebido: '$a_raw'");
    }
    if (!is_numeric($b_raw)) {
        return erro("'b' deve ser numérico, recebido: '$b_raw'");
    }

    // ── 3. CONVERTER PARA TIPO CORRECTO ─────────────────────
    // strpos para detectar ponto decimal: int ou float
    // Podia usar filter_var com FILTER_VALIDATE_INT/FLOAT
    $a = str_contains($a_raw, '.') ? (float)$a_raw : (int)$a_raw;
    $b = str_contains($b_raw, '.') ? (float)$b_raw : (int)$b_raw;

    // ── 4. VALIDAR OPERAÇÃO ──────────────────────────────────
    // Lista branca (allowlist) — só o que está na lista passa.
    // NUNCA usar lista negra (blocklist) para segurança:
    // é impossível prever todos os valores maliciosos.
    if (!in_array($op_raw, OPS_PERMITIDAS, strict: true)) {
        // named argument: strict: true → comparação ===
        // in_array sem strict usa == → vulnerabilidade!
        return erro("Operação inválida. Permitidas: " . implode(', ', OPS_PERMITIDAS));
    }

    // ── 5. VALIDAÇÃO DE NEGÓCIO ──────────────────────────────
    // Divisão por zero: melhor detectar aqui do que apanhar excepção
    // (embora a calculadora também lance a excepção — defence in depth)
    if ($op_raw === '/' && $b == 0) {
        return erro('Divisão por zero não é permitida');
    }

    return [
        'valido' => true,
        'erro'   => '',
        'dados'  => ['a' => $a, 'b' => $b, 'op' => $op_raw],
    ];
}

/** Helper interno — evita repetição */
function erro(string $msg): array
{
    return ['valido' => false, 'erro' => $msg, 'dados' => []];
}