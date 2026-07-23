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
    $nome = mb_strtoupper(trim($_POST['nome'] ?? ''), 'UTF-8');
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

        <!-- Barra de busca e ações -->
        <div class="d-flex align-items-center gap-3 flex-wrap p-3" style="border-bottom:1px solid #eef1f7;">
            <div style="flex:1 1 320px;max-width:420px;">
                <div class="input-group">
                    <span class="input-group-text border-0" style="background:#f4f6fb;border-radius:8px 0 0 8px;">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input
                        type="text"
                        id="buscaTipo"
                        class="form-control border-0"
                        placeholder="Buscar por nome..."
                        style="background:#f4f6fb;border-radius:0 8px 8px 0;"
                        autocomplete="off"
                    >
                </div>
            </div>
            <a href="#" id="limparFiltrosTipo" class="text-decoration-none" style="font-size:.85rem;color:#c3cbdb;pointer-events:none;">Limpar filtros</a>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 ms-auto"
                    style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;white-space:nowrap;"
                    onclick="abrirModal()">
                <i class="bi bi-plus-lg"></i> Nova Ficha
            </button>
        </div>

        <?php if (empty($tipos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-tags d-block mb-2" style="font-size:2.5rem;"></i>
            Nenhum tipo de ficha cadastrado ainda.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.87rem;">
                <thead>
                    <tr style="color:#7a8aaa;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Tipo</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Cor</th>
                        <th class="border-0 py-3 text-center" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Fichas</th>
                        <th class="border-0 py-3 pe-4" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Status</th>
                    </tr>
                </thead>
                <tbody id="corpoTabelaTipos">
                <?php foreach ($tipos as $t): ?>
                <tr style="cursor:pointer;<?= !$t['ativo'] ? 'opacity:.55;' : '' ?>"
                    onclick="abrirModalDetalhesTipo(this)"
                    data-id="<?= $t['id'] ?>"
                    data-nome="<?= htmlspecialchars($t['nome'], ENT_QUOTES) ?>"
                    data-cor="<?= $t['cor'] ?>"
                    data-fichas="<?= (int)$t['total_fichas'] ?>"
                    data-ativo="<?= $t['ativo'] ? '1' : '0' ?>">
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
                    <td class="pe-4">
                        <?php if ($t['ativo']): ?>
                        <span class="badge" style="background:#e9f7ef;color:#198754;border-radius:6px;padding:4px 9px;font-size:.78rem;">Ativo</span>
                        <?php else: ?>
                        <span class="badge" style="background:#f8f9fa;color:#6c757d;border-radius:6px;padding:4px 9px;font-size:.78rem;">Inativo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr id="tipoSemResultado" style="display:none;">
                    <td colspan="4" class="text-center text-muted py-4">Nenhum tipo encontrado.</td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rodapé: contagem, fora do card -->
<?php if (!empty($tipos)): ?>
<p class="text-muted mt-2 mb-0 ps-1" style="font-size:.84rem;">
    <span id="tipoContagem"><?= count($tipos) ?></span> registro<span id="tipoContagemPlural"><?= count($tipos) !== 1 ? 's' : '' ?></span> encontrado<span id="tipoContagemPlural2"><?= count($tipos) !== 1 ? 's' : '' ?></span>
</p>
<?php endif; ?>

<!-- Modal de detalhes do tipo -->
<div class="modal fade" id="modalDetalhesTipo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;">
            <div class="position-relative p-4" style="background:linear-gradient(135deg,#1a56a0,#123f78);color:#fff;">
                <button type="button" class="btn-close btn-close-white position-absolute" style="top:18px;right:18px;" data-bs-dismiss="modal" aria-label="Fechar"></button>
                <div class="d-flex align-items-center gap-3">
                    <div id="tipoModalAvatar" style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                    <div>
                        <h5 id="tipoModalNome" class="mb-1" style="font-family:'Sora',sans-serif;font-weight:700;"></h5>
                        <span id="tipoModalStatus" class="badge" style="font-weight:500;font-size:.75rem;"></span>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Cor identificadora</div>
                        <div class="d-flex align-items-center gap-2">
                            <span id="tipoModalCorDot" style="width:14px;height:14px;border-radius:50%;flex-shrink:0;"></span>
                            <span id="tipoModalCor" style="font-family:monospace;font-weight:500;color:#1e2d45;font-size:.92rem;"></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Fichas vinculadas</div>
                        <div id="tipoModalFichas" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 p-4 pt-0 d-flex flex-column gap-2">
                <a id="tipoModalEditar" href="#" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-pencil"></i> Editar Tipo
                </a>
                <button type="button" id="tipoModalToggle" class="btn w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;"></button>
                <button type="button" id="tipoModalExcluir" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-trash"></i> Excluir Tipo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Formulário oculto para ações de ativar/desativar/excluir -->
<form method="POST" id="formAcaoTipo" style="display:none;">
    <input type="hidden" name="acao" id="formAcaoTipoAcao">
    <input type="hidden" name="id" id="formAcaoTipoId">
</form>

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
                               style="border-radius:8px;border-color:#d1dff0;text-transform:uppercase;"
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

                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary px-4" style="border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4" style="border-radius:8px;font-weight:600;">
                        <i class="bi bi-floppy me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
var modalEl, modalDetalhesEl;
var tipoAtual = null;
var tipoAcaoPendente = null;

document.addEventListener('DOMContentLoaded', function() {
    modalEl         = new bootstrap.Modal(document.getElementById('modalTipo'));
    modalDetalhesEl = new bootstrap.Modal(document.getElementById('modalDetalhesTipo'));

    document.getElementById('modalCor').addEventListener('input', function() {
        document.getElementById('modalCorHex').textContent = this.value;
    });

    // Busca ao vivo (filtra a tabela já carregada, sem recarregar a página)
    const buscaTipo   = document.getElementById('buscaTipo');
    const linhasTipo  = Array.from(document.querySelectorAll('#corpoTabelaTipos tr[data-nome]'));
    const semResTipo  = document.getElementById('tipoSemResultado');
    const limparTipo  = document.getElementById('limparFiltrosTipo');

    function filtrarTipos() {
        const termo = buscaTipo.value.trim().toLowerCase();
        let visiveis = 0;

        linhasTipo.forEach(function(tr) {
            const bate = tr.dataset.nome.toLowerCase().includes(termo);
            tr.style.display = bate ? '' : 'none';
            if (bate) visiveis++;
        });

        semResTipo.style.display = visiveis === 0 ? '' : 'none';

        document.getElementById('tipoContagem').textContent       = visiveis;
        document.getElementById('tipoContagemPlural').textContent  = visiveis !== 1 ? 's' : '';
        document.getElementById('tipoContagemPlural2').textContent = visiveis !== 1 ? 's' : '';

        if (termo) {
            limparTipo.style.color = '#7a8aaa';
            limparTipo.style.pointerEvents = 'auto';
        } else {
            limparTipo.style.color = '#c3cbdb';
            limparTipo.style.pointerEvents = 'none';
        }
    }

    buscaTipo.addEventListener('input', filtrarTipos);
    limparTipo.addEventListener('click', function(e) {
        e.preventDefault();
        buscaTipo.value = '';
        filtrarTipos();
        buscaTipo.focus();
    });

    document.getElementById('tipoModalEditar').addEventListener('click', function(e) {
        e.preventDefault();
        tipoAcaoPendente = () => abrirModal(tipoAtual.id, tipoAtual.nome, tipoAtual.cor);
        modalDetalhesEl.hide();
    });

    document.getElementById('tipoModalToggle').addEventListener('click', function() {
        document.getElementById('formAcaoTipoAcao').value = 'toggle';
        document.getElementById('formAcaoTipoId').value   = tipoAtual.id;
        document.getElementById('formAcaoTipo').submit();
    });

    document.getElementById('tipoModalExcluir').addEventListener('click', function() {
        if (!confirm('Excluir tipo ' + tipoAtual.nome + '?')) return;
        document.getElementById('formAcaoTipoAcao').value = 'excluir';
        document.getElementById('formAcaoTipoId').value   = tipoAtual.id;
        document.getElementById('formAcaoTipo').submit();
    });

    document.getElementById('modalDetalhesTipo').addEventListener('hidden.bs.modal', function() {
        if (tipoAcaoPendente) {
            const acao = tipoAcaoPendente;
            tipoAcaoPendente = null;
            acao();
        }
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

function abrirModalDetalhesTipo(tr) {
    const d = tr.dataset;
    tipoAtual = { id: d.id, nome: d.nome, cor: d.cor };

    document.getElementById('tipoModalAvatar').style.background = d.cor;
    document.getElementById('tipoModalNome').textContent = d.nome;
    document.getElementById('tipoModalCorDot').style.background = d.cor;
    document.getElementById('tipoModalCor').textContent = d.cor;
    document.getElementById('tipoModalFichas').textContent = d.fichas + ' ficha' + (d.fichas != 1 ? 's' : '');

    const ativo  = d.ativo === '1';
    const status = document.getElementById('tipoModalStatus');
    status.textContent    = ativo ? 'Ativo' : 'Inativo';
    status.style.background = ativo ? 'rgba(61,220,132,.2)' : 'rgba(255,255,255,.18)';
    status.style.color      = '#fff';

    const btnToggle = document.getElementById('tipoModalToggle');
    btnToggle.className = 'btn w-100 d-flex align-items-center justify-content-center gap-2 ' +
        (ativo ? 'btn-outline-warning' : 'btn-outline-success');
    btnToggle.innerHTML = ativo
        ? '<i class="bi bi-toggle-off"></i> Desativar Tipo'
        : '<i class="bi bi-toggle-on"></i> Ativar Tipo';

    const btnExcluir = document.getElementById('tipoModalExcluir');
    btnExcluir.style.display = (parseInt(d.fichas, 10) === 0) ? '' : 'none';

    modalDetalhesEl.show();
}
</script>
