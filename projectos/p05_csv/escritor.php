<?php
declare(strict_types=1);

/**
 * Escreve um array de dados como CSV para download pelo browser.
 *
 * PORQUÊ php://output:
 * Em vez de escrever para um ficheiro no disco e depois servir,
 * escrevemos directamente para o stream de output HTTP.
 * Mais eficiente — zero ficheiros temporários.
 *
 * @param array<int, array<string, mixed>> $dados
 * @param string $nomeArquivo  Nome sugerido para o ficheiro ao fazer download
 */
function enviarCsvComoDownload(array $dados, string $nomeArquivo = 'export.csv'): never
{
    if (empty($dados)) {
        http_response_code(204); // No Content
        exit;
    }

    // Headers que dizem ao browser: "isto é um ficheiro para descarregar"
    header('Content-Type: text/csv; charset=utf-8');
    // attachment = download; inline = mostrar no browser
    header("Content-Disposition: attachment; filename=\"{$nomeArquivo}\"");
    header('Cache-Control: no-cache, no-store');

    // Abrir o stream de output HTTP como se fosse um ficheiro
    $output = fopen('php://output', 'w');

    // BOM UTF-8: garante que o Excel abre correctamente com acentos
    // Sem isto, "José" pode aparecer como "JosÃ©" no Excel
    fwrite($output, "\xEF\xBB\xBF");

    // Cabeçalho: extrair as chaves do primeiro elemento
    fputcsv($output, array_keys($dados[0]));

    // Escrever cada linha
    foreach ($dados as $linha) {
        fputcsv($output, $linha);
    }

    fclose($output);
    exit;
}


/**
 * Escreve CSV para um ficheiro no disco.
 * Útil para relatórios agendados, backups, etc.
 *
 * @param array<int, array<string, mixed>> $dados
 * @param string $caminho  Path absoluto
 */
function escreverCsvParaFicheiro(array $dados, string $caminho): void
{
    // 'w' abre para escrita e TRUNCA o ficheiro se já existir
    // Para adicionar sem apagar: usar 'a' (append)
    $handle = fopen($caminho, 'w');

    if ($handle === false) {
        throw new \RuntimeException("Não foi possível criar o ficheiro: {$caminho}");
    }

    try {
        fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8
        fputcsv($handle, array_keys($dados[0]));

        foreach ($dados as $linha) {
            fputcsv($handle, $linha);
        }
    } finally {
        fclose($handle);
    }
}