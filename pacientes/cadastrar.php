<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirPerfil(['admin','administracao','recepcao']);

$erro = '';
$nome = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');

    if (mb_strlen($nome) < 3) {
        $erro = 'O nome deve ter pelo menos 3 caracteres.';
    } elseif (mb_strlen($nome) > 150) {
        $erro = 'O nome não pode ultrapassar 150 caracteres.';
    } else {
        $db  = Database::get();
        $dup = $db->prepare('SELECT id FROM pacientes WHERE nome = :nome LIMIT 1');
        $dup->execute([':nome' => $nome]);

        if ($dup->fetch()) {
            $erro = 'Já existe um paciente cadastrado com este nome.';
        } else {
            $db->prepare('INSERT INTO pacientes (nome) VALUES (:nome)')
               ->execute([':nome' => $nome]);

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
        <p class="text-muted mb-0" style="font-size:.85rem;">Preencha o nome completo para cadastrar</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body p-4">

                <?php if ($erro): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:8px;font-size:.88rem;">
                    <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                    <?= htmlspecialchars($erro) ?>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>

                    <div class="mb-4">
                        <label for="nome" class="form-label" style="font-weight:500;font-size:.88rem;">
                            Nome completo <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            class="form-control form-control-lg"
                            placeholder="Ex.: João da Silva"
                            value="<?= htmlspecialchars($nome) ?>"
                            maxlength="150"
                            autofocus
                            required
                            style="border-radius:8px;border-color:#d1dff0;"
                        >
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Mínimo 3 caracteres</small>
                            <small class="text-muted" id="contadorNome">0/150</small>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;">
                            <i class="bi bi-person-plus me-2"></i>Cadastrar Paciente
                        </button>
                        <a href="listar.php" class="btn btn-outline-secondary" style="border-radius:8px;">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

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
