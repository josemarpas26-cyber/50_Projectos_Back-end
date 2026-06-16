<?php
declare(strict_types=1);

require_once __DIR__ . '/resposta.php';
require_once __DIR__ . '/gerador.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$rota = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Rota: GET /senha → gerar senha
// Rota: GET /token → gerar token
// Qualquer outra → 404


if ($metodo !== 'GET'){
    responderErro('Método não permitido. Use GET.', 405);
}

match(true){
    $rota === '/senha' => (function () {
        $params = extrairParametros($_GET);

        try{
            $senha = gerarSenha(
                comprimento: $params['comprimento'],
                minusculas: $params['minusculas'],
                maiusculas: $params['maiusculas'],
                digitos: $params['digitos'],
                especiais: $params['especiais'],
                garantirTodos: $params['garantirTodos'],
            );

            responderJson([
                'sucesso' => true,
                'dados' => [
                    'senha' => $senha,
                    'comprimento' => strlen($senha),
                    'entropia' => calcularEntropia($params),
                ],
            ]);
            
        } catch (\InvalidArgumentException $e) {
            responderErro($e->getMessage(), 422);
        }
    })(),

    $rota === '/token' => (function () {
        $bytes = isset($_GET['bytes']) ? (int) $_GET['bytes'] : 32;

        try {
            $token = gerarToken($bytes);
            responderJson([
                'sucesso' => true,
                'dados' => [
                    'token' => $token,
                    'bits' => $bytes * 8,
                    'uso' => 'Reset de password, API key, CSRF token',
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            responderErro($e->getMessage(), 422);
        }
    })(),

    default => responderErro('Rota não eonctrada. Use /senha ou /token', 404),
};

// ── Helper: calcular entropia teórica em bits
// Entropia = log2(tamanho_alfabeto) × comprimento
// Indica a "força" da senha em termos de bits de entropia.
function calcularEntropia(array $params): string
{
    $tamanho = 0;
    if ($params['minusculas']) $tamanho += 26;
    if ($params['maiusculas']) $tamanho += 26;
    if ($params['digitos']) $tamanho += 10;
    if ($params['especiais']) $tamanho += strlen(ConjuntoCaracteres::Especiais->value);

    if ($tamanho === 0) return '0 bits';

    $bits = (int) round(log($tamanho, 2) * $params['comprimento']);
    return "{$bits} bits";
}