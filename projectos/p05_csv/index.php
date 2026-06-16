<?php
declare(strict_types=1);

require_once __DIR__ . '/validacao.php';
require_once __DIR__ . '/leitor.php';
require_once __DIR__ . '/escritor.php';

// ── Helpers de resposta inline (simples, sem ficheiro separado desta vez
//    para mostrar que podes adaptá-los ao contexto)
function json(mixed $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$rota   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ── GET /processar?ficheiro=exemplo  → processa o CSV de exemplo
if ($metodo === 'GET' && $rota === '/processar') {
    $caminho = __DIR__ . '/dados/exemplo.csv';

    if (!file_exists($caminho)) {
        json(['sucesso' => false, 'erro' => 'Ficheiro de exemplo não encontrado.'], 404);
    }

    $resultado = processarCsv($caminho);

    json(['sucesso' => true, 'dados' => $resultado]);
}

// ── GET /exportar → exporta apenas as linhas válidas do exemplo como CSV
if ($metodo === 'GET' && $rota === '/exportar') {
    $caminho  = __DIR__ . '/dados/exemplo.csv';
    $resultado = processarCsv($caminho);

    // Isto envia o CSV directamente — sem json(), sem resposta JSON
    enviarCsvComoDownload($resultado['validas'], 'clientes_validos.csv');
}

// ── POST /upload → aceita upload de CSV e processa
if ($metodo === 'POST' && $rota === '/upload') {
    if (!isset($_FILES['csv'])) {
        json(['sucesso' => false, 'erro' => 'Nenhum ficheiro enviado. Campo esperado: csv'], 400);
    }

    $ficheiro = $_FILES['csv'];

    // Verificar erros de upload (disco cheio, tamanho excedido, etc.)
    if ($ficheiro['error'] !== UPLOAD_ERR_OK) {
        json(['sucesso' => false, 'erro' => "Erro no upload: código {$ficheiro['error']}"], 400);
    }

    // Verificar tipo MIME — nunca confiar apenas na extensão
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($ficheiro['tmp_name']);

    $mimesPermitidos = ['text/csv', 'text/plain', 'application/csv'];
    if (!in_array($mime, $mimesPermitidos, strict: true)) {
        json(['sucesso' => false, 'erro' => "Tipo de ficheiro não permitido: {$mime}"], 422);
    }

    // move_uploaded_file: move do directório temporário para o nosso destino
    // NUNCA usar rename() para ficheiros uploaded — não é seguro
    $destino = __DIR__ . '/dados/upload_' . time() . '.csv';
    if (!move_uploaded_file($ficheiro['tmp_name'], $destino)) {
        json(['sucesso' => false, 'erro' => 'Falha ao guardar o ficheiro.'], 500);
    }

    $resultado = processarCsv($destino);

    // Apagar o ficheiro depois de processar (não deixar lixo no servidor)
    unlink($destino);

    json(['sucesso' => true, 'dados' => $resultado]);
}

json(['sucesso' => false, 'erro' => 'Rota não encontrada.'], 404);