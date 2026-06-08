<?php
/**
 * P01 — Hello PHP + Tipos
 * Ponto de entrada: carrega e executa todos os módulos.
 *
 * declare(strict_types=1) DEVE ser a primeira instrução.
 * Activa a verificação estrita de tipos em TODA ESTA FICHEIRO.
 * Sem isto, PHP tenta coercionar automaticamente:
 *   soma(3, "2abc") → funciona silenciosamente → bug escondido.
 * Com isto, lança TypeError imediatamente → erro visível.
 */
declare(strict_types=1);

// __DIR__ é uma constante mágica: o directório do ficheiro actual.
// Usar __DIR__ . '/ficheiro.php' é sempre mais seguro que
// apenas 'ficheiro.php' — funciona independentemente de onde
// o script é chamado.
require_once __DIR__ . '/tipos.php';
require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/moderno.php';

// Separador visual entre secções de output
function secao(string $titulo): void
{
    echo PHP_EOL
        . str_repeat('─', 50) . PHP_EOL
        . "  $titulo" . PHP_EOL
        . str_repeat('─', 50) . PHP_EOL;
}

secao('1. TIPOS ESCALARES');
demonstrar_tipos();

secao('2. CASTING E COERÇÃO');
demonstrar_casting();

secao('3. TIPAGEM EM FUNÇÕES');
demonstrar_funcoes();

secao('4. PHP 8.x MODERNO');
demonstrar_moderno();