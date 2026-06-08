<?php
declare(strict_types=1);

/**
 * TIPOS ESCALARES E COMPOSTOS
 * ───────────────────────────
 * PHP é dinamicamente tipado: uma variável pode mudar de tipo.
 * PHP 8+ é progressivamente tipado: podes (e deves) anotar tipos
 * onde importa — funções, propriedades de classes, retornos.
 */

function demonstrar_tipos(): void
{
    // ── INT ─────────────────────────────────────────────────
    // PHP_INT_MAX em 64-bit: 9223372036854775807
    // PHP_INT_MIN: -9223372036854775808
    // Acima do max → converte automaticamente para float

    $idade = 28;
    $negativo = -15;
    $hex = 0xFF;
    $octal = 0777;
    $binario = 0b1010;
    $grande   = 1_000_000; // underscore: separador visual (PHP 7.4+)

    echo "INT: idade=$idade, negativo=$negativo, hex=$hex, octal=$octal, binario=$binario, grande=$grande\n";
    var_dump($idade);

    
    // ── FLOAT ───────────────────────────────────────────────
    // Atenção: floats têm imprecisão inerente (IEEE 754)
    // NUNCA comparar floats com == em código de produção

    $preço = 19.99;
    $ciencia = 1.5e3;
    $inf = INF;
    $nan = NAN;

    $soma = 0.1 + 0.2;
    echo "0.1 + 0.2 = $soma\n"; // Esperado: 0.3, mas pode ser 0.30000000000000004
    echo "Comparação correcta: " . (abs($soma - 0.3) < PHP_FLOAT_EPSILON ? 'iguais' : 'diferentes') . "\n";


    // ── STRING ──────────────────────────────────────────────
    // Aspas duplas: interpola variáveis e escapes (\n, \t, \\)
    // Aspas simples: literal — mais rápido, sem interpolação

    $nome = 'Josemar';
    $saudacao = "Olá, $nome!";
    $complexo = "Olá, {$nome}!";
    $multiline = <<<EOT
    Linha 1
    Linha 2 com $nome
    EOT;

    echo $saudacao . "\n";
    echo "Tamanho: " . strlen($nome) . " caracteres\n";
    echo "MB tamanho: " . mb_strlen($nome). " caracteres\n";


    // ── BOOL ────────────────────────────────────────────────
    // Falsy em PHP: false, 0, 0.0, "0", "", "0", [], null
    // TUDO o resto é truthy — incluindo "false" (string!)
    
    $activo = true;
    $inactivo = false;
    $armadilha = "false"; // string, é truthy!

    var_dump($activo, $inactivo);
    var_dump($armadilha);


    // ── NULL ────────────────────────────────────────────────
    // null = ausência de valor. Diferente de 0, "", false.
    // isset() → false se null ou não definida
    // is_null() → true apenas se null

    $vazio = null;
    var_dump($vazio);
    var_dump(isset($vazio)); // false
    var_dump(is_null($vazio)); // true
    
    // ── ARRAY ───────────────────────────────────────────────
    // Em PHP, arrays são ordered maps — chave → valor
    // A chave pode ser int ou string. O valor pode ser qualquer coisa.

    $lista = [1,2,3];
    $associativo = ['nome' => 'Josemar', 'cidade' => 'Luanda'];
    $misto = ['id' => 1, 'tags' => ['php', 'tipos'], 'activo' => true];

    print_r($lista);
    var_dump($associativo);
    }

function demonstrar_casting(): void
{
    // ── CASTING EXPLÍCITO ────────────────────────────────────
    // (tipo)$valor — tu controlas a conversão
    $str = "42abc";
    $float = 9.99;
    $zero = "0";
    $vazio = "";

    var_dump((int)$str);    // int(42) — pára no primeiro não-numérico
    var_dump((int)$float);  // int(9)  — trunca, NÃO arredonda
    var_dump((bool)$zero);  // bool(false) — "0" é falsy!
    var_dump((bool)$vazio); // bool(false)
    var_dump((array)$str);  // array(1) { [0] => "42abc" }


    // ── COMPARAÇÃO LOOSE vs STRICT ───────────────────────────
    // == : compara VALOR após coerção de tipos (armadilha!)
    // === : compara VALOR e TIPO (sempre preferir)
    var_dump(0 ==  "foo");    // bool(false) no PHP 8 (era true no PHP 7!)
    var_dump(0 === "foo");    // bool(false)
    var_dump("1" ==  1);     // bool(true)  — coerção!
    var_dump("1" === 1);     // bool(false) — tipos diferentes
    var_dump(null ==  false); // bool(true)  — outra armadilha
    var_dump(null === false); // bool(false)
}
    