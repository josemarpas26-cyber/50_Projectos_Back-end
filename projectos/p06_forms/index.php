<?php
declare(strict_types=1);

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/sanitizador.php';
require_once __DIR__ . '/validar.php';
require_once __DIR__ . '/processador.php';

csrfIniciar();

$metodo = $_SERVER['REQUEST_METHOD'];

// ── POST: processar formulário → JSON
if ($metodo === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $resultado = processarPost();

    echo json_encode(
        array_merge(['sucesso' => $resultado['sucesso']], $resultado),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
    );
    exit;
}

// ── GET: mostrar o formulário HTML com token CSRF embutido
// htmlspecialchars() no output — NUNCA colocar variáveis directamente em HTML
$token       = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
$msgSucesso  = '';

if (!empty($_SESSION['form_sucesso'])) {
    $nome = htmlspecialchars($_SESSION['form_sucesso']['nome'], ENT_QUOTES, 'UTF-8');
    $msgSucesso = "<p class='sucesso'>Mensagem recebida, {$nome}! Obrigado.</p>";
    unset($_SESSION['form_sucesso']); // one-time flash message
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>P06 — Formulário de Contacto</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        label { display: block; margin-top: 1rem; font-weight: bold; }
        input, textarea { width: 100%; padding: .5rem; margin-top: .25rem; box-sizing: border-box; }
        .erro { color: #c00; font-size: .875rem; margin-top: .25rem; }
        .sucesso { color: #080; background: #efe; padding: 1rem; border-radius: 4px; }
        button { margin-top: 1.5rem; padding: .75rem 2rem; background: #0066cc; color: white; border: none; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Formulário de Contacto</h1>
    <?= $msgSucesso ?>
    <div id="erros-globais"></div>

    <form id="form-contacto">
        <!-- Token CSRF: hidden, enviado com o POST, verificado no servidor -->
        <input type="hidden" name="csrf_token" value="<?= $token ?>">

        <label for="nome">Nome *</label>
        <input type="text" id="nome" name="nome" maxlength="100">
        <div class="erro" id="erro-nome"></div>

        <label for="email">Email *</label>
        <input type="email" id="email" name="email">
        <div class="erro" id="erro-email"></div>

        <label for="telefone">Telefone (opcional)</label>
        <input type="tel" id="telefone" name="telefone" placeholder="+244 923 456 789">
        <div class="erro" id="erro-telefone"></div>

        <label for="mensagem">Mensagem *</label>
        <textarea id="mensagem" name="mensagem" rows="5" maxlength="2000"></textarea>
        <div class="erro" id="erro-mensagem"></div>

        <label for="website">Website (opcional)</label>
        <input type="url" id="website" name="website" placeholder="https://">
        <div class="erro" id="erro-website"></div>

        <button type="submit">Enviar</button>
    </form>

    <script>
    document.getElementById('form-contacto').addEventListener('submit', async (e) => {
        e.preventDefault();

        // Limpar erros anteriores
        document.querySelectorAll('.erro').forEach(el => el.textContent = '');

        const dados = new FormData(e.target);

        const resposta = await fetch('/', { method: 'POST', body: dados });
        const json = await resposta.json();

        if (json.sucesso) {
            // PRG não é possível com fetch — recarregar a página simula o GET
            window.location.reload();
        } else if (json.erros) {
            // Mostrar erro por campo
            for (const [campo, mensagem] of Object.entries(json.erros)) {
                const el = document.getElementById(`erro-${campo}`);
                if (el) el.textContent = mensagem;
            }
        } else if (json.erro) {
            document.getElementById('erros-globais').textContent = json.erro;
        }
    });
    </script>
</body>
</html>