<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirPerfil(['admin','administracao']);

$db = Database::get();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM pacientes WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: listar.php?erro=' . urlencode('Paciente não encontrado.'));
    exit;
}

$erro = '';
$nome = $paciente['nome'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');

    if (mb_strlen($nome) < 3) {
        $erro = 'O nome deve ter pelo menos 3 caracteres.';
    } elseif (mb_strlen($nome) > 150) {
        $erro = 'O nome não pode ultrapassar 150 caracteres.';
    } else {
        $dup = $db->prepare('SELECT id FROM pacientes WHERE nome = :nome AND id != :id LIMIT 1');
        $dup->execute([':nome' => $nome, ':id' => $id]);

        if ($dup->fetch()) {
            $erro = 'Já existe outro paciente com este nome.';
        } else {
            $db->prepare('UPDATE pacientes SET nome = :nome WHERE id = :id')
               ->execute([':nome' => $nome, ':id' => $id]);

            Auth::registrarAuditoria(Auth::usuario()['id'], 'paciente_editado', "Paciente ID $id: $nome");

            header('Location: listar.php?ok=' . urlencode("Paciente \"$nome\" atualizado com sucesso."));
            exit;
        }
    }
}

$paginaTitulo = 'Editar Paciente';
$paginaAtiva  = 'pacientes';
require_once __DIR__ . '/../includes/header.php';

$totalFichas = $db->prepare('SELECT COUNT(*) FROM fichas WHERE paciente_id = :id');
$totalFichas->execute([':id' => $id]);
$qtdFichas = $totalFichas->fetchColumn();
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="listar.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:700;">Editar Paciente</h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">ID #<?= $id ?></p>
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
                            <i class="bi bi-check-lg me-2"></i>Salvar Alterações
                        </button>
                        <a href="listar.php" class="btn btn-outline-secondary" style="border-radius:8px;">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>
        </div>

        <?php if ($qtdFichas > 0): ?>
        <div class="mt-3 p-3 d-flex align-items-center gap-2"
             style="background:#e8f1fb;border-radius:10px;font-size:.85rem;color:#1a56a0;">
            <i class="bi bi-info-circle-fill flex-shrink-0"></i>
            Este paciente possui <strong><?= $qtdFichas ?> ficha<?= $qtdFichas > 1 ? 's' : '' ?></strong> vinculada<?= $qtdFichas > 1 ? 's' : '' ?>.
            <a href="/SysFAA/fichas/listar.php?paciente_id=<?= $id ?>" style="color:#1a56a0;font-weight:600;">Ver fichas →</a>
        </div>
        <?php endif; ?>
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
