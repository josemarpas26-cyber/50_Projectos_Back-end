<?php
declare(strict_types=1);

function demonstrar_moderno(): void
{
    // ── NAMED ARGUMENTS (PHP 8.0+) ───────────────────────────
    // Passa argumentos pelo nome — ignora ordem.
    // Torna chamadas com muitos parâmetros muito mais legíveis.
    $arr = [3, 1, 4, 1, 5];

    // sem named args — tens de lembrar a ordem dos parâmetros:
    $slice1 = array_slice($arr, 1, 3, true);

    // com named args — auto-documentado:
    $slice2 = array_slice(
        array:        $arr,
        offset:       1,
        length:       3,
        preserve_keys: true,
    );
    print_r($slice2);

    // ── NULLSAFE OPERATOR (PHP 8.0+) ?-> ────────────────────
    // Evita o null check em cadeia.
    // Sem: if ($user !== null && $user->profile !== null) { ... }
    // Com: $user?->profile?->avatar

    // Simulação com objectos simples:
    $user = new stdClass();
    $user->profile = null;

    $avatar = $user?->profile?->avatar ?? 'default.png';
    echo "Avatar: $avatar\n";  // Avatar: default.png
    // Sem ?-> lançaria: Fatal error: null pointer dereference

    // ── READONLY PROPERTIES (PHP 8.1+) ───────────────────────
    // Propriedade que só pode ser escrita UMA vez (no construtor).
    // Ideal para Value Objects e DTOs imutáveis.
    class Produto
    {
        public function __construct(
            public readonly string $nome,
            public readonly float  $preco,
        ) {}
        // PHP 8 Constructor Promotion:
        // public readonly string $nome no construtor
        // = declara a propriedade E inicializa ao mesmo tempo
    }

    $p = new Produto(nome: 'Teclado', preco: 5000.00);
    echo "{$p->nome}: {$p->preco} Kz\n";
    // $p->nome = 'Outro'; → Error: readonly property

    // ── ENUMS (PHP 8.1+) ─────────────────────────────────────
    // Substituem as constantes mágicas dispersas.
    // Backed enum: cada case tem um valor escalar associado.
    enum StatusPedido: string
    {
        case Pendente  = 'pendente';
        case Aprovado  = 'aprovado';
        case Rejeitado = 'rejeitado';

        public function label(): string
        {
            return match($this) {
                self::Pendente  => '⏳ Pendente',
                self::Aprovado  => '✅ Aprovado',
                self::Rejeitado => '❌ Rejeitado',
            };
        }
    }

    $status = StatusPedido::Aprovado;
    echo $status->label() . "\n";        // ✅ Aprovado
    echo $status->value . "\n";         // aprovado

    // Converter de string para Enum (útil ao ler da BD):
    $doDb = StatusPedido::from('rejeitado');
    echo $doDb->label() . "\n";          // ❌ Rejeitado

    // ── FIBERS (PHP 8.1+) — intro ────────────────────────────
    // Execução cooperativa: pause/resume dentro de uma função.
    // Base para async em PHP. Não é multithreading.
    $fiber = new Fiber(function(): void {
        echo "Fiber: início\n";
        $valor = Fiber::suspend('suspenso');
        echo "Fiber: retomado com '$valor'\n";
    });

    $suspenso = $fiber->start();     // Fiber: início → pausa
    echo "Main: fiber suspendeu com '$suspenso'\n";
    $fiber->resume('olá!');          // retoma → Fiber: retomado com 'olá!'
}