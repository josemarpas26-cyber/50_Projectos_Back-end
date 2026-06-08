<?php
declare(strict_types=1);

/**
 * LÓGICA PURA — sem dependências de HTTP.
 * ─────────────────────────────────────────
 * Esta função pode ser testada unitariamente sem servidor web.
 * É o princípio de Separação de Responsabilidades:
 * a lógica de negócio não sabe nada sobre HTTP.
 */

/**
 * @throws DivisionByZeroError se $b === 0 e op === '/'
 * @throws InvalidArgumentException se operação desconhecida
 */

function calcular(
    int|float $a,
    int|float $b,
    string $op,
): int|float {
    // match(true): cada arm é uma condição booleana.
    // Mais expressivo que if/elseif em cadeia.
    return match(true) {
        $op === '+' => $a + $b,
        $op === '-' => $a - $b,
        $op === '*' => $a * $b,
        $op === '**' => $a ** $b,

        $op === '/' && $b !== 0 => $a / $b,
        default => throw new InvalidArgumentException("Operação inválida: $op"),
    };
}

// ── TESTAR A LÓGICA SEM HTTP ─────────────────────────────
// Descomenta para testar directamente: php calculadora.php
/*
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    var_dump(calcular(10, 3, '+'));   // int(13)
    var_dump(calcular(10, 3, '/'));   // float(3.333...)
    var_dump(calcular(10, 3, '**'));  // int(1000)
    var_dump(calcular(2.5, 1.5, '-')); // float(1.0)
}
*/