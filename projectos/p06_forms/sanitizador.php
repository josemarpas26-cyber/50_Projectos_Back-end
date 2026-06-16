<?php
declare(strict_types=1);

// ─────────────────────────────────────────────
// SANITIZAR ≠ VALIDAR
//
// Sanitizar: limpar o input para um formato previsível
//   → remover espaços extra, normalizar case, remover chars perigosos
//   → NÃO rejeita — modifica
//
// Validar: verificar se o valor limpo respeita as regras
//   → rejeita se não passa
//
// Ordem OBRIGATÓRIA: Sanitizar → Validar → Usar → Escapar ao fazer output
// ─────────────────────────────────────────────

/**
 * Sanitiza todos os campos de input de um formulário de contacto/registo.
 *
 * @param array<string, mixed> $input  Normalmente $_POST
 * @return array<string, string>       Sempre devolve strings limpas
 */

function sanitizarFormulario(array $input): array{
    return [
        'nome' => sanitizarTexto($input['nome'] ?? ''),
        'email' => sanitizarEmail($input['email'] ?? ''),
        'telefone' => sanitizarTelefone($input['telefone'] ?? ''),
        'mensagem' => sanitizarTexto($input['mensagem'] ?? ''),
        'website' => sanitizarURL($input['website'] ?? ''),
    ];
}

/**
 * Texto genérico: trim + colapsar espaços múltiplos + strip tags HTML.
 * FILTER_SANITIZE_SPECIAL_CHARS não existe em PHP 8.1+ — usar htmlspecialchars
 * apenas no OUTPUT, não aqui. Aqui apenas limpamos o input.
 */
function sanitizarTexto(string $valor): string{
    $limpo = strip_tags(trim($valor));
    return (string) preg_replace('/\s+/', ' ', $limpo);
}

/**
 * Email: lowercase + trim + remover chars ilegais.
 * FILTER_SANITIZE_EMAIL remove chars que não podem existir num email.
 */
function sanitizarEmail(string $valor): string{
    $limpo = strtolower(trim($valor));
    return (string) filter_var($limpo, FILTER_SANITIZE_EMAIL);
}


/**
 * Telefone: manter apenas dígitos, +, (, ), -, espaços.
 * Formato angolano: +244 9XX XXX XXX
 */
function sanitizarTelefone(string $valor): string{
    $limpo = trim($valor);
    return (string) preg_replace('/[^\d\+\(\)\-\s]/', '', $limpo);
}

/**
 * URL: trim + scheme em lowercase.
 * FILTER_SANITIZE_URL remove chars ilegais numa URL.
 */
function sanitizarURL(string $valor): string
{
    if ($valor === '') return '';
    return (string) filter_var(trim($valor), FILTER_SANITIZE_URL);
}