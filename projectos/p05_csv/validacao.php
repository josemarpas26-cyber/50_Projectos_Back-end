<?php
declare(strict_types=1);


enum ErroLinha: string{
    case NomeFaltando = 'nome_em_falta';
    case EmailInvalido = 'email_invalido';
    case IdadeInvalida = 'idade_invalida';
    case MenorDeIdade = 'menor_de_idade';
    case SalarioInvalido = 'salario_invalido';
}

/**
 * Valida uma linha do CSV já como array associativo.
 * Devolve lista de erros (vazia = linha válida).
 *
 * @param array<string, string> $linha
 * @return array<int, ErroLinha>
 */
function validarLinha(array $linha): array{
    $erros = [];

    if (!isset($linha['nome']) || trim($linha['nome']) === ''){
        $erros[] = ErroLinha::NomeFaltando;
    }

    if(empty($linha['email']) || filter_var(trim($linha['email']), FILTER_VALIDATE_EMAIL) === false){
        $erros[] = ErroLinha::EmailInvalido;
    }

    $idade = filter_var($linha['idade'] ?? '', FILTER_VALIDATE_INT);
    if ($idade === false){
        $erros[] = ErroLinha::IdadeInvalida;
    } elseif ($idade < 18) {
        $erros[] = ErroLinha::MenorDeIdade;
    }

    $salario = filter_var($linha['salario'] ?? '', FILTER_VALIDATE_FLOAT);
    if ($salario === false || $salario <= 0.0){
        $erros[] = ErroLinha::SalarioInvalido;
    }

    return $erros;
}