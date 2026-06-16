<?php
declare(strict_types=1);

function responderJson(mixed $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
    );

    exit;
}


function responderErro(string $mensagem, int $codigo = 422): never
{
    responderJson(['sucesso' => false, 'erro' => $mensagem, 'codigo'=> $codigo], $codigo);
}