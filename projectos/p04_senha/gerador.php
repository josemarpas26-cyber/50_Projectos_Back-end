<?php
declare(strict_types=1);

// ─────────────────────────────────────────────
// PORQUÊ um Enum aqui:
// Os conjuntos de caracteres são um conjunto fechado de opções.
// Um enum torna isso explícito e elimina strings mágicas no código.
// Já usaste enums backed no P01 — aqui aplicamos num contexto real.
// ─────────────────────────────────────────────
enum ConjuntoCaracteres: string
{
    case Minusculas = 'abcdefghijklmnopqrstuvwxyz';
    case Maiusculas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    case Digitos = '0123456789';
    case Especiais = '!@#$%^&*()-_=+[]{}|;:,.<>?';
}

/**
 * Gera uma senha criptograficamente segura.
 *
 * PORQUÊ não usar rand() ou mt_rand():
 * São geradores pseudo-aleatórios determinísticos — dado o seed, o output
 * é previsível. Um atacante que conheça o timestamp de criação pode
 * reproduzir a sequência. random_int() usa /dev/urandom (Linux) ou
 * CryptGenRandom (Windows) — fontes de entropia do SO, imprevisíveis.
 *
 * @param int    $comprimento  Número de caracteres (8–128)
 * @param bool   $minusculas
 * @param bool   $maiusculas
 * @param bool   $digitos
 * @param bool   $especiais
 * @param bool   $garantirTodos  Se true, garante ≥1 char de cada conjunto activo
 * @return string
 * @throws \InvalidArgumentException
 * @throws \ValueError
 */

function gerarSenha(int $comprimento = 16,
    bool $minusculas = true,
    bool $maiusculas = true,
    bool $digitos = true,
    bool $especiais = false,
    bool $garantirTodos = true,
): string {
    if ($comprimento < 8 || $comprimento > 128){
        throw new \InvalidArgumentException(
            "Comprimento deve estar entre 8 e 128. Recebido: {$comprimento}"
        );
    }
    $alfabeto = '';
    $conjuntosActivos = [];

    if ($minusculas){
        $alfabeto .= ConjuntoCaracteres::Minusculas->value;
        $conjuntosActivos[] = ConjuntoCaracteres::Minusculas->value;
    }

    if ($maiusculas){
        $alfabeto .= ConjuntoCaracteres::Maiusculas->value;
        $conjuntosActivos[] = ConjuntoCaracteres::Maiusculas->value;
    }

    if ($digitos){
        $alfabeto .= ConjuntoCaracteres::Digitos->value;
        $conjuntosActivos[] = ConjuntoCaracteres::Digitos->value;
    }

    if ($especiais){
        $alfabeto .= ConjuntoCaracteres::Especiais->value;
        $conjuntosActivos[] = ConjuntoCaracteres::Especiais->value;
    }

    if ($alfabeto === ''){
        throw new \InvalidArgumentException('Pelo menos um conjunto de caracteres deve estar activo.');
    }
 
    $tamanhoAlfabeto = strlen($alfabeto);

    $senha = [];
    for ($i = 0; $i < $comprimento; $i++) {
        $indice = random_int(0, $tamanhoAlfabeto - 1);
        $senha[] = $alfabeto[$indice];
    }
    if ($garantirTodos && count($conjuntosActivos) > 1) {
        $senha = garantirCobertura($senha, $conjuntosActivos);
    }
    return implode('', $senha);
}


/**
 * Garante que a senha contém pelo menos 1 caractere de cada conjunto activo.
 * Substitui posições aleatórias — de forma segura — sem alterar o comprimento.
 *
 * PORQUÊ não usar shuffle() para misturar:
 * shuffle() usa mt_rand() internamente — inseguro para passwords.
 * Usamos o algoritmo de Fisher-Yates com random_int() em vez.
 *
 * @param array<int, string> $senha
 * @param array<int, string> $conjuntos  Strings de caracteres disponíveis por conjunto
 * @return array<int, string>
 */
function garantirCobertura(array $senha, array $conjuntos): array
{
    $comprimento = count($senha);

    // Para cada conjunto activo, forçar pelo menos 1 caractere numa posição aleatória
    foreach ($conjuntos as $conjunto) {
        $tamanho = strlen($conjunto);
        // Posição aleatória segura na senha
        $posicao  = random_int(0, $comprimento - 1);
        // Caractere aleatório seguro deste conjunto
        $caracter = $conjunto[random_int(0, $tamanho - 1)];
        $senha[$posicao] = $caracter;
    }

    // Fisher-Yates com random_int — mistura segura
    // PORQUÊ Fisher-Yates: garante distribuição uniforme.
    // shuffle() é equivalente mas usa mt_rand() — imprevisível em contexto de segurança.
    for ($i = $comprimento - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        // Troca $senha[$i] com $senha[$j]
        [$senha[$i], $senha[$j]] = [$senha[$j], $senha[$i]];
    }

    return $senha;
}


/**
 * Gera um token hexadecimal seguro (para reset de password, API keys, etc.)
 * Diferente de uma senha: não é legível por humanos, é para máquinas.
 *
 * random_bytes(32) → 32 bytes de entropia → bin2hex → 64 caracteres hex
 *
 * @param int $bytes  Número de bytes de entropia (32 = 256 bits — padrão da indústria)
 * @return string     String hexadecimal com 2×$bytes caracteres
 */
function gerarToken(int $bytes = 32): string
{
    if ($bytes < 16 || $bytes > 64) {
        throw new \InvalidArgumentException("Bytes deve estar entre 16 e 64.");
    }

    // random_bytes devolve uma string binária de $bytes bytes
    // bin2hex converte para representação hexadecimal legível
    return bin2hex(random_bytes($bytes));
}


/**
 * Valida e extrai os parâmetros de um request HTTP para gerarSenha().
 * PORQUÊ separar isto: a lógica HTTP não deve entrar em gerarSenha().
 *
 * @param array<string, mixed> $params  Normalmente $_GET ou $_POST
 * @return array{comprimento: int, minusculas: bool, maiusculas: bool, digitos: bool, especiais: bool, garantirTodos: bool}
 */
function extrairParametros(array $params): array
{
    // Comprimento: default 16, sanitizado para int
    $comprimento = isset($params['comprimento'])
        ? (int) $params['comprimento']
        : 16;

    // Checkboxes HTML enviam "1" quando marcados, nada quando desmarcados.
    // Se o parâmetro não existir, o default é true (excepto especiais).
    $parseBool = fn(mixed $v, bool $default): bool =>
        isset($v) ? filter_var($v, FILTER_VALIDATE_BOOLEAN) : $default;

    return [
        'comprimento'   => $comprimento,
        'minusculas'    => $parseBool($params['minusculas']   ?? null, true),
        'maiusculas'    => $parseBool($params['maiusculas']   ?? null, true),
        'digitos'       => $parseBool($params['digitos']      ?? null, true),
        'especiais'     => $parseBool($params['especiais']    ?? null, false),
        'garantirTodos' => $parseBool($params['garantirTodos'] ?? null, true),
    ];
}