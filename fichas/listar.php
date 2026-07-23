<?php
$paginaTitulo = 'Fichas';
$paginaAtiva  = 'fichas';
require_once __DIR__ . '/../includes/header.php';

$db = Database::get();

// ── Filtros ──────────────────────────────────────────────────
$filtroPaciente = (int)($_GET['paciente_id']   ?? 0);
$filtroTipo     = (int)($_GET['tipo_ficha_id'] ?? 0);
$filtroDataDe   = trim($_GET['data_de']        ?? '');
$filtroDataAte  = trim($_GET['data_ate']       ?? '');
$busca          = trim($_GET['q']              ?? '');
$pagina         = max(1, (int)($_GET['p']      ?? 1));
$porPagina      = 20;
$offset         = ($pagina - 1) * $porPagina;

// Monta WHERE dinâmico
$where  = ['1=1'];
$params = [];

if ($filtroPaciente) {
    $where[] = 'f.paciente_id = :pid';
    $params[':pid'] = $filtroPaciente;
}
if ($filtroTipo) {
    $where[] = 'f.tipo_ficha_id = :tid';
    $params[':tid'] = $filtroTipo;
}
if ($filtroDataDe) {
    $where[] = 'f.data_ficha >= :dde';
    $params[':dde'] = $filtroDataDe;
}
if ($filtroDataAte) {
    $where[] = 'f.data_ficha <= :dat';
    $params[':dat'] = $filtroDataAte;
}
if ($busca) {
    $where[] = 'p.nome LIKE :q';
    $params[':q'] = '%' . $busca . '%';
}

$whereStr = implode(' AND ', $where);

// Total
$stmtTotal = $db->prepare(
    "SELECT COUNT(*) FROM fichas f
     JOIN pacientes p ON p.id = f.paciente_id
     WHERE $whereStr"
);
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas   = max(1, (int)ceil($totalRegistros / $porPagina));

// Fichas
$stmtFichas = $db->prepare(
    "SELECT f.id, f.nome_original, f.tamanho_bytes, f.data_ficha, f.criado_em,
            p.id AS pac_id, p.nome AS paciente, p.data_nascimento, p.nome_mae,
            t.nome AS tipo, t.cor,
            u.nome AS enviado_por
     FROM fichas f
     JOIN pacientes   p ON p.id = f.paciente_id
     JOIN tipos_ficha t ON t.id = f.tipo_ficha_id
     JOIN usuarios    u ON u.id = f.usuario_id
     WHERE $whereStr
     ORDER BY f.criado_em DESC
     LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) $stmtFichas->bindValue($k, $v);
$stmtFichas->bindValue(':lim', $porPagina, PDO::PARAM_INT);
$stmtFichas->bindValue(':off', $offset,    PDO::PARAM_INT);
$stmtFichas->execute();
$fichas = $stmtFichas->fetchAll();

// Dados para os selects de filtro
$pacientes = $db->query('SELECT id, nome, data_nascimento, nome_mae FROM pacientes ORDER BY nome ASC')->fetchAll();
$tipos     = $db->query('SELECT id, nome, cor FROM tipos_ficha WHERE ativo = 1 ORDER BY nome ASC')->fetchAll();

// Paciente selecionado (para exibir nome no título)
$nomePacienteFiltro = '';
if ($filtroPaciente) {
    $sp = $db->prepare('SELECT nome FROM pacientes WHERE id = :id LIMIT 1');
    $sp->execute([':id' => $filtroPaciente]);
    $nomePacienteFiltro = $sp->fetchColumn();
}
?>

<?php if ($nomePacienteFiltro): ?>
<div class="mb-3">
    <h4 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:700;">Fichas de <?= htmlspecialchars($nomePacienteFiltro) ?></h4>
</div>
<?php endif; ?>

<!-- Tabela -->
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-0">

        <!-- Barra de filtros e ações -->
        <form method="GET" action="">
        <div class="d-flex align-items-end gap-3 flex-wrap p-3" style="border-bottom:1px solid #eef1f7;">

            <div style="flex:1 1 240px;max-width:340px;">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Paciente</label>
                <select name="paciente_id" class="form-select" style="background:#f4f6fb;border:none;border-radius:8px;">
                    <option value="">Todos</option>
                    <?php foreach ($pacientes as $p):
                        $rotulo = $p['nome'];
                        if ($p['data_nascimento']) $rotulo .= ' — Nasc: ' . date('d/m/Y', strtotime($p['data_nascimento']));
                        if ($p['nome_mae'])        $rotulo .= ' — Mãe: ' . $p['nome_mae'];
                    ?>
                    <option value="<?= $p['id'] ?>" <?= $filtroPaciente === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rotulo) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:1 1 180px;max-width:220px;">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Tipo</label>
                <select name="tipo_ficha_id" class="form-select" style="background:#f4f6fb;border:none;border-radius:8px;">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $filtroTipo === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:0 1 150px;">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Data de</label>
                <input type="date" name="data_de" class="form-control"
                       value="<?= htmlspecialchars($filtroDataDe) ?>"
                       style="background:#f4f6fb;border:none;border-radius:8px;">
            </div>

            <div style="flex:0 1 150px;">
                <label class="form-label mb-1" style="font-size:.72rem;font-weight:500;color:#7a8aaa;text-transform:uppercase;letter-spacing:.04em;">Data até</label>
                <input type="date" name="data_ate" class="form-control"
                       value="<?= htmlspecialchars($filtroDataAte) ?>"
                       style="background:#f4f6fb;border:none;border-radius:8px;">
            </div>

            <button type="submit" class="btn btn-outline-secondary d-flex align-items-center gap-2" style="border-radius:8px;">
                <i class="bi bi-funnel"></i> Filtrar
            </button>

            <?php if ($filtroPaciente || $filtroTipo || $filtroDataDe || $filtroDataAte): ?>
            <a href="listar.php" class="text-decoration-none" style="font-size:.85rem;color:#7a8aaa;">Limpar filtros</a>
            <?php else: ?>
            <span style="font-size:.85rem;color:#c3cbdb;">Limpar filtros</span>
            <?php endif; ?>

            <a href="upload.php" class="btn btn-primary d-flex align-items-center gap-2 ms-auto"
               style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;white-space:nowrap;">
                <i class="bi bi-cloud-upload"></i> Upload
            </a>
        </div>
        </form>

        <?php if (empty($fichas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-file-earmark-x d-block mb-2" style="font-size:2.5rem;opacity:.4;"></i>
            <p style="font-size:.9rem;">Nenhuma ficha encontrada com os filtros aplicados.</p>
            <a href="upload.php" class="btn btn-primary btn-sm" style="border-radius:8px;">
                <i class="bi bi-cloud-upload me-1"></i>Fazer upload
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.87rem;">
                <thead>
                    <tr style="color:#7a8aaa;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Arquivo</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Paciente</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Tipo</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Data Ficha</th>
                        <th class="border-0 py-3" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Tamanho</th>
                        <th class="border-0 py-3 pe-4" style="background-color:#f4f6fb;border-bottom:1px solid #e8edf5;">Enviado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fichas as $f): ?>
                    <tr style="cursor:pointer;"
                        onclick="abrirModalFicha(this)"
                        data-id="<?= $f['id'] ?>"
                        data-arquivo="<?= htmlspecialchars($f['nome_original'], ENT_QUOTES) ?>"
                        data-paciente="<?= htmlspecialchars($f['paciente'], ENT_QUOTES) ?>"
                        data-pac-id="<?= $f['pac_id'] ?>"
                        data-nascimento="<?= $f['data_nascimento'] ? date('d/m/Y', strtotime($f['data_nascimento'])) : '—' ?>"
                        data-mae="<?= $f['nome_mae'] ? htmlspecialchars($f['nome_mae'], ENT_QUOTES) : '—' ?>"
                        data-tipo="<?= htmlspecialchars($f['tipo'], ENT_QUOTES) ?>"
                        data-cor="<?= $f['cor'] ?>"
                        data-data-ficha="<?= date('d/m/Y', strtotime($f['data_ficha'])) ?>"
                        data-tamanho="<?= formatarTamanho($f['tamanho_bytes']) ?>"
                        data-enviado-por="<?= htmlspecialchars($f['enviado_por'], ENT_QUOTES) ?>">
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-pdf text-danger" style="font-size:1.2rem;flex-shrink:0;"></i>
                                <span style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#1e2d45;font-weight:500;"
                                      title="<?= htmlspecialchars($f['nome_original']) ?>">
                                    <?= htmlspecialchars($f['nome_original']) ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <a href="listar.php?paciente_id=<?= $f['pac_id'] ?>"
                               onclick="event.stopPropagation()"
                               style="color:#1a56a0;text-decoration:none;font-weight:500;">
                                <?= htmlspecialchars($f['paciente']) ?>
                            </a>
                            <?php if ($f['nome_mae'] || $f['data_nascimento']): ?>
                            <div class="text-muted" style="font-size:.76rem;">
                                <?php if ($f['data_nascimento']): ?>
                                    Nasc: <?= date('d/m/Y', strtotime($f['data_nascimento'])) ?>
                                <?php endif; ?>
                                <?php if ($f['nome_mae']): ?>
                                    <?= $f['data_nascimento'] ? ' · ' : '' ?>Mãe: <?= htmlspecialchars($f['nome_mae']) ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge"
                                  style="background:<?= $f['cor'] ?>22;color:<?= $f['cor'] ?>;font-weight:500;font-size:.78rem;border-radius:6px;padding:4px 8px;">
                                <?= htmlspecialchars($f['tipo']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($f['data_ficha'])) ?></td>
                        <td class="text-muted" style="font-size:.82rem;"><?= formatarTamanho($f['tamanho_bytes']) ?></td>
                        <td class="text-muted pe-4" style="font-size:.82rem;"><?= htmlspecialchars($f['enviado_por']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rodapé: contagem e paginação, fora do card -->
<?php if (!empty($fichas)):
    $queryBase = http_build_query(array_filter([
        'paciente_id'   => $filtroPaciente ?: null,
        'tipo_ficha_id' => $filtroTipo     ?: null,
        'data_de'       => $filtroDataDe   ?: null,
        'data_ate'      => $filtroDataAte  ?: null,
        'q'             => $busca          ?: null,
    ]));
?>
<div class="d-flex align-items-center justify-content-between mt-2 px-1" style="font-size:.84rem;">
    <span class="text-muted">
        <?= number_format($totalRegistros, 0, ',', '.') ?> registro<?= $totalRegistros !== 1 ? 's' : '' ?> encontrado<?= $totalRegistros !== 1 ? 's' : '' ?>
    </span>
    <?php if ($totalPaginas > 1): ?>
    <nav>
        <ul class="pagination pagination-sm mb-0 gap-1">
            <?php for ($pg = 1; $pg <= $totalPaginas; $pg++): ?>
            <li class="page-item <?= $pg === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="?p=<?= $pg ?>&<?= $queryBase ?>"
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

<!-- Modal de detalhes da ficha -->
<div class="modal fade" id="modalFicha" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;">
            <div class="position-relative p-4" style="background:linear-gradient(135deg,#1a56a0,#123f78);color:#fff;">
                <button type="button" class="btn-close btn-close-white position-absolute" style="top:18px;right:18px;" data-bs-dismiss="modal" aria-label="Fechar"></button>
                <div class="d-flex align-items-center gap-3">
                    <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>
                    <div style="min-width:0;">
                        <h5 id="fichaModalArquivo" class="mb-1 text-truncate" style="font-family:'Sora',sans-serif;font-weight:700;max-width:340px;"></h5>
                        <span class="badge d-inline-flex align-items-center gap-1" style="background:rgba(255,255,255,.18);font-weight:500;font-size:.75rem;">
                            <span id="fichaModalTipoDot" style="width:8px;height:8px;border-radius:50%;display:inline-block;"></span>
                            <span id="fichaModalTipo"></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Paciente</div>
                        <a id="fichaModalPaciente" href="#" style="font-weight:500;color:#1a56a0;text-decoration:none;font-size:.92rem;"></a>
                        <div id="fichaModalPacienteInfo" class="text-muted" style="font-size:.78rem;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Data da Ficha</div>
                        <div id="fichaModalDataFicha" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Tamanho</div>
                        <div id="fichaModalTamanho" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Enviado por</div>
                        <div id="fichaModalEnviadoPor" style="font-weight:500;color:#1e2d45;font-size:.92rem;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 p-4 pt-0 d-flex flex-column gap-2">
                <a id="fichaModalVisualizar" href="#" target="_blank" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-eye"></i> Abrir PDF
                </a>
                <a id="fichaModalDownload" href="#" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-download"></i> Baixar Ficha
                </a>
                <?php if (Auth::temPermissao('admin')): ?>
                <button type="button" id="fichaModalExcluir" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius:8px;font-weight:600;font-size:.88rem;">
                    <i class="bi bi-trash"></i> Excluir Ficha
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:none;">
            <div class="modal-body text-center p-5">
                <div style="width:60px;height:60px;background:#fff2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="bi bi-trash" style="font-size:1.6rem;color:#dc3545;"></i>
                </div>
                <h5 style="font-family:'Sora',sans-serif;font-weight:700;margin-bottom:8px;">Excluir ficha?</h5>
                <p class="text-muted mb-1" style="font-size:.9rem;">
                    Tem certeza que deseja excluir<br><strong id="nomeExcluir"></strong>?
                </p>
                <p style="color:#dc3545;font-size:.82rem;" class="mb-4">Esta ação não pode ser desfeita.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary px-4" style="border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                    <a id="btnConfirmarExcluir" href="#" class="btn btn-danger px-4" style="border-radius:8px;">Excluir</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';

function formatarTamanho(int $bytes): string {
    if ($bytes < 1024)            return $bytes . ' B';
    if ($bytes < 1024 * 1024)     return number_format($bytes / 1024, 1) . ' KB';
    return number_format($bytes / 1024 / 1024, 1) . ' MB';
}
?>
<script>
function confirmarExclusao(id, nome) {
    document.getElementById('nomeExcluir').textContent = nome;
    document.getElementById('btnConfirmarExcluir').href = 'excluir.php?id=' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}

let fichaAtual = null;
let fichaAcaoPendente = null;
const modalFichaEl = document.getElementById('modalFicha');

function abrirModalFicha(tr) {
    const d = tr.dataset;
    fichaAtual = { id: d.id, arquivo: d.arquivo };

    document.getElementById('fichaModalArquivo').textContent = d.arquivo;
    document.getElementById('fichaModalArquivo').title = d.arquivo;

    document.getElementById('fichaModalTipo').textContent = d.tipo;
    document.getElementById('fichaModalTipoDot').style.background = d.cor;

    document.getElementById('fichaModalPaciente').textContent = d.paciente;
    document.getElementById('fichaModalPaciente').href = 'listar.php?paciente_id=' + d.pacId;

    let infoPaciente = [];
    if (d.nascimento !== '—') infoPaciente.push('Nasc: ' + d.nascimento);
    if (d.mae !== '—') infoPaciente.push('Mãe: ' + d.mae);
    document.getElementById('fichaModalPacienteInfo').textContent = infoPaciente.join(' · ');

    document.getElementById('fichaModalDataFicha').textContent = d.dataFicha;
    document.getElementById('fichaModalTamanho').textContent = d.tamanho;
    document.getElementById('fichaModalEnviadoPor').textContent = d.enviadoPor;

    document.getElementById('fichaModalVisualizar').href = 'visualizar.php?id=' + d.id;
    document.getElementById('fichaModalDownload').href = 'download.php?id=' + d.id;

    new bootstrap.Modal(modalFichaEl).show();
}

document.getElementById('fichaModalExcluir')?.addEventListener('click', function () {
    fichaAcaoPendente = () => confirmarExclusao(fichaAtual.id, fichaAtual.arquivo);
    bootstrap.Modal.getInstance(modalFichaEl).hide();
});

modalFichaEl.addEventListener('hidden.bs.modal', function () {
    if (fichaAcaoPendente) {
        const acao = fichaAcaoPendente;
        fichaAcaoPendente = null;
        acao();
    }
});
</script>
