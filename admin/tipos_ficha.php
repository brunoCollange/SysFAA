<?php
// Todo PHP ANTES do header para evitar "headers already sent"
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirPerfil('admin');

$db    = Database::get();
$erro  = '';
$msgOk = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $tid  = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $cor  = trim($_POST['cor']  ?? '#0d6efd');

    if ($acao === 'criar' || $acao === 'editar') {
        if (mb_strlen($nome) < 2) {
            $erro = 'O nome deve ter pelo menos 2 caracteres.';
        } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $cor)) {
            $erro = 'Cor inválida.';
        } else {
            $dup = $db->prepare('SELECT id FROM tipos_ficha WHERE nome = :nome AND id != :id LIMIT 1');
            $dup->execute([':nome' => $nome, ':id' => $tid]);
            if ($dup->fetch()) {
                $erro = 'Já existe um tipo com este nome.';
            } else {
                if ($acao === 'criar') {
                    $db->prepare('INSERT INTO tipos_ficha (nome, cor) VALUES (:nome, :cor)')
                       ->execute([':nome' => $nome, ':cor' => $cor]);
                    Auth::registrarAuditoria(Auth::usuario()['id'], 'tipo_criado', $nome);
                    $msgOk = "Tipo \"$nome\" criado com sucesso.";
                } else {
                    $db->prepare('UPDATE tipos_ficha SET nome = :nome, cor = :cor WHERE id = :id')
                       ->execute([':nome' => $nome, ':cor' => $cor, ':id' => $tid]);
                    Auth::registrarAuditoria(Auth::usuario()['id'], 'tipo_editado', "ID $tid: $nome");
                    $msgOk = "Tipo \"$nome\" atualizado.";
                }
            }
        }
    } elseif ($acao === 'toggle' && $tid) {
        $row = $db->prepare('SELECT nome, ativo FROM tipos_ficha WHERE id = :id LIMIT 1');
        $row->execute([':id' => $tid]);
        $tipo = $row->fetch();
        if ($tipo) {
            $novo = $tipo['ativo'] ? 0 : 1;
            $db->prepare('UPDATE tipos_ficha SET ativo = :a WHERE id = :id')
               ->execute([':a' => $novo, ':id' => $tid]);
            $msgOk = $novo ? "Tipo \"{$tipo['nome']}\" ativado." : "Tipo \"{$tipo['nome']}\" desativado.";
        }
    } elseif ($acao === 'excluir' && $tid) {
        $qtd = $db->prepare('SELECT COUNT(*) FROM fichas WHERE tipo_ficha_id = :id');
        $qtd->execute([':id' => $tid]);
        if ($qtd->fetchColumn() > 0) {
            $erro = 'Não é possível excluir: existem fichas vinculadas a este tipo.';
        } else {
            $row = $db->prepare('SELECT nome FROM tipos_ficha WHERE id = :id LIMIT 1');
            $row->execute([':id' => $tid]);
            $nomeExc = $row->fetchColumn();
            $db->prepare('DELETE FROM tipos_ficha WHERE id = :id')->execute([':id' => $tid]);
            Auth::registrarAuditoria(Auth::usuario()['id'], 'tipo_excluido', $nomeExc);
            $msgOk = "Tipo \"$nomeExc\" excluído.";
        }
    }
}

$tipos = $db->query(
    'SELECT t.id, t.nome, t.cor, t.ativo,
            COUNT(f.id) AS total_fichas
     FROM tipos_ficha t
     LEFT JOIN fichas f ON f.tipo_ficha_id = t.id
     GROUP BY t.id
     ORDER BY t.nome ASC'
)->fetchAll();

$paginaTitulo = 'Tipos de Ficha';
$paginaAtiva  = 'tipos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1" style="font-family:'Sora',sans-serif;font-weight:700;">Tipos de Ficha</h4>
        <p class="text-muted mb-0" style="font-size:.88rem;"><?= count($tipos) ?> tipo(s) cadastrado(s)</p>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2"
            style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;"
            onclick="abrirModal()">
        <i class="bi bi-plus-lg"></i> Novo Tipo
    </button>
</div>

<?php if ($msgOk): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:.88rem;">
    <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($msgOk) ?>
</div>
<?php endif; ?>
<?php if ($erro): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:.88rem;">
    <i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($erro) ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.87rem;">
                <thead>
                    <tr style="background:#f8fafd;color:#7a8aaa;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3">Tipo</th>
                        <th class="border-0 py-3">Cor</th>
                        <th class="border-0 py-3 text-center">Fichas</th>
                        <th class="border-0 py-3">Status</th>
                        <th class="border-0 py-3 pe-4 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tipos as $t): ?>
                <tr style="<?= !$t['ativo'] ? 'opacity:.55;' : '' ?>">
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:14px;height:14px;border-radius:50%;background:<?= $t['cor'] ?>;flex-shrink:0;"></div>
                            <span style="font-weight:500;"><?= htmlspecialchars($t['nome']) ?></span>
                        </div>
                    </td>
                    <td>
                        <span style="font-family:monospace;font-size:.82rem;color:#7a8aaa;"><?= $t['cor'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="badge" style="background:#e8edf5;color:#1e2d45;border-radius:20px;padding:4px 10px;font-size:.8rem;">
                            <?= $t['total_fichas'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($t['ativo']): ?>
                        <span class="badge" style="background:#e9f7ef;color:#198754;border-radius:6px;padding:4px 9px;font-size:.78rem;">Ativo</span>
                        <?php else: ?>
                        <span class="badge" style="background:#f8f9fa;color:#6c757d;border-radius:6px;padding:4px 9px;font-size:.78rem;">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="pe-4 text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    style="border-radius:6px;font-size:.78rem;" title="Editar"
                                    onclick="abrirModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nome'])) ?>', '<?= $t['cor'] ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="toggle">
                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $t['ativo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                        style="border-radius:6px;font-size:.78rem;"
                                        title="<?= $t['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                    <i class="bi <?= $t['ativo'] ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                </button>
                            </form>
                            <?php if ($t['total_fichas'] == 0): ?>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Excluir tipo <?= addslashes($t['nome']) ?>?')">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        style="border-radius:6px;font-size:.78rem;" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal criar/editar -->
<div class="modal fade" id="modalTipo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:none;">
            <form method="POST">
                <input type="hidden" name="acao" id="modalAcao" value="criar">
                <input type="hidden" name="id"   id="modalId"   value="0">
                <div class="modal-body p-4">
                    <h5 id="modalTitulo" class="mb-4" style="font-family:'Sora',sans-serif;font-weight:700;">Novo Tipo de Ficha</h5>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Nome <span class="text-danger">*</span></label>
                        <input type="text" id="modalNome" name="nome" class="form-control"
                               style="border-radius:8px;border-color:#d1dff0;"
                               placeholder="Ex.: Internação" maxlength="100" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Cor identificadora</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="color" id="modalCor" name="cor" value="#0d6efd"
                                   style="width:48px;height:40px;border-radius:8px;border:1.5px solid #d1dff0;cursor:pointer;padding:2px;">
                            <span id="modalCorHex" style="font-family:monospace;font-size:.88rem;color:#7a8aaa;">#0d6efd</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-fill" style="border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary flex-fill" style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;">Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
var modalEl;
document.addEventListener('DOMContentLoaded', function() {
    modalEl = new bootstrap.Modal(document.getElementById('modalTipo'));

    document.getElementById('modalCor').addEventListener('input', function() {
        document.getElementById('modalCorHex').textContent = this.value;
    });
});

function abrirModal(id, nome, cor) {
    id   = id   || 0;
    nome = nome || '';
    cor  = cor  || '#0d6efd';

    document.getElementById('modalAcao').value          = id ? 'editar' : 'criar';
    document.getElementById('modalId').value            = id;
    document.getElementById('modalNome').value          = nome;
    document.getElementById('modalCor').value           = cor;
    document.getElementById('modalCorHex').textContent  = cor;
    document.getElementById('modalTitulo').textContent  = id ? 'Editar Tipo de Ficha' : 'Novo Tipo de Ficha';
    modalEl.show();
}
</script>
