<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirPerfil(['admin','administracao','recepcao']);

$erro           = '';
$nome           = '';
$dataNascimento = '';
$nomeMae        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome           = mb_strtoupper(trim($_POST['nome'] ?? ''), 'UTF-8');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $nomeMae        = mb_strtoupper(trim($_POST['nome_mae'] ?? ''), 'UTF-8');

    if (mb_strlen($nome) < 3) {
        $erro = 'O nome deve ter pelo menos 3 caracteres.';
    } elseif (mb_strlen($nome) > 150) {
        $erro = 'O nome não pode ultrapassar 150 caracteres.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNascimento) || !strtotime($dataNascimento)) {
        $erro = 'Informe uma data de nascimento válida.';
    } elseif (strtotime($dataNascimento) > time()) {
        $erro = 'A data de nascimento não pode ser no futuro.';
    } elseif (mb_strlen($nomeMae) < 3) {
        $erro = 'O nome da mãe deve ter pelo menos 3 caracteres.';
    } elseif (mb_strlen($nomeMae) > 150) {
        $erro = 'O nome da mãe não pode ultrapassar 150 caracteres.';
    } else {
        $db  = Database::get();
        $dup = $db->prepare('SELECT id FROM pacientes WHERE nome = :nome LIMIT 1');
        $dup->execute([':nome' => $nome]);

        if ($dup->fetch()) {
            $erro = 'Já existe um paciente cadastrado com este nome.';
        } else {
            $db->prepare('INSERT INTO pacientes (nome, data_nascimento, nome_mae) VALUES (:nome, :data_nascimento, :nome_mae)')
               ->execute([
                   ':nome'            => $nome,
                   ':data_nascimento' => $dataNascimento,
                   ':nome_mae'        => $nomeMae,
               ]);

            $novoId = $db->lastInsertId();
            Auth::registrarAuditoria(Auth::usuario()['id'], 'paciente_criado', "Paciente ID $novoId: $nome");

            header('Location: listar.php?ok=' . urlencode("Paciente \"$nome\" cadastrado com sucesso."));
            exit;
        }
    }
}

$paginaTitulo = 'Novo Paciente';
$paginaAtiva  = 'pacientes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="listar.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:700;">Novo Paciente</h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">Preencha os dados abaixo para cadastrar</p>
    </div>
</div>

<form method="POST" novalidate>

<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">

    <!-- Faixa de identificação -->
    <div class="d-flex align-items-center gap-3 p-4" style="background:linear-gradient(135deg,#1a56a0,#123f78);color:#fff;">
        <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
            <i class="bi bi-person-plus"></i>
        </div>
        <div>
            <div style="font-family:'Sora',sans-serif;font-weight:700;font-size:1.05rem;">Novo Paciente</div>
            <span style="font-size:.82rem;opacity:.85;">Cadastro de ficha do paciente</span>
        </div>
    </div>

    <div class="card-body p-4 p-md-5">

        <?php if ($erro): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:8px;font-size:.88rem;">
            <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
            <?= htmlspecialchars($erro) ?>
        </div>
        <?php endif; ?>

        <div class="form-secao-titulo">Dados do paciente</div>

            <div class="mb-4">
                <label for="nome" class="form-label" style="font-weight:500;font-size:.88rem;">
                    Nome completo <span class="text-danger">*</span>
                </label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;border-color:#d1dff0;">
                        <i class="bi bi-person text-muted"></i>
                    </span>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        class="form-control border-start-0 ps-0"
                        placeholder="Ex.: João da Silva"
                        value="<?= htmlspecialchars($nome) ?>"
                        maxlength="150"
                        autofocus
                        required
                        style="border-radius:0 8px 8px 0;border-color:#d1dff0;text-transform:uppercase;"
                    >
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted">Mínimo 3 caracteres</small>
                    <small class="text-muted" id="contadorNome">0/150</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6 mb-4">
                    <label for="data_nascimento" class="form-label" style="font-weight:500;font-size:.88rem;">
                        Data de nascimento <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;border-color:#d1dff0;">
                            <i class="bi bi-calendar3 text-muted"></i>
                        </span>
                        <input
                            type="date"
                            id="data_nascimento"
                            name="data_nascimento"
                            class="form-control border-start-0 ps-0"
                            value="<?= htmlspecialchars($dataNascimento) ?>"
                            max="<?= date('Y-m-d') ?>"
                            required
                            style="border-radius:0 8px 8px 0;border-color:#d1dff0;"
                        >
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <label for="nome_mae" class="form-label" style="font-weight:500;font-size:.88rem;">
                        Nome da mãe <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;border-color:#d1dff0;">
                            <i class="bi bi-gender-female text-muted"></i>
                        </span>
                        <input
                            type="text"
                            id="nome_mae"
                            name="nome_mae"
                            class="form-control border-start-0 ps-0"
                            placeholder="Ex.: Maria da Silva"
                            value="<?= htmlspecialchars($nomeMae) ?>"
                            maxlength="150"
                            required
                            style="border-radius:0 8px 8px 0;border-color:#d1dff0;text-transform:uppercase;"
                        >
                    </div>
                </div>
            </div>

    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-3">
    <a href="listar.php" class="btn btn-outline-secondary px-4" style="border-radius:8px;">Cancelar</a>
    <button type="submit" class="btn btn-success px-4" style="border-radius:8px;font-weight:600;">
        <i class="bi bi-floppy me-2"></i>Salvar
    </button>
</div>

</form>

<style>
.form-secao-titulo {
    font-family: 'Sora', sans-serif;
    font-weight: 700;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #7a8aaa;
    margin-bottom: 14px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
const inputNome    = document.getElementById('nome');
const contadorNome = document.getElementById('contadorNome');
function atualizarContador() {
    contadorNome.textContent = inputNome.value.length + '/150';
}
inputNome.addEventListener('input', atualizarContador);
atualizarContador();
</script>
