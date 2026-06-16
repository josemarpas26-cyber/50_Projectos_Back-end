<?php
declare(strict_types=1);

/**
 * Resultado de validação de um campo individual.
 * Readonly + constructor promotion — preview de OOP que vais aprofundar em P21.
 */
final class ResultadoCampo
{
    public function __construct(
        public readonly bool   $valido,
        public readonly string $erro = '',
    ) {}
}

/**
 * Resultado completo do formulário.
 */
final class ResultadoFormulario
{
    /** @var array<string, ResultadoCampo> */
    private array $campos = [];

    public function adicionarCampo(string $nome, ResultadoCampo $resultado): void
    {
        $this->campos[$nome] = $resultado;
    }

    public function valido(): bool
    {
        foreach ($this->campos as $campo) {
            if (!$campo->valido) return false;
        }
        return true;
    }

    /** @return array<string, string> Apenas os erros (campo → mensagem) */
    public function erros(): array
    {
        $erros = [];
        foreach ($this->campos as $nome => $campo) {
            if (!$campo->valido) {
                $erros[$nome] = $campo->erro;
            }
        }
        return $erros;
    }
}


/**
 * Valida o formulário sanitizado.
 * Cada campo tem as suas regras — falha independentemente dos outros.
 * O utilizador vê TODOS os erros de uma vez (não só o primeiro).
 *
 * @param array<string, string> $dados  Output de sanitizarFormulario()
 */
function validarFormulario(array $dados): ResultadoFormulario
{
    $resultado = new ResultadoFormulario();

    // ── Nome: obrigatório, 2–100 chars
    $resultado->adicionarCampo('nome', match(true) {
        $dados['nome'] === ''            => new ResultadoCampo(false, 'O nome é obrigatório.'),
        mb_strlen($dados['nome']) < 2    => new ResultadoCampo(false, 'O nome deve ter pelo menos 2 caracteres.'),
        mb_strlen($dados['nome']) > 100  => new ResultadoCampo(false, 'O nome não pode exceder 100 caracteres.'),
        default                          => new ResultadoCampo(true),
    });

    // ── Email: obrigatório + formato RFC válido
    $resultado->adicionarCampo('email', match(true) {
        $dados['email'] === ''
            => new ResultadoCampo(false, 'O email é obrigatório.'),
        filter_var($dados['email'], FILTER_VALIDATE_EMAIL) === false
            => new ResultadoCampo(false, 'Formato de email inválido.'),
        default
            => new ResultadoCampo(true),
    });

    // ── Telefone: opcional, mas se preenchido deve ser válido
    // Formato angolano: +244 seguido de 9 dígitos, ou só 9 dígitos
    if ($dados['telefone'] !== '') {
        $telLimpo = preg_replace('/[\s\-\(\)]/', '', $dados['telefone']);
        $valido = (bool) preg_match('/^(\+244)?[89]\d{8}$/', (string) $telLimpo);
        $resultado->adicionarCampo('telefone',
            $valido
                ? new ResultadoCampo(true)
                : new ResultadoCampo(false, 'Telefone inválido. Ex: +244 923 456 789')
        );
    } else {
        $resultado->adicionarCampo('telefone', new ResultadoCampo(true)); // opcional
    }

    // ── Mensagem: obrigatória, 10–2000 chars
    $resultado->adicionarCampo('mensagem', match(true) {
        $dados['mensagem'] === ''
            => new ResultadoCampo(false, 'A mensagem é obrigatória.'),
        mb_strlen($dados['mensagem']) < 10
            => new ResultadoCampo(false, 'A mensagem deve ter pelo menos 10 caracteres.'),
        mb_strlen($dados['mensagem']) > 2000
            => new ResultadoCampo(false, 'A mensagem não pode exceder 2000 caracteres.'),
        default
            => new ResultadoCampo(true),
    });

    // ── Website: opcional, mas se preenchido deve ser URL válida com https
    if ($dados['website'] !== '') {
        $urlValida = filter_var($dados['website'], FILTER_VALIDATE_URL) !== false
                     && str_starts_with($dados['website'], 'https://');
        $resultado->adicionarCampo('website',
            $urlValida
                ? new ResultadoCampo(true)
                : new ResultadoCampo(false, 'Website deve ser uma URL válida com https://')
        );
    } else {
        $resultado->adicionarCampo('website', new ResultadoCampo(true));
    }

    return $resultado;
}