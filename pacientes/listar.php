<?php
$paginaTitulo = 'Pacientes';
$paginaAtiva  = 'pacientes';
require_once __DIR__ . '/../includes/header.php';

$db = Database::get();

// Busca e paginação (servidor — mantém a tela rápida mesmo com muitos registros)
$busca             = trim($_GET['q']          ?? '');
$filtroNascimento  = trim($_GET['nascimento'] ?? '');
$filtroMae         = trim($_GET['mae']        ?? '');
$pagina            = max(1, (int)($_GET['p']  ?? 1));
$porPagina         = 20;
$offset            = ($pagina - 1) * $porPagina;
$filtrosAtivos     = $busca !== '' || $filtroNascimento !== '' || $filtroMae !== '';

$where  = ['1=1'];
$params = [];
if ($busca !== '') {
    $where[] = 'nome LIKE :q';
    $params[':q'] = '%' . $busca . '%';
}
if ($filtroNascimento !== '') {
    $where[] = 'data_nascimento = :nascimento';
    $params[':nascimento'] = $filtroNascimento;
}
if ($filtroMae !== '') {
    $where[] = 'nome_mae LIKE :mae';
    $params[':mae'] = '%' . $filtroMae . '%';
}
$whereStr = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM pacientes WHERE $whereStr");
$total->execute($params);
$totalRegistros = (int)$total->fetchColumn();
$totalPaginas   = max(1, (int)ceil($totalRegistros / $porPagina));

$stmt = $db->prepare(
    "SELECT p.id, p.nome, p.data_nascimento, p.nome_mae, p.criado_em,
            COUNT(f.id) AS total_fichas
     FROM pacientes p
     LEFT JOIN fichas f ON f.paciente_id = p.id
     WHERE $whereStr
     GROUP BY p.id
     ORDER BY p.nome ASC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
$stmt->execute();
$pacientes = $stmt->fetchAll();

// Mensagem de feedback
$msgSucesso = $_GET['ok']   ?? '';
$msgErro    = $_GET['erro'] ?? '';

// Detecta pacientes duplicados já existentes na base (mesmo nome + data de
// nascimento + nome da mãe) — checagem independente da busca/paginação acima,
// pois o sistema já estava em produção quando essa validação foi endurecida.
$duplicados = $db->query(
    "SELECT nome, data_nascimento, nome_mae, COUNT(*) AS total
     FROM pacientes
     GROUP BY nome, data_nascimento, nome_mae
     HAVING COUNT(*) > 1"
)->fetchAll();

$chavesDuplicadas  = [];
$totalRegDuplicados = 0;
foreach ($duplicados as $d) {
    $chavesDuplicadas[$d['nome'] . '|' . $d['data_nascimento'] . '|' . $d['nome_mae']] = true;
    $totalRegDuplicados += (int)$d['total'];
}
?>

<!-- Alertas -->
<?php if ($msgSucesso): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:8px;font-size:.88rem;" role="alert">
    <i class="bi bi-check-circle-fill"></i>
    <?= htmlspecialchars($msgSucesso) ?>
</div>
<?php endif; ?>
<?php if ($msgErro): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:8px;font-size:.88rem;" role="alert">
    <i class="bi bi-exclamation-circle-fill"></i>
    <?= htmlspecialchars($msgErro) ?>
</div>
<?php endif; ?>
<?php if (!empty($duplicados)): ?>
<div class="alert alert-warning d-flex align-items-start gap-2 mb-3" style="border-radius:8px;font-size:.88rem;" role="alert">
    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
    <div>
        <strong><?= count($duplicados) ?> grupo(s) de pacientes duplicados</strong> encontrados na base
        (<?= $totalRegDuplicados ?> cadastros ao todo com o mesmo nome, data de nascimento e nome da mãe).
        Os registros afetados estão marcados com <span class="badge" style="background:#fff3cd;color:#997404;border-radius:4px;padding:3px 7px;font-size:.72rem;font-weight:600;">Duplicata</span> abaixo — revise e mantenha apenas um cadastro por paciente.
    </div>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card border-0 shadow-sm" style="border-radius:8px;overflow:hidden;">
    <div class="card-body p-3">
        <form method="GET" action="" id="formFiltrosPaciente">
        <input type="hidden" name="foco" id="campoFoco" value="<?= htmlspecialchars($_GET['foco'] ?? '') ?>">
        <div class="filtros-grid">
            <div class="campo-lg">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Nome</label>
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Nome do paciente"
                    value="<?= htmlspecialchars($busca) ?>"
                    style="background:#f4f6fb;border:none;border-radius:6px;"
                    autocomplete="off"
                >
            </div>

            <div class="campo-sm">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Data de Nascimento</label>
                <input
                    type="date"
                    name="nascimento"
                    class="form-control"
                    value="<?= htmlspecialchars($filtroNascimento) ?>"
                    style="background:#f4f6fb;border:none;border-radius:6px;"
                >
            </div>

            <div class="campo-md">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Nome da Mãe</label>
                <input
                    type="text"
                    name="mae"
                    class="form-control"
                    placeholder="Nome da mãe"
                    value="<?= htmlspecialchars($filtroMae) ?>"
                    style="background:#f4f6fb;border:none;border-radius:6px;"
                    autocomplete="off"
                >
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-3">
            <?php if ($filtrosAtivos): ?>
            <a href="listar.php" class="text-decoration-none" style="font-size:.85rem;color:#7a8aaa;">Limpar filtros</a>
            <?php else: ?>
            <span style="font-size:.85rem;color:#c3cbdb;">Limpar filtros</span>
            <?php endif; ?>

            <?php if (Auth::temPermissao(['admin','administracao','recepcao'])): ?>
            <a href="cadastrar.php" class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:6px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;white-space:nowrap;">
                <i class="bi bi-person-plus"></i> Novo Paciente
            </a>
            <?php endif; ?>
        </div>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm mt-3" style="border-radius:8px;overflow:hidden;">
    <div class="card-body p-0">

        <?php if (empty($pacientes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-person-x d-block mb-2" style="font-size:2.5rem;"></i>
            <?php if ($filtrosAtivos): ?>
            Nenhum paciente encontrado para os filtros aplicados.
            <?php else: ?>
            Nenhum paciente cadastrado ainda.<br>
            <a href="cadastrar.php" class="btn btn-primary mt-3" style="border-radius:6px;">Cadastrar primeiro paciente</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.88rem;table-layout:fixed;width:100%;">
                <thead>
                    <tr style="color:#7a8aaa;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:7%;">ID</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:28%;">Nome do Paciente</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:22%;">Nome da Mãe</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:13%;">Nascimento</th>
                        <th class="border-0 py-3 text-center" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:13%;">Fichas</th>
                        <th class="border-0 py-3 pe-4" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;width:17%;">Cadastrado em</th>
                    </tr>
                </thead>
                <tbody id="corpoTabelaPacientes">
                    <?php foreach ($pacientes as $p):
                        $chaveDup = $p['nome'] . '|' . $p['data_nascimento'] . '|' . $p['nome_mae'];
                        $ehDuplicado = isset($chavesDuplicadas[$chaveDup]);
                    ?>
                    <tr class="linha-paciente"
                        style="cursor:pointer;<?= $ehDuplicado ? 'background:#fffaf0;' : '' ?>"
                        onclick="abrirModalPaciente(this)"
                        data-id="<?= $p['id'] ?>"
                        data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>"
                        data-mae="<?= $p['nome_mae'] ? htmlspecialchars($p['nome_mae'], ENT_QUOTES) : '—' ?>"
                        data-nascimento="<?= $p['data_nascimento'] ? date('d/m/Y', strtotime($p['data_nascimento'])) : '—' ?>"
                        data-fichas="<?= (int)$p['total_fichas'] ?>"
                        data-cadastro="<?= date('d/m/Y', strtotime($p['criado_em'])) ?>">
                        <td class="ps-4" style="font-size:.8rem;color:#1a56a0;font-weight:600;">
                            <?= $p['id'] ?>
                        </td>
                        <td style="max-width:0;">
                            <div class="d-flex align-items-center gap-2" style="min-width:0;">
                                <span class="text-truncate" style="font-weight:500;color:#1e2d45;min-width:0;" title="<?= htmlspecialchars($p['nome']) ?>">
                                    <?= htmlspecialchars($p['nome']) ?>
                                </span>
                                <?php if ($ehDuplicado): ?>
                                <span class="badge" style="background:#fff3cd;color:#997404;border-radius:4px;padding:3px 7px;font-size:.7rem;font-weight:600;flex-shrink:0;" title="Existe outro paciente com o mesmo nome, data de nascimento e nome da mãe">Duplicata</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-muted text-truncate" style="font-size:.85rem;max-width:0;">
                            <?= $p['nome_mae'] ? htmlspecialchars($p['nome_mae']) : '—' ?>
                        </td>
                        <td class="text-muted" style="font-size:.85rem;">
                            <?= $p['data_nascimento'] ? date('d/m/Y', strtotime($p['data_nascimento'])) : '—' ?>
                        </td>
                        <td class="text-center">
                            <?php if ($p['total_fichas'] > 0): ?>
                            <a href="<?= BASE_URL ?>/fichas/listar.php?paciente_id=<?= $p['id'] ?>"
                               class="text-muted text-decoration-none"
                               onclick="event.stopPropagation()"
                               style="font-size:.85rem;">
                                <?= $p['total_fichas'] ?> ficha(s)
                            </a>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted pe-4" style="font-size:.82rem;">
                            <?= date('d/m/Y', strtotime($p['criado_em'])) ?>
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
<?php if (!empty($pacientes)):
    $qb = http_build_query(array_filter([
        'q'          => $busca            !== '' ? $busca            : null,
        'nascimento' => $filtroNascimento !== '' ? $filtroNascimento : null,
        'mae'        => $filtroMae        !== '' ? $filtroMae        : null,
    ]));
?>
<div class="d-flex align-items-center justify-content-between mt-2 px-1" style="font-size:.84rem;">
    <span class="text-muted">
        <?= number_format($totalRegistros, 0, ',', '.') ?> registro(s) encontrado(s)
    </span>
    <?php if ($totalPaginas > 1): ?>
    <nav>
        <ul class="pagination pagination-sm mb-0 gap-1">
            <?php for ($pg = 1; $pg <= $totalPaginas; $pg++): ?>
            <li class="page-item <?= $pg === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="?p=<?= $pg ?>&<?= $qb ?>"
                   style="border-radius:6px;<?= $pg === $pagina ? 'background:#1a56a0;border-color:#1a56a0;' : '' ?>">
                    <?= $pg ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal de detalhes do paciente -->
<div class="modal fade" id="modalPaciente" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:8px;border:none;overflow:hidden;">
            <div class="position-relative p-4" style="background:linear-gradient(135deg,#1a56a0,#123f78);color:#fff;">
                <button type="button" class="btn-close btn-close-white position-absolute" style="top:18px;right:18px;" data-bs-dismiss="modal" aria-label="Fechar"></button>
                <div>
                    <h5 id="pacienteModalNome" class="mb-1" style="font-family:'Sora',sans-serif;font-weight:700;"></h5>
                    <span class="badge" style="background:rgba(255,255,255,.18);font-weight:500;font-size:.75rem;">ID #<span id="pacienteModalId"></span></span>
                </div>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Nome da Mãe</div>
                        <div id="pacienteModalMae" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Nascimento</div>
                        <div id="pacienteModalNascimento" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Fichas cadastradas</div>
                        <div id="pacienteModalFichas" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Cadastrado em</div>
                        <div id="pacienteModalCadastro" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 p-4 pt-0 d-flex flex-column gap-2">
                <a id="pacienteModalVerFichas" href="#" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:6px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-file-earmark-medical"></i> Ver Fichas do Paciente
                </a>
                <?php if (Auth::temPermissao(['admin','administracao'])): ?>
                <a id="pacienteModalEditar" href="#" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:6px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-pencil"></i> Editar Paciente
                </a>
                <?php endif; ?>
                <?php if (Auth::temPermissao('admin')): ?>
                <button type="button" id="pacienteModalExcluir" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:6px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-trash"></i> Excluir Paciente
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:8px;border:none;overflow:hidden;">
            <div class="modal-body text-center p-5">
                <div style="width:60px;height:60px;background:#fff2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="bi bi-trash" style="font-size:1.6rem;color:#dc3545;"></i>
                </div>
                <h5 style="font-family:'Sora',sans-serif;font-weight:700;margin-bottom:8px;">Excluir paciente?</h5>
                <p class="text-muted mb-4" style="font-size:.9rem;">
                    Tem certeza que deseja excluir <strong id="nomeExcluir"></strong>?<br>
                    <span style="color:#dc3545;font-size:.82rem;">Esta ação não pode ser desfeita.</span>
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary px-4" style="border-radius:6px;" data-bs-dismiss="modal">Cancelar</button>
                    <a id="btnConfirmarExcluir" href="#" class="btn btn-danger px-4" style="border-radius:6px;">Excluir</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.filtros-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}
.filtros-grid > .campo-lg { flex: 3 1 220px; }
.filtros-grid > .campo-md { flex: 2 1 160px; }
.filtros-grid > .campo-sm { flex: 1 1 130px; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formFiltros = document.getElementById('formFiltrosPaciente');
    if (!formFiltros) return;

    const campoFoco = document.getElementById('campoFoco');

    // Recoloca o foco (e o cursor no final) no campo que estava sendo
    // digitado antes do auto-submit recarregar a página.
    if (campoFoco.value) {
        const alvo = formFiltros.querySelector('[name="' + campoFoco.value + '"]');
        if (alvo && alvo.type === 'text') {
            alvo.focus();
            const fim = alvo.value.length;
            alvo.setSelectionRange(fim, fim);
        }
    }

    let timerBusca = null;
    formFiltros.querySelectorAll('input[name="q"], input[name="mae"]').forEach(function(campo) {
        campo.addEventListener('input', function() {
            campoFoco.value = this.name;
            clearTimeout(timerBusca);
            timerBusca = setTimeout(function() {
                formFiltros.submit();
            }, 500);
        });
    });

    const dataNascimento = formFiltros.querySelector('input[name="nascimento"]');
    if (dataNascimento) {
        dataNascimento.addEventListener('change', function() {
            campoFoco.value = '';
            formFiltros.submit();
        });
    }
});

function confirmarExclusao(id, nome) {
    document.getElementById('nomeExcluir').textContent = nome;
    document.getElementById('btnConfirmarExcluir').href = 'excluir.php?id=' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}

let pacienteAtual = null;
let pacienteAcaoPendente = null;
const modalPacienteEl = document.getElementById('modalPaciente');

function abrirModalPaciente(tr) {
    const d = tr.dataset;
    pacienteAtual = { id: d.id, nome: d.nome };

    document.getElementById('pacienteModalNome').textContent = d.nome;
    document.getElementById('pacienteModalId').textContent = d.id;
    document.getElementById('pacienteModalMae').textContent = d.mae;
    document.getElementById('pacienteModalNascimento').textContent = d.nascimento;

    const totalFichas = parseInt(d.fichas, 10);
    document.getElementById('pacienteModalFichas').textContent = totalFichas > 0
        ? totalFichas + ' ficha(s)'
        : 'Nenhuma ficha';

    document.getElementById('pacienteModalCadastro').textContent = d.cadastro;
    document.getElementById('pacienteModalVerFichas').href = '<?= BASE_URL ?>/fichas/listar.php?paciente_id=' + d.id;

    const btnEditar = document.getElementById('pacienteModalEditar');
    if (btnEditar) btnEditar.href = 'editar.php?id=' + d.id;

    new bootstrap.Modal(modalPacienteEl).show();
}

document.getElementById('pacienteModalExcluir')?.addEventListener('click', function () {
    pacienteAcaoPendente = () => confirmarExclusao(pacienteAtual.id, pacienteAtual.nome);
    bootstrap.Modal.getInstance(modalPacienteEl).hide();
});

modalPacienteEl.addEventListener('hidden.bs.modal', function () {
    if (pacienteAcaoPendente) {
        const acao = pacienteAcaoPendente;
        pacienteAcaoPendente = null;
        acao();
    }
});
</script>
