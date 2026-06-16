<?php
declare(strict_types=1);

// PORQUÊ funções puras aqui:
// Uma função pura não tem efeitos secundários — não escreve em ficheiros,
// não faz queries, não acede a superglobais. Recebe dados, devolve dados.
// Isto torna-as testáveis e reutilizáveis (em P45 vais escrever PHPUnit para estas).


// ─────────────────────────────────────────────
// ETAPA 1 — NORMALIZAR (array_map)
// ─────────────────────────────────────────────

/**
 * Normaliza cada produto: limpa tipos, trim em strings, converte activo para bool.
 * 
 * PORQUÊ array_map:
 * Queremos transformar TODOS os elementos, sem excepção, produzindo
 * um array do mesmo tamanho. É uma relação 1-para-1: entra 1, sai 1.
 *
 * @param array<int, array<string, mixed>> $produtos
 * @return array<int, array<string, mixed>>
 */
function normalizarProdutos(array $produtos): array
{
    return array_map(function (array $produto): array {
        return [
            'id'        => (int) $produto['id'],
            // trim() remove espaços e \t \n à volta — BD costuma devolver com padding
            'nome'      => trim((string) $produto['nome']),
            // (float) converte string "12.50" para float 12.5
            'preco'     => (float) $produto['preco'],
            'stock'     => (int) $produto['stock'],
            'categoria' => trim((string) $produto['categoria']),
            // (bool) de int: 0 → false, 1 → true
            'activo'    => (bool) $produto['activo'],
        ];
    }, $produtos);
    // Nota: array_map(callable, array) — o callable primeiro, o array depois.
    // Devolve um NOVO array — o original não é modificado. Imutabilidade.
}


// ─────────────────────────────────────────────
// ETAPA 2 — FILTRAR (array_filter)
// ─────────────────────────────────────────────

/**
 * Mantém apenas produtos activos E com stock disponível.
 *
 * PORQUÊ array_filter:
 * Queremos SELECCIONAR elementos — o array de saída pode ser menor.
 * O callable recebe cada elemento e retorna true (manter) ou false (descartar).
 *
 * ARMADILHA CLÁSSICA:
 * array_filter preserva as chaves originais! Se o array de entrada
 * tinha chaves 0,1,2,3,4 e filtramos 2 elementos, o resultado pode
 * ter chaves 0,2,4. Para reindexar: array_values() no fim.
 *
 * @param array<int, array<string, mixed>> $produtos
 * @return array<int, array<string, mixed>>
 */
function filtrarDisponiveis(array $produtos): array
{
    $filtrados = array_filter($produtos, function (array $produto): bool {
        // Produto tem de estar activo E ter stock > 0
        // Usando === para comparação estrita (decisão técnica #4 do projecto)
        return $produto['activo'] === true && $produto['stock'] > 0;
    });

    // array_values() reindexar para 0, 1, 2, ... sem "buracos"
    // Sem isto, json_encode produzia {"0": ..., "2": ..., "4": ...} em vez de array
    return array_values($filtrados);
}

/**
 * Filtra por categoria específica.
 * Demonstra arrow function (fn) — sintaxe curta do PHP 7.4+
 *
 * PORQUÊ arrow function aqui:
 * A arrow function captura automaticamente variáveis do scope pai ($categoria).
 * Numa closure normal precisaríamos de: function(...) use ($categoria) {...}
 * Arrow functions são ideais para expressões simples de uma linha.
 *
 * @param array<int, array<string, mixed>> $produtos
 * @param string $categoria
 * @return array<int, array<string, mixed>>
 */
function filtrarPorCategoria(array $produtos, string $categoria): array
{
    $filtrados = array_filter(
        $produtos,
        // fn() captura $categoria automaticamente do scope externo
        fn(array $p): bool => $p['categoria'] === $categoria
    );

    return array_values($filtrados);
}


// ─────────────────────────────────────────────
// ETAPA 3 — REDUZIR (array_reduce)
// ─────────────────────────────────────────────

/**
 * Calcula o valor total do inventário (preço × stock de cada produto).
 *
 * PORQUÊ array_reduce:
 * Queremos COLAPSAR um array num único valor — soma, contagem, max, string, outro array.
 * É mais versátil que array_sum porque o "acumulador" pode ser qualquer tipo.
 *
 * Assinatura: array_reduce(array, callable(acumulador, item): acumulador, valorInicial)
 * O callable recebe: ($carry = acumulador actual, $item = elemento actual)
 * e retorna o novo acumulador.
 *
 * @param array<int, array<string, mixed>> $produtos
 * @return float
 */
function calcularValorInventario(array $produtos): float
{
    return array_reduce(
        $produtos,
        function (float $carry, array $produto): float {
            // $carry começa em 0.0 (o valor inicial abaixo)
            // A cada iteração acumulamos: preço × stock deste produto
            return $carry + ($produto['preco'] * $produto['stock']);
        },
        0.0  // ← valor inicial do acumulador
    );
}

/**
 * Agrupa produtos por categoria usando array_reduce.
 * Demonstra que o acumulador pode ser um array — não só um escalar.
 *
 * Resultado: ['electrónica' => [...produtos...], 'mobiliário' => [...], ...]
 *
 * @param array<int, array<string, mixed>> $produtos
 * @return array<string, array<int, array<string, mixed>>>
 */
function agruparPorCategoria(array $produtos): array
{
    return array_reduce(
        $produtos,
        function (array $grupos, array $produto): array {
            $cat = $produto['categoria'];

            // Se a categoria ainda não existe no acumulador, inicializa como array vazio
            if (!isset($grupos[$cat])) {
                $grupos[$cat] = [];
            }

            // Adiciona o produto ao grupo correspondente
            $grupos[$cat][] = $produto;

            // OBRIGATÓRIO: sempre retornar o acumulador actualizado
            return $grupos;
        },
        []  // ← começa com array vazio
    );
}


// ─────────────────────────────────────────────
// ETAPA 4 — ORDENAR (usort)
// ─────────────────────────────────────────────

/**
 * Ordena produtos por preço (ascendente por defeito).
 *
 * PORQUÊ usort e não sort:
 * sort() reindexar e compara valores directamente (útil para arrays simples).
 * usort() permite DEFINIR O CRITÉRIO de comparação — essencial para arrays associativos.
 *
 * O callable de usort recebe dois elementos ($a, $b) e deve retornar:
 *   < 0 se $a vem antes de $b
 *   > 0 se $a vem depois de $b
 *   = 0 se são equivalentes
 *
 * O operador <=> (spaceship) faz exactamente isto em uma linha.
 *
 * ATENÇÃO: usort modifica o array in-place (por referência) e reindexar as chaves.
 * Por isso recebemos por valor e devolvemos uma cópia.
 *
 * @param array<int, array<string, mixed>> $produtos
 * @param string $campo     Campo pelo qual ordenar
 * @param bool   $desc      True para descendente
 * @return array<int, array<string, mixed>>
 */
function ordenarPor(array $produtos, string $campo, bool $desc = false): array
{
    // usort modifica $produtos in-place — como recebemos cópia por valor, está seguro
    usort($produtos, function (array $a, array $b) use ($campo, $desc): int {
        // Operador spaceship <=>: devolve -1, 0 ou 1
        $resultado = $a[$campo] <=> $b[$campo];

        // Se descendente, invertemos o resultado
        return $desc ? -$resultado : $resultado;
    });

    return $produtos;
}


// ─────────────────────────────────────────────
// ETAPA 5 — EXTRAIR / COMBINAR
// ─────────────────────────────────────────────

/**
 * Extrai apenas os nomes dos produtos.
 * array_column é equivalente a array_map extraindo um campo, mas mais directo.
 *
 * @param array<int, array<string, mixed>> $produtos
 * @return array<int, string>
 */
function extrairNomes(array $produtos): array
{
    // array_column(array, chave_valor, chave_indice_opcional)
    return array_column($produtos, 'nome');
}

/**
 * Cria um mapa id → nome para lookups rápidos.
 * Demonstra array_column com terceiro argumento (chave do índice).
 *
 * Resultado: [1 => 'Laptop Pro', 5 => 'Monitor 4K', ...]
 *
 * @param array<int, array<string, mixed>> $produtos
 * @return array<int, string>
 */
function criarMapaIdNome(array $produtos): array
{
    // Terceiro argumento: qual campo usar como CHAVE do array resultante
    return array_column($produtos, 'nome', 'id');
}


// ─────────────────────────────────────────────
// ETAPA 6 — SPREAD OPERATOR
// ─────────────────────────────────────────────

/**
 * Demonstra o spread operator (...) em contextos práticos.
 *
 * PORQUÊ spread operator:
 * Permite "desempacotar" um array como argumentos individuais de uma função,
 * ou fundir arrays de forma mais legível que array_merge.
 *
 * @param array<int, array<string, mixed>> ...$listasDeprodutos  ← variadic
 * @return array<int, array<string, mixed>>
 */
function fundirListasDeProdutos(array ...$listasDeProdutos): array
{
    // array_merge com spread: funde N arrays em um
    // Sem spread teríamos de passar explicitamente: array_merge($a, $b, $c)
    // Com spread podemos passar um array de arrays: array_merge(...$listasDeProdutos)
    return array_merge(...$listasDeProdutos);
}

/**
 * Paginar resultados — simula LIMIT/OFFSET do SQL em memória.
 *
 * @param array<int, array<string, mixed>> $produtos
 * @param int $pagina   Começa em 1
 * @param int $porPagina
 * @return array{pagina: int, total: int, resultados: array<int, array<string, mixed>>}
 */
function paginar(array $produtos, int $pagina, int $porPagina = 3): array
{
    // array_chunk divide o array em pedaços de $porPagina elementos
    // false = não preservar chaves (reindexar cada chunk)
    $paginas = array_chunk($produtos, $porPagina, preserve_keys: false);

    // Páginas indexadas de 1, mas array_chunk indexa de 0
    $indice = $pagina - 1;

    return [
        'pagina'     => $pagina,
        'total'      => count($produtos),
        'resultados' => $paginas[$indice] ?? [],
    ];
}