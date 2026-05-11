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

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1" style="font-family:'Sora',sans-serif;font-weight:700;">Usuários</h4>
        <p class="text-muted mb-0" style="font-size:.88rem;"><?= count($usuarios) ?> usuário(s) cadastrado(s)</p>
    </div>
    <a href="usuario_form.php" class="btn btn-primary d-flex align-items-center gap-2"
       style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;">
        <i class="bi bi-person-plus"></i> Novo Usuário
    </a>
</div>

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
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.87rem;">
                <thead>
                    <tr style="background:#f8fafd;color:#7a8aaa;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3">Usuário</th>
                        <th class="border-0 py-3">E-mail</th>
                        <th class="border-0 py-3">Perfil</th>
                        <th class="border-0 py-3">Último acesso</th>
                        <th class="border-0 py-3">Status</th>
                        <th class="border-0 py-3 pe-4 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u):
                    $corPerfil = ['admin' => '#dc3545', 'medico' => '#1a56a0', 'recepcao' => '#198754'][$u['perfil']] ?? '#6c757d';
                    $bgPerfil  = ['admin' => '#fff2f2', 'medico' => '#e8f1fb', 'recepcao' => '#e9f7ef'][$u['perfil']]  ?? '#f8f9fa';
                    $ehProprio = ($u['id'] == Auth::usuario()['id']);
                ?>
                <tr style="<?= !$u['ativo'] ? 'opacity:.55;' : '' ?>">
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $corPerfil ?>22;display:flex;align-items:center;justify-content:center;color:<?= $corPerfil ?>;font-weight:700;font-size:.9rem;flex-shrink:0;">
                                <?= mb_strtoupper(mb_substr($u['nome'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                <span style="font-weight:500;color:#1e2d45;"><?= htmlspecialchars($u['nome']) ?></span>
                                <?php
                                $online = !empty($u['ultima_atividade']) &&
                                          (time() - strtotime($u['ultima_atividade'])) < 900;
                                if ($online): ?>
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
                    <td>
                        <?php if ($u['ativo']): ?>
                        <span class="badge" style="background:#e9f7ef;color:#198754;border-radius:6px;padding:4px 9px;font-size:.78rem;">Ativo</span>
                        <?php else: ?>
                        <span class="badge" style="background:#f8f9fa;color:#6c757d;border-radius:6px;padding:4px 9px;font-size:.78rem;">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="pe-4 text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="usuario_form.php?id=<?= $u['id'] ?>"
                               class="btn btn-sm btn-outline-secondary"
                               style="border-radius:6px;font-size:.78rem;" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (!$ehProprio): ?>
                            <a href="usuario_toggle.php?id=<?= $u['id'] ?>"
                               class="btn btn-sm <?= $u['ativo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                               style="border-radius:6px;font-size:.78rem;"
                               title="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                <i class="bi <?= $u['ativo'] ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
                            </a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
