<?php
declare(strict_types=1);

/**
 * Lê um CSV e devolve um Generator que produz linhas associativas.
 *
 * PORQUÊ Generator em vez de array:
 * Um CSV de 500MB carregado com file() ou array explode em memória.
 * Um Generator produz UMA linha de cada vez — memória constante O(1),
 * independentemente do tamanho do ficheiro. Em P11 vais importar estes
 * dados linha a linha para a BD — o Generator encaixa directamente.
 *
 * @param string $caminho  Path absoluto para o ficheiro CSV
 * @param string $separador  Normalmente ',' mas pode ser ';' (Excel PT)
 * @return \Generator<int, array<string, string>>
 * @throws \RuntimeException
 */
function lerCsvComoGenerator(string $caminho, string $separador = ','): \Generator
{
    // fopen abre um "handle" (recurso) para o ficheiro
    // 'r' = read only — nunca abrir com 'w' quando só vais ler
    $handle = fopen($caminho, 'r');

    if ($handle === false) {
        throw new \RuntimeException("Não foi possível abrir o ficheiro: {$caminho}");
    }

    try {
        // Primeira linha = cabeçalho (nomes das colunas)
        // fgetcsv lê UMA linha e faz o parse automático (trata aspas, vírgulas dentro de campos, etc.)
        $cabecalho = fgetcsv($handle, length: 0, separator: $separador);

        if ($cabecalho === false || $cabecalho === null) {
            return; // ficheiro vazio — generator não produz nada
        }

        // Limpar BOM UTF-8 se presente (Excel adiciona \xEF\xBB\xBF no início)
        $cabecalho[0] = ltrim($cabecalho[0], "\xEF\xBB\xBF");

        $numeroLinha = 1;

        // Ler linha a linha até ao fim do ficheiro
        while (($linha = fgetcsv($handle, length: 0, separator: $separador)) !== false) {
            $numeroLinha++;

            // Ignorar linhas completamente vazias
            if ($linha === [null]) {
                continue;
            }

            // array_combine: cria array associativo juntando cabeçalho + valores
            // ['id','nome','email'] + ['1','Ana','ana@...'] → ['id'=>'1', 'nome'=>'Ana', ...]
            if (count($cabecalho) !== count($linha)) {
                // Linha com número errado de colunas — registar mas continuar
                yield $numeroLinha => ['_erro_formato' => 'número de colunas não coincide'];
                continue;
            }

            // yield: produz este valor e SUSPENDE a função
            // O código só avança para a próxima iteração quando o consumer pedir
            yield $numeroLinha => array_combine($cabecalho, $linha);
        }
    } finally {
        // SEMPRE fechar o handle — finally garante isso mesmo com excepções
        fclose($handle);
    }
}


/**
 * Versão simples: carrega TODO o CSV em memória como array.
 * Usar apenas para ficheiros pequenos (< 10MB).
 *
 * @param string $caminho
 * @return array{cabecalho: array<string>, linhas: array<int, array<string, string>>}
 */
function lerCsvCompleto(string $caminho, string $separador = ','): array
{
    $linhas = [];

    foreach (lerCsvComoGenerator($caminho, $separador) as $num => $linha) {
        $linhas[$num] = $linha;
    }

    // Ler cabeçalho separadamente para o incluir no retorno
    $handle = fopen($caminho, 'r');
    $cabecalho = fgetcsv($handle, length: 0, separator: $separador) ?: [];
    $cabecalho[0] = ltrim($cabecalho[0] ?? '', "\xEF\xBB\xBF");
    fclose($handle);

    return ['cabecalho' => $cabecalho, 'linhas' => $linhas];
}


/**
 * Processa o CSV: lê, valida cada linha, separa válidas de inválidas.
 *
 * @param string $caminho
 * @return array{validas: array, invalidas: array, resumo: array}
 */
function processarCsv(string $caminho): array
{
    $validas    = [];
    $invalidas  = [];

    foreach (lerCsvComoGenerator($caminho) as $numeroLinha => $linha) {
        // Linha com erro de formato (colunas erradas)
        if (isset($linha['_erro_formato'])) {
            $invalidas[] = ['linha' => $numeroLinha, 'erros' => ['formato_invalido'], 'dados' => $linha];
            continue;
        }

        $erros = validarLinha($linha);

        if (empty($erros)) {
            // Normalizar tipos ao guardar
            $validas[] = [
                'id'     => (int)   $linha['id'],
                'nome'   => trim($linha['nome']),
                'email'  => strtolower(trim($linha['email'])),
                'idade'  => (int)   $linha['idade'],
                'salario'=> (float) $linha['salario'],
            ];
        } else {
            $invalidas[] = [
                'linha'  => $numeroLinha,
                // Enum backed: ->value para obter a string do erro
                'erros'  => array_map(fn(ErroLinha $e) => $e->value, $erros),
                'dados'  => $linha,
            ];
        }
    }

    return [
        'validas'   => $validas,
        'invalidas' => $invalidas,
        'resumo'    => [
                        'total_lidas'    => count($validas) + count($invalidas),
                        'total_validas'  => count($validas),
                        'total_invalidas'=> count($invalidas),
                    ],
    ];
}