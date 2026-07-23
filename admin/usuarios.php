<?php
$paginaTitulo = 'Usuários';
$paginaAtiva  = 'usuarios';
require_once __DIR__ . '/../includes/header.php';
Auth::exigirPerfil('admin');

$db = Database::get();

$usuarios = $db->query(
    'SELECT u.id, u.nome, u.email, u.ativo, u.criado_em, u.ultimo_acesso,
            u.ultima_atividade,
            p.nome AS perfil
     FROM usuarios u
     JOIN perfis p ON p.id = u.perfil_id
     ORDER BY u.nome ASC'
)->fetchAll();

$msgOk   = $_GET['ok']   ?? '';
$msgErro = $_GET['erro'] ?? '';
?>

<?php if ($msgOk): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:.88rem;">
    <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($msgOk) ?>
</div>
<?php endif; ?>
<?php if ($msgErro): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:.88rem;">
    <i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($msgErro) ?>
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
                        id="buscaUsuario"
                        class="form-control border-0"
                        placeholder="Buscar por nome ou e-mail..."
                        style="background:#f4f6fb;border-radius:0 8px 8px 0;"
                        autocomplete="off"
                    >
                </div>
            </div>
            <a href="#" id="limparFiltrosUsuario" class="text-decoration-none" style="font-size:.85rem;color:#c3cbdb;pointer-events:none;">Limpar filtros</a>
            <a href="usuario_form.php" class="btn btn-primary d-flex align-items-center gap-2 ms-auto"
               style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;white-space:nowrap;">
                <i class="bi bi-person-plus"></i> Novo Usuário
            </a>
        </div>

        <?php if (empty($usuarios)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-person-x d-block mb-2" style="font-size:2.5rem;"></i>
            Nenhum usuário cadastrado ainda.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.87rem;">
                <thead>
                    <tr style="color:#7a8aaa;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">ID</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Usuário</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">E-mail</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Perfil</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Último acesso</th>
                        <th class="border-0 py-3 pe-4" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Status</th>
                    </tr>
                </thead>
                <tbody id="corpoTabelaUsuarios">
                <?php foreach ($usuarios as $u):
                    $corPerfil = ['admin' => '#dc3545', 'administracao' => '#1a56a0', 'recepcao' => '#198754'][$u['perfil']] ?? '#6c757d';
                    $bgPerfil  = ['admin' => '#fff2f2', 'administracao' => '#e8f1fb', 'recepcao' => '#e9f7ef'][$u['perfil']]  ?? '#f8f9fa';
                    $ehProprio = ($u['id'] == Auth::usuario()['id']);
                    $online    = !empty($u['ultima_atividade']) &&
                                 (time() - strtotime($u['ultima_atividade'])) < 900;
                ?>
                <tr class="linha-usuario"
                    style="cursor:pointer;<?= !$u['ativo'] ? 'opacity:.55;' : '' ?>"
                    onclick="abrirModalUsuario(this)"
                    data-id="<?= $u['id'] ?>"
                    data-nome="<?= htmlspecialchars($u['nome'], ENT_QUOTES) ?>"
                    data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>"
                    data-perfil="<?= htmlspecialchars(ucfirst($u['perfil']), ENT_QUOTES) ?>"
                    data-cor="<?= $corPerfil ?>"
                    data-bg="<?= $bgPerfil ?>"
                    data-ativo="<?= $u['ativo'] ? '1' : '0' ?>"
                    data-online="<?= $online ? '1' : '0' ?>"
                    data-ultimo-acesso="<?= $u['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) : '—' ?>"
                    data-criado="<?= date('d/m/Y', strtotime($u['criado_em'])) ?>"
                    data-eh-proprio="<?= $ehProprio ? '1' : '0' ?>">
                    <td class="ps-4" style="font-size:.8rem;color:#1a56a0;font-weight:600;">
                        <?= $u['id'] ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $corPerfil ?>22;display:flex;align-items:center;justify-content:center;color:<?= $corPerfil ?>;font-weight:700;font-size:.9rem;flex-shrink:0;">
                                <?= mb_strtoupper(mb_substr($u['nome'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                <span style="font-weight:500;color:#1e2d45;"><?= htmlspecialchars($u['nome']) ?></span>
                                <?php if ($online): ?>
                                <span title="Online agora" style="width:8px;height:8px;border-radius:50%;background:#198754;display:inline-block;flex-shrink:0;box-shadow:0 0 0 2px #e9f7ef;"></span>
                                <?php endif; ?>
                            </div>
                                <div style="font-size:.75rem;color:#aab4c8;">desde <?= date('d/m/Y', strtotime($u['criado_em'])) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge" style="background:<?= $bgPerfil ?>;color:<?= $corPerfil ?>;border-radius:6px;padding:4px 9px;font-weight:500;font-size:.78rem;">
                            <?= ucfirst($u['perfil']) ?>
                        </span>
                    </td>
                    <td class="text-muted" style="font-size:.82rem;">
                        <?= $u['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) : '—' ?>
                    </td>
                    <td class="pe-4">
                        <?php if ($u['ativo']): ?>
                        <span class="badge" style="background:#e9f7ef;color:#198754;border-radius:6px;padding:4px 9px;font-size:.78rem;">Ativo</span>
                        <?php else: ?>
                        <span class="badge" style="background:#f8f9fa;color:#6c757d;border-radius:6px;padding:4px 9px;font-size:.78rem;">Inativo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr id="usuarioSemResultado" style="display:none;">
                    <td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado.</td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rodapé: contagem, fora do card -->
<?php if (!empty($usuarios)): ?>
<p class="text-muted mt-2 mb-0 ps-1" style="font-size:.84rem;">
    <span id="usuarioContagem"><?= count($usuarios) ?></span> registro<span id="usuarioContagemPlural"><?= count($usuarios) !== 1 ? 's' : '' ?></span> encontrado<span id="usuarioContagemPlural2"><?= count($usuarios) !== 1 ? 's' : '' ?></span>
</p>
<?php endif; ?>

<!-- Modal de detalhes do usuário -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;">
            <div class="position-relative p-4" style="background:linear-gradient(135deg,#1a56a0,#123f78);color:#fff;">
                <button type="button" class="btn-close btn-close-white position-absolute" style="top:18px;right:18px;" data-bs-dismiss="modal" aria-label="Fechar"></button>
                <div class="d-flex align-items-center gap-3">
                    <div id="usuarioModalAvatar" style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.4rem;flex-shrink:0;background:rgba(255,255,255,.2);"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 id="usuarioModalNome" class="mb-0" style="font-family:'Sora',sans-serif;font-weight:700;"></h5>
                            <span id="usuarioModalOnline" title="Online agora" style="width:8px;height:8px;border-radius:50%;background:#3ddc84;display:none;flex-shrink:0;box-shadow:0 0 0 2px rgba(255,255,255,.4);"></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge" style="background:rgba(255,255,255,.18);font-weight:500;font-size:.75rem;">ID #<span id="usuarioModalId"></span></span>
                            <span id="usuarioModalStatus" class="badge" style="font-weight:500;font-size:.75rem;"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">E-mail</div>
                        <div id="usuarioModalEmail" style="font-weight:500;color:#1e2d45;font-size:.92rem;word-break:break-all;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Perfil</div>
                        <div id="usuarioModalPerfil" style="font-weight:500;font-size:.92rem;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Último acesso</div>
                        <div id="usuarioModalUltimoAcesso" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Cadastrado em</div>
                        <div id="usuarioModalCriado" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 p-4 pt-0 d-flex flex-column gap-2">
                <a id="usuarioModalEditar" href="#" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-pencil"></i> Editar Usuário
                </a>
                <a id="usuarioModalToggle" href="#" class="btn w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;"></a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscaUsuario  = document.getElementById('buscaUsuario');
    const semResUsuario = document.getElementById('usuarioSemResultado');
    const limparUsuario = document.getElementById('limparFiltrosUsuario');
    if (!buscaUsuario) return;

    const linhasUsuario = Array.from(document.querySelectorAll('#corpoTabelaUsuarios tr[data-nome]'));

    function filtrarUsuarios() {
        const termo = buscaUsuario.value.trim().toLowerCase();
        let visiveis = 0;

        linhasUsuario.forEach(function(tr) {
            const bate = tr.dataset.nome.toLowerCase().includes(termo) ||
                         tr.dataset.email.toLowerCase().includes(termo);
            tr.style.display = bate ? '' : 'none';
            if (bate) visiveis++;
        });

        semResUsuario.style.display = visiveis === 0 ? '' : 'none';

        document.getElementById('usuarioContagem').textContent       = visiveis;
        document.getElementById('usuarioContagemPlural').textContent  = visiveis !== 1 ? 's' : '';
        document.getElementById('usuarioContagemPlural2').textContent = visiveis !== 1 ? 's' : '';

        if (termo) {
            limparUsuario.style.color = '#7a8aaa';
            limparUsuario.style.pointerEvents = 'auto';
        } else {
            limparUsuario.style.color = '#c3cbdb';
            limparUsuario.style.pointerEvents = 'none';
        }
    }

    buscaUsuario.addEventListener('input', filtrarUsuarios);
    limparUsuario.addEventListener('click', function(e) {
        e.preventDefault();
        buscaUsuario.value = '';
        filtrarUsuarios();
        buscaUsuario.focus();
    });
});

function abrirModalUsuario(tr) {
    const d = tr.dataset;

    const avatar = document.getElementById('usuarioModalAvatar');
    avatar.textContent = d.nome.charAt(0).toUpperCase();

    document.getElementById('usuarioModalNome').textContent = d.nome;
    document.getElementById('usuarioModalId').textContent = d.id;
    document.getElementById('usuarioModalOnline').style.display = d.online === '1' ? 'inline-block' : 'none';

    const ativo = d.ativo === '1';
    const status = document.getElementById('usuarioModalStatus');
    status.textContent = ativo ? 'Ativo' : 'Inativo';
    status.style.background = ativo ? 'rgba(61,220,132,.2)' : 'rgba(255,255,255,.18)';
    status.style.color = '#fff';

    document.getElementById('usuarioModalEmail').textContent = d.email;

    const perfil = document.getElementById('usuarioModalPerfil');
    perfil.textContent = d.perfil;
    perfil.style.color = d.cor;

    document.getElementById('usuarioModalUltimoAcesso').textContent = d.ultimoAcesso;
    document.getElementById('usuarioModalCriado').textContent = d.criado;

    document.getElementById('usuarioModalEditar').href = 'usuario_form.php?id=' + d.id;

    const btnToggle = document.getElementById('usuarioModalToggle');
    if (d.ehProprio === '1') {
        btnToggle.style.display = 'none';
    } else {
        btnToggle.style.display = '';
        btnToggle.href = 'usuario_toggle.php?id=' + d.id;
        btnToggle.className = 'btn w-100 d-flex align-items-center justify-content-center gap-2 ' +
            (ativo ? 'btn-outline-warning' : 'btn-outline-success');
        btnToggle.innerHTML = ativo
            ? '<i class="bi bi-person-dash"></i> Desativar Usuário'
            : '<i class="bi bi-person-check"></i> Ativar Usuário';
    }

    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}
</script>
