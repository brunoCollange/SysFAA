<?php
$paginaTitulo = 'Auditoria';
$paginaAtiva  = 'auditoria';
require_once __DIR__ . '/../includes/header.php';
Auth::exigirPerfil('admin');

$db = Database::get();

// Filtros
$filtroUsuario = trim($_GET['usuario']  ?? '');
$filtroAcao    = trim($_GET['acao']     ?? '');
$filtroDataDe  = trim($_GET['data_de']  ?? '');
$filtroDataAte = trim($_GET['data_ate'] ?? '');
$pagina        = max(1, (int)($_GET['p'] ?? 1));
$porPagina     = 30;
$offset        = ($pagina - 1) * $porPagina;

$where  = ['1=1'];
$params = [];

if ($filtroUsuario !== '') {
    $where[] = 'u.nome LIKE :unome';
    $params[':unome'] = '%' . $filtroUsuario . '%';
}
if ($filtroAcao) {
    $where[] = 'a.acao = :acao';
    $params[':acao'] = $filtroAcao;
}
if ($filtroDataDe) {
    $where[] = 'DATE(a.criado_em) >= :dde';
    $params[':dde'] = $filtroDataDe;
}
if ($filtroDataAte) {
    $where[] = 'DATE(a.criado_em) <= :dat';
    $params[':dat'] = $filtroDataAte;
}

$whereStr = implode(' AND ', $where);

$stmtTotal = $db->prepare(
    "SELECT COUNT(*) FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE $whereStr"
);
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($total / $porPagina));

$stmt = $db->prepare(
    "SELECT a.*, u.nome AS usuario_nome
     FROM auditoria a
     LEFT JOIN usuarios u ON u.id = a.usuario_id
     WHERE $whereStr
     ORDER BY a.criado_em DESC
     LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,    PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Lista para filtro de ação
$acoes = $db->query('SELECT DISTINCT acao FROM auditoria ORDER BY acao ASC')->fetchAll(PDO::FETCH_COLUMN);

// Mapa de cores por ação
function corAcao(string $acao): array {
    $mapa = [
        'login_ok'          => ['#e9f7ef','#198754','bi-box-arrow-in-right'],
        'login_falha'       => ['#fff2f2','#dc3545','bi-exclamation-circle'],
        'logout'            => ['#f8f9fa','#6c757d','bi-box-arrow-right'],
        'upload'            => ['#e8f1fb','#1a56a0','bi-cloud-upload'],
        'visualizar'        => ['#fff8e6','#fd7e14','bi-eye'],
        'download'          => ['#f3eeff','#6f42c1','bi-download'],
        'excluir_ficha'     => ['#fff2f2','#dc3545','bi-trash'],
        'paciente_criado'   => ['#e9f7ef','#198754','bi-person-plus'],
        'paciente_editado'  => ['#e8f1fb','#1a56a0','bi-pencil'],
        'paciente_excluido' => ['#fff2f2','#dc3545','bi-person-dash'],
        'usuario_criado'    => ['#e9f7ef','#198754','bi-person-plus-fill'],
        'usuario_editado'   => ['#e8f1fb','#1a56a0','bi-pencil-fill'],
        'usuario_ativado'   => ['#e9f7ef','#198754','bi-person-check'],
        'usuario_desativado'=> ['#fff8e6','#fd7e14','bi-person-slash'],
        'tipo_criado'       => ['#e9f7ef','#198754','bi-tags'],
        'tipo_editado'      => ['#e8f1fb','#1a56a0','bi-tag'],
        'tipo_excluido'     => ['#fff2f2','#dc3545','bi-trash'],
        'senha_alterada'    => ['#e8f1fb','#1a56a0','bi-shield-lock'],
    ];
    return $mapa[$acao] ?? ['#f8f9fa','#6c757d','bi-activity'];
}
?>

<!-- Filtros -->
<div class="card border-0 shadow-sm" style="border-radius:8px;overflow:hidden;">
    <div class="card-body p-3">
        <form method="GET" id="formFiltrosAuditoria">
        <input type="hidden" name="foco" id="campoFoco" value="<?= htmlspecialchars($_GET['foco'] ?? '') ?>">
        <div class="filtros-grid">

            <div class="campo-lg">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Usuário</label>
                <input
                    type="text"
                    name="usuario"
                    class="form-control"
                    placeholder="Nome do usuário"
                    value="<?= htmlspecialchars($filtroUsuario) ?>"
                    style="background:#f4f6fb;border:none;border-radius:6px;"
                    autocomplete="off"
                >
            </div>

            <div class="campo-md">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Ação</label>
                <div class="select-dropdown">
                    <select name="acao" class="form-select select-dropdown-input" style="background-color:#f4f6fb;border:none;border-radius:6px;">
                        <option value="">Todas</option>
                        <?php foreach ($acoes as $a): ?>
                        <option value="<?= $a ?>" <?= $filtroAcao === $a ? 'selected':'' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down select-dropdown-chevron"></i>
                </div>
            </div>

            <div class="campo-sm">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Data de</label>
                <input type="date" name="data_de" class="form-control"
                       value="<?= htmlspecialchars($filtroDataDe) ?>" style="background:#f4f6fb;border:none;border-radius:6px;">
            </div>

            <div class="campo-sm">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Data até</label>
                <input type="date" name="data_ate" class="form-control"
                       value="<?= htmlspecialchars($filtroDataAte) ?>" style="background:#f4f6fb;border:none;border-radius:6px;">
            </div>

        </div>

        <div class="d-flex align-items-center justify-content-between mt-3">
            <?php if ($filtroUsuario || $filtroAcao || $filtroDataDe || $filtroDataAte): ?>
            <a href="auditoria.php" class="text-decoration-none" style="font-size:.85rem;color:#7a8aaa;">Limpar filtros</a>
            <?php else: ?>
            <span style="font-size:.85rem;color:#c3cbdb;">Limpar filtros</span>
            <?php endif; ?>
        </div>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm mt-3" style="border-radius:8px;overflow:hidden;">
    <div class="card-body p-0">

        <?php if (empty($logs)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-journal-x d-block mb-2" style="font-size:2.5rem;opacity:.4;"></i>
            <p style="font-size:.9rem;">Nenhum registro encontrado.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;table-layout:fixed;width:100%;">
                <thead>
                    <tr style="color:#7a8aaa;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:16%;">Data/Hora</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:15%;">Ação</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:15%;">Usuário</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:39%;">Descrição</th>
                        <th class="border-0 py-3 pe-4" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:15%;">IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log):
                    [$bg, $cor, $icone] = corAcao($log['acao']);
                ?>
                <tr>
                    <td class="ps-4 text-muted" style="font-size:.8rem;white-space:nowrap;">
                        <?= date('d/m/Y H:i:s', strtotime($log['criado_em'])) ?>
                    </td>
                    <td>
                        <span class="badge d-inline-flex align-items-center gap-1"
                              style="background:<?= $bg ?>;color:<?= $cor ?>;border-radius:6px;padding:5px 9px;font-size:.78rem;font-weight:500;">
                            <i class="bi <?= $icone ?>"></i>
                            <?= htmlspecialchars($log['acao']) ?>
                        </span>
                    </td>
                    <td class="text-truncate" style="font-weight:500;color:#1e2d45;max-width:0;">
                        <?= $log['usuario_nome'] ? htmlspecialchars($log['usuario_nome']) : '<span class="text-muted">Sistema</span>' ?>
                    </td>
                    <td class="text-muted text-truncate" style="max-width:0;"
                        title="<?= htmlspecialchars($log['descricao'] ?? '') ?>">
                        <?= htmlspecialchars($log['descricao'] ?? '—') ?>
                    </td>
                    <td class="pe-4 text-muted text-truncate" style="font-size:.8rem;font-family:monospace;max-width:0;">
                        <?= htmlspecialchars($log['ip'] ?? '—') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rodapé: contagem e paginação, fora do card -->
<?php if (!empty($logs)):
    $qb = http_build_query(array_filter([
        'usuario'  => $filtroUsuario !== '' ? $filtroUsuario : null,
        'acao'     => $filtroAcao    ?: null,
        'data_de'  => $filtroDataDe  ?: null,
        'data_ate' => $filtroDataAte ?: null,
    ]));
?>
<div class="d-flex align-items-center justify-content-between mt-2 px-1" style="font-size:.84rem;">
    <span class="text-muted">
        <?= number_format($total, 0, ',', '.') ?> registro(s) encontrado(s)
    </span>
    <?php if ($totalPaginas > 1): ?>
    <nav>
        <ul class="pagination pagination-sm mb-0 gap-1">
            <?php for ($pg=1;$pg<=$totalPaginas;$pg++): ?>
            <li class="page-item <?= $pg===$pagina?'active':'' ?>">
                <a class="page-link" href="?p=<?= $pg ?>&<?= $qb ?>"
                   style="border-radius:6px;<?= $pg===$pagina?'background:#1a56a0;border-color:#1a56a0;':'' ?>">
                    <?= $pg ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>

<style>
.filtros-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}
.filtros-grid > .campo-lg { flex: 3 1 220px; }
.filtros-grid > .campo-md { flex: 2 1 160px; }
.filtros-grid > .campo-sm { flex: 1 1 130px; }
.select-dropdown {
    position: relative;
}
.select-dropdown-input {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none !important;
    padding-right: 2.25rem !important;
    width: 100%;
}
.select-dropdown-chevron {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #7a8aaa;
    font-size: .75rem;
    transition: transform .15s ease;
}
.select-dropdown-input:focus + .select-dropdown-chevron {
    transform: translateY(-50%) rotate(180deg);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formFiltrosAuditoria');
    if (!form) return;

    const campoFoco = document.getElementById('campoFoco');

    // Recoloca o foco (e o cursor no final) no campo que estava sendo
    // digitado antes do auto-submit recarregar a página.
    if (campoFoco.value) {
        const alvo = form.querySelector('[name="' + campoFoco.value + '"]');
        if (alvo && alvo.type === 'text') {
            alvo.focus();
            const fim = alvo.value.length;
            alvo.setSelectionRange(fim, fim);
        }
    }

    const campoUsuario = form.querySelector('input[name="usuario"]');
    if (campoUsuario) {
        let timerBusca = null;
        campoUsuario.addEventListener('input', function() {
            campoFoco.value = this.name;
            clearTimeout(timerBusca);
            timerBusca = setTimeout(function() {
                form.submit();
            }, 500);
        });
    }

    form.querySelectorAll('select[name="acao"], input[name="data_de"], input[name="data_ate"]').forEach(function(campo) {
        campo.addEventListener('change', function() {
            campoFoco.value = '';
            form.submit();
        });
    });
});
</script>
