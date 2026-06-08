<?php
declare(strict_types=1);

function demonstrar_funcoes(): void
{
    // ── TIPAGEM BÁSICA ───────────────────────────────────────
    // PHP 7+: tipos em parâmetros e retorno
    // Com strict_types=1: TypeError se tipo errado

    function somar(int $a, int $b): int
    {
        return $a + $b;
    }

    echo somar(3, 4) . "\n";       // 7
    // somar(3, "4") → TypeError com strict_types=1
    // somar(3, "4") → 7 SEM strict_types (perigoso)

    // ── NULLABLE TYPE (?tipo) ────────────────────────────────
    // ?string = string OU null — parâmetro pode não existir
    function saudar(?string $nome = null): string
    {
        return "Olá, " . ($nome ?? 'visitante') . "!";
        // ?? = null coalescing: se $nome for null → 'visitante'
    }

    echo saudar('Josemar') . "\n";  // Olá, Josemar!
    echo saudar() . "\n";           // Olá, visitante!

    // ── UNION TYPES (PHP 8.0+) ───────────────────────────────
    // int|float = aceita int OU float
    // Útil quando a função faz sentido com vários tipos
    function duplicar(int|float $n): int|float
    {
        return $n * 2;
    }

    var_dump(duplicar(5));    // int(10)
    var_dump(duplicar(2.5));  // float(5)

    // ── INTERSECTION TYPES (PHP 8.1+) ────────────────────────
    // Tipo&Tipo = valor DEVE implementar ambos os tipos
    // Usado com interfaces — veremos nos projectos OOP

    // ── NEVER (PHP 8.1+) ─────────────────────────────────────
    // Retorno never = função NUNCA retorna (lança excepção ou exit)
    function lancar_erro(string $msg): never
    {
        throw new RuntimeException($msg);
        // PHP sabe que depois disto não há código — optimização
    }

    // ── MATCH (PHP 8.0+) vs SWITCH ──────────────────────────
    // switch: comparação loose (==), não retorna valor, precisa break
    // match: comparação strict (===), retorna valor, exaustivo
    $status = 2;

    $label = match($status) {
        1       => 'Pendente',
        2       => 'Aprovado',
        3       => 'Rejeitado',
        default => 'Desconhecido',
    };
    echo "Status: $label\n";  // Status: Aprovado

    // match sem default lança UnhandledMatchError se nenhum arm casar
    // switch sem default falha silenciosamente — bug escondido
}