<?php
$paginaTitulo = 'Pacientes';
$paginaAtiva  = 'pacientes';
require_once __DIR__ . '/../includes/header.php';

$db = Database::get();

// Busca e paginação
$busca    = trim($_GET['q'] ?? '');
$pagina   = max(1, (int)($_GET['p'] ?? 1));
$porPagina = 20;
$offset   = ($pagina - 1) * $porPagina;

$params = [];
$where  = '';
if ($busca !== '') {
    $where    = 'WHERE nome LIKE :q';
    $params[':q'] = '%' . $busca . '%';
}

$total = $db->prepare("SELECT COUNT(*) FROM pacientes $where");
$total->execute($params);
$totalRegistros = (int)$total->fetchColumn();
$totalPaginas   = max(1, (int)ceil($totalRegistros / $porPagina));

$stmt = $db->prepare(
    "SELECT p.id, p.nome, p.data_nascimento, p.nome_mae, p.criado_em,
            COUNT(f.id) AS total_fichas
     FROM pacientes p
     LEFT JOIN fichas f ON f.paciente_id = p.id
     $where
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
?>

<!-- Alertas -->
<?php if ($msgSucesso): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:.88rem;" role="alert">
    <i class="bi bi-check-circle-fill"></i>
    <?= htmlspecialchars($msgSucesso) ?>
</div>
<?php endif; ?>
<?php if ($msgErro): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:10px;font-size:.88rem;" role="alert">
    <i class="bi bi-exclamation-circle-fill"></i>
    <?= htmlspecialchars($msgErro) ?>
</div>
<?php endif; ?>

<!-- Tabela -->
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-0">

        <!-- Barra de busca e ações -->
        <form method="GET" action="">
        <div class="d-flex align-items-center gap-3 flex-wrap p-3" style="border-bottom:1px solid #eef1f7;">
            <div class="busca-pill d-flex align-items-center" style="flex:1 1 320px;max-width:420px;background:#f4f6fb;border-radius:10px;padding:3px 3px 3px 14px;transition:box-shadow .15s;">
                <i class="bi bi-search text-muted" style="font-size:.9rem;"></i>
                <input
                    type="text"
                    name="q"
                    class="form-control border-0 shadow-none px-2"
                    placeholder="Buscar por nome..."
                    value="<?= htmlspecialchars($busca) ?>"
                    style="background:transparent;"
                >
                <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;border-radius:8px;">
                    <i class="bi bi-search" style="font-size:.85rem;"></i>
                </button>
            </div>
            <?php if ($busca): ?>
            <a href="listar.php" class="text-decoration-none" style="font-size:.85rem;color:#7a8aaa;">Limpar filtros</a>
            <?php else: ?>
            <span style="font-size:.85rem;color:#c3cbdb;">Limpar filtros</span>
            <?php endif; ?>
            <?php if (Auth::temPermissao(['admin','administracao','recepcao'])): ?>
            <a href="cadastrar.php" class="btn btn-primary d-flex align-items-center gap-2 ms-auto" style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;white-space:nowrap;">
                <i class="bi bi-person-plus"></i> Novo Paciente
            </a>
            <?php endif; ?>
        </div>
        </form>

        <?php if (empty($pacientes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-person-x d-block mb-2" style="font-size:2.5rem;"></i>
            <?php if ($busca): ?>
                Nenhum paciente encontrado para "<strong><?= htmlspecialchars($busca) ?></strong>".
            <?php else: ?>
                Nenhum paciente cadastrado ainda.<br>
                <a href="cadastrar.php" class="btn btn-primary mt-3" style="border-radius:8px;">Cadastrar primeiro paciente</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.88rem;">
                <thead>
                    <tr style="color:#7a8aaa;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">ID</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Nome do Paciente</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Nome da Mãe</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Nascimento</th>
                        <th class="border-0 py-3 text-center" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Fichas</th>
                        <th class="border-0 py-3 pe-4" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Cadastrado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pacientes as $p): ?>
                    <tr class="linha-paciente"
                        style="cursor:pointer;"
                        onclick="abrirModalPaciente(this)"
                        data-id="<?= $p['id'] ?>"
                        data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>"
                        data-mae="<?= $p['nome_mae'] ? htmlspecialchars($p['nome_mae'], ENT_QUOTES) : '—' ?>"
                        data-nascimento="<?= $p['data_nascimento'] ? date('d/m/Y', strtotime($p['data_nascimento'])) : '—' ?>"
                        data-fichas="<?= (int)$p['total_fichas'] ?>"
                        data-cadastro="<?= date('d/m/Y', strtotime($p['criado_em'])) ?>"
                        data-cor="<?= gerarCorAvatar($p['nome']) ?>">
                        <td class="ps-4" style="font-size:.8rem;color:#1a56a0;font-weight:600;">
                            <?= $p['id'] ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <!-- Avatar com inicial -->
                                <div style="
                                    width:36px;height:36px;border-radius:50%;
                                    background:<?= gerarCorAvatar($p['nome']) ?>;
                                    display:flex;align-items:center;justify-content:center;
                                    color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0;
                                ">
                                    <?= mb_strtoupper(mb_substr($p['nome'], 0, 1)) ?>
                                </div>
                                <span style="font-weight:500;color:#1e2d45;">
                                    <?= htmlspecialchars($p['nome']) ?>
                                </span>
                            </div>
                        </td>
                        <td class="text-muted" style="font-size:.85rem;">
                            <?= $p['nome_mae'] ? htmlspecialchars($p['nome_mae']) : '—' ?>
                        </td>
                        <td class="text-muted" style="font-size:.85rem;">
                            <?= $p['data_nascimento'] ? date('d/m/Y', strtotime($p['data_nascimento'])) : '—' ?>
                        </td>
                        <td class="text-center">
                            <?php if ($p['total_fichas'] > 0): ?>
                            <a href="<?= BASE_URL ?>/fichas/listar.php?paciente_id=<?= $p['id'] ?>"
                               class="badge text-decoration-none"
                               onclick="event.stopPropagation()"
                               style="background:#e8f1fb;color:#1a56a0;border-radius:20px;padding:4px 10px;font-size:.8rem;font-weight:600;">
                                <?= $p['total_fichas'] ?> ficha<?= $p['total_fichas'] > 1 ? 's' : '' ?>
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
<?php if (!empty($pacientes)): ?>
<div class="d-flex align-items-center justify-content-between mt-2 px-1" style="font-size:.84rem;">
    <span class="text-muted">
        <?= number_format($totalRegistros, 0, ',', '.') ?> registro<?= $totalRegistros !== 1 ? 's' : '' ?> encontrado<?= $totalRegistros !== 1 ? 's' : '' ?>
    </span>
    <?php if ($totalPaginas > 1): ?>
    <nav>
        <ul class="pagination pagination-sm mb-0 gap-1">
            <?php for ($pg = 1; $pg <= $totalPaginas; $pg++): ?>
            <li class="page-item <?= $pg === $pagina ? 'active' : '' ?>">
                <a class="page-link"
                   href="?p=<?= $pg ?><?= $busca ? '&q=' . urlencode($busca) : '' ?>"
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
        <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;">
            <div class="position-relative p-4" style="background:linear-gradient(135deg,#1a56a0,#123f78);color:#fff;">
                <button type="button" class="btn-close btn-close-white position-absolute" style="top:18px;right:18px;" data-bs-dismiss="modal" aria-label="Fechar"></button>
                <div class="d-flex align-items-center gap-3">
                    <div id="pacienteModalAvatar" style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.4rem;flex-shrink:0;background:rgba(255,255,255,.2);"></div>
                    <div>
                        <h5 id="pacienteModalNome" class="mb-1" style="font-family:'Sora',sans-serif;font-weight:700;"></h5>
                        <span class="badge" style="background:rgba(255,255,255,.18);font-weight:500;font-size:.75rem;">ID #<span id="pacienteModalId"></span></span>
                    </div>
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
                <a id="pacienteModalVerFichas" href="#" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-file-earmark-medical"></i> Ver Fichas do Paciente
                </a>
                <?php if (Auth::temPermissao(['admin','administracao'])): ?>
                <a id="pacienteModalEditar" href="#" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-pencil"></i> Editar Paciente
                </a>
                <?php endif; ?>
                <?php if (Auth::temPermissao('admin')): ?>
                <button type="button" id="pacienteModalExcluir" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
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
        <div class="modal-content" style="border-radius:14px;border:none;">
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
                    <button type="button" class="btn btn-outline-secondary px-4" style="border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                    <a id="btnConfirmarExcluir" href="#" class="btn btn-danger px-4" style="border-radius:8px;">Excluir</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.busca-pill:focus-within {
    background: #eef2fa;
    box-shadow: 0 0 0 3px #e8f1fb;
}
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';

// Gera cor consistente para avatar baseada no nome
function gerarCorAvatar(string $nome): string {
    $cores = ['#1a56a0','#198754','#6f42c1','#fd7e14','#0dcaf0','#d63384','#20c997','#dc3545'];
    return $cores[crc32($nome) % count($cores)];
}
?>

<script>
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

    const avatar = document.getElementById('pacienteModalAvatar');
    avatar.textContent = d.nome.charAt(0).toUpperCase();
    avatar.style.background = d.cor;

    document.getElementById('pacienteModalNome').textContent = d.nome;
    document.getElementById('pacienteModalId').textContent = d.id;
    document.getElementById('pacienteModalMae').textContent = d.mae;
    document.getElementById('pacienteModalNascimento').textContent = d.nascimento;

    const totalFichas = parseInt(d.fichas, 10);
    document.getElementById('pacienteModalFichas').textContent = totalFichas > 0
        ? totalFichas + ' ficha' + (totalFichas > 1 ? 's' : '')
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
