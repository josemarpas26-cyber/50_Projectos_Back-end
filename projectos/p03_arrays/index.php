<?php
declare(strict_types=1);

// Carregar os dados e a lógica
require_once __DIR__ . '/dados.php';
require_once __DIR__ . '/pipeline.php';

// ══════════════════════════════════════════════
// CORRER O PIPELINE COMPLETO
// ══════════════════════════════════════════════

echo "════════════════════════════════════\n";
echo "  P03 — MANIPULADOR DE ARRAYS\n";
echo "════════════════════════════════════\n\n";


// ── ETAPA 1: Dados brutos da "BD"
$brutos = obterProdutosBrutos();
echo "📦 DADOS BRUTOS (8 produtos, tipos sujos):\n";
echo "  preco[0] tipo: " . get_debug_type($brutos[0]['preco']) . " → '{$brutos[0]['preco']}'\n";
echo "  activo[0] tipo: " . get_debug_type($brutos[0]['activo']) . " → {$brutos[0]['activo']}\n\n";


// ── ETAPA 2: Normalizar com array_map
$normalizados = normalizarProdutos($brutos);
echo "✅ APÓS NORMALIZAÇÃO (array_map):\n";
echo "  preco[0] tipo: " . get_debug_type($normalizados[0]['preco']) . " → {$normalizados[0]['preco']}\n";
echo "  activo[0] tipo: " . get_debug_type($normalizados[0]['activo']) . " → " . ($normalizados[0]['activo'] ? 'true' : 'false') . "\n";
echo "  categoria[0]: '" . $normalizados[0]['categoria'] . "' (sem espaços)\n\n";


// ── ETAPA 3: Filtrar disponíveis (activo + stock > 0)
$disponiveis = filtrarDisponiveis($normalizados);
echo "🔍 APÓS FILTRO (array_filter — activo + stock > 0):\n";
echo "  Total: " . count($disponiveis) . " de 8 produtos\n";
$nomesDisponiveis = extrairNomes($disponiveis);
echo "  Produtos: " . implode(', ', $nomesDisponiveis) . "\n\n";


// ── ETAPA 4: Filtrar por categoria
$electronicos = filtrarPorCategoria($disponiveis, 'electrónica');
echo "🏷️  FILTRO POR CATEGORIA 'electrónica' (arrow fn):\n";
echo "  Total: " . count($electronicos) . " produtos\n";
foreach ($electronicos as $p) {
    echo "  - {$p['nome']} (€{$p['preco']}, stock: {$p['stock']})\n";
}
echo "\n";


// ── ETAPA 5: Calcular valor de inventário com array_reduce
$valorTotal = calcularValorInventario($disponiveis);
echo "💰 VALOR TOTAL DO INVENTÁRIO (array_reduce):\n";
// number_format: 2 casas decimais, vírgula decimal, ponto milhares
echo "  €" . number_format($valorTotal, 2, ',', '.') . "\n\n";


// ── ETAPA 6: Agrupar por categoria com array_reduce
$grupos = agruparPorCategoria($disponiveis);
echo "📂 AGRUPADO POR CATEGORIA (array_reduce com acumulador array):\n";
foreach ($grupos as $categoria => $produtos) {
    echo "  [$categoria]: " . count($produtos) . " produto(s)\n";
}
echo "\n";


// ── ETAPA 7: Ordenar por preço descendente (usort)
$ordenados = ordenarPor($disponiveis, campo: 'preco', desc: true);
echo "📊 ORDENADO POR PREÇO DESCENDENTE (usort + spaceship):\n";
foreach ($ordenados as $p) {
    echo "  {$p['nome']}: €{$p['preco']}\n";
}
echo "\n";


// ── ETAPA 8: Mapa id → nome (array_column com índice)
$mapa = criarMapaIdNome($disponiveis);
echo "🗺️  MAPA ID → NOME (array_column com 3 args):\n";
foreach ($mapa as $id => $nome) {
    echo "  [{$id}] → {$nome}\n";
}
echo "\n";


// ── ETAPA 9: Spread operator — fundir duas listas
$lista1 = filtrarPorCategoria($normalizados, 'mobiliário');
$lista2 = filtrarPorCategoria($normalizados, 'iluminação');
$fundidos = fundirListasDeProdutos($lista1, $lista2);
echo "🔀 SPREAD — FUNDIR mobiliário + iluminação:\n";
echo "  Total fundido: " . count($fundidos) . " produtos\n";
foreach ($fundidos as $p) {
    echo "  - {$p['nome']} ({$p['categoria']})\n";
}
echo "\n";


// ── ETAPA 10: Paginação com array_chunk
echo "📄 PAGINAÇÃO (array_chunk) — 3 por página:\n";
for ($pag = 1; $pag <= 3; $pag++) {
    $resultado = paginar($disponiveis, pagina: $pag, porPagina: 3);
    $nomes = extrairNomes($resultado['resultados']);
    $lista = empty($nomes) ? '(vazio)' : implode(', ', $nomes);
    echo "  Página {$pag}: {$lista}\n";
}
echo "\n";

echo "════════════════════════════════════\n";
echo "  Pipeline concluído com sucesso.\n";
echo "════════════════════════════════════\n";