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
            p.id AS pac_id, p.nome AS paciente,
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
$pacientes = $db->query('SELECT id, nome FROM pacientes ORDER BY nome ASC')->fetchAll();
$tipos     = $db->query('SELECT id, nome, cor FROM tipos_ficha WHERE ativo = 1 ORDER BY nome ASC')->fetchAll();

// Paciente selecionado (para exibir nome no título)
$nomePacienteFiltro = '';
if ($filtroPaciente) {
    $sp = $db->prepare('SELECT nome FROM pacientes WHERE id = :id LIMIT 1');
    $sp->execute([':id' => $filtroPaciente]);
    $nomePacienteFiltro = $sp->fetchColumn();
}
?>

<!-- Cabeçalho -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1" style="font-family:'Sora',sans-serif;font-weight:700;">
            <?= $nomePacienteFiltro ? 'Fichas de ' . htmlspecialchars($nomePacienteFiltro) : 'Fichas' ?>
        </h4>
        <p class="text-muted mb-0" style="font-size:.88rem;">
            <?= number_format($totalRegistros, 0, ',', '.') ?> ficha<?= $totalRegistros !== 1 ? 's' : '' ?> encontrada<?= $totalRegistros !== 1 ? 's' : '' ?>
        </p>
    </div>
    <a href="upload.php" class="btn btn-primary d-flex align-items-center gap-2"
       style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;">
        <i class="bi bi-cloud-upload"></i> Upload
    </a>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
    <div class="card-body p-3">
        <form method="GET" action="" class="row g-2 align-items-end">

            <div class="col-12 col-md-3">
                <label class="form-label mb-1" style="font-size:.78rem;font-weight:500;color:#7a8aaa;">PACIENTE</label>
                <select name="paciente_id" class="form-select form-select-sm" style="border-radius:7px;border-color:#d1dff0;">
                    <option value="">Todos</option>
                    <?php foreach ($pacientes as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $filtroPaciente === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label mb-1" style="font-size:.78rem;font-weight:500;color:#7a8aaa;">TIPO</label>
                <select name="tipo_ficha_id" class="form-select form-select-sm" style="border-radius:7px;border-color:#d1dff0;">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $filtroTipo === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.78rem;font-weight:500;color:#7a8aaa;">DATA DE</label>
                <input type="date" name="data_de" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filtroDataDe) ?>"
                       style="border-radius:7px;border-color:#d1dff0;">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.78rem;font-weight:500;color:#7a8aaa;">DATA ATÉ</label>
                <input type="date" name="data_ate" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filtroDataAte) ?>"
                       style="border-radius:7px;border-color:#d1dff0;">
            </div>

            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3 flex-fill" style="border-radius:7px;">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="listar.php" class="btn btn-outline-secondary btn-sm px-3" style="border-radius:7px;" title="Limpar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-0">
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
                    <tr style="background:#f8fafd;color:#7a8aaa;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3">Arquivo</th>
                        <th class="border-0 py-3">Paciente</th>
                        <th class="border-0 py-3">Tipo</th>
                        <th class="border-0 py-3">Data Ficha</th>
                        <th class="border-0 py-3">Tamanho</th>
                        <th class="border-0 py-3">Enviado por</th>
                        <th class="border-0 py-3 pe-4 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fichas as $f): ?>
                    <tr>
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
                               style="color:#1a56a0;text-decoration:none;font-weight:500;">
                                <?= htmlspecialchars($f['paciente']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge"
                                  style="background:<?= $f['cor'] ?>22;color:<?= $f['cor'] ?>;font-weight:500;font-size:.78rem;border-radius:6px;padding:4px 8px;">
                                <?= htmlspecialchars($f['tipo']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($f['data_ficha'])) ?></td>
                        <td class="text-muted" style="font-size:.82rem;"><?= formatarTamanho($f['tamanho_bytes']) ?></td>
                        <td class="text-muted" style="font-size:.82rem;"><?= htmlspecialchars($f['enviado_por']) ?></td>
                        <td class="pe-4 text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <!-- Abrir PDF em nova aba -->
                                <a href="visualizar.php?id=<?= $f['id'] ?>" target="_blank"
                                   class="btn btn-sm btn-outline-primary"
                                   style="border-radius:6px;font-size:.78rem;" title="Abrir PDF">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <!-- Download -->
                                <a href="download.php?id=<?= $f['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary"
                                   style="border-radius:6px;font-size:.78rem;" title="Baixar">
                                    <i class="bi bi-download"></i>
                                </a>
                                <!-- Excluir (só admin) -->
                                <?php if (Auth::temPermissao('admin')): ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        style="border-radius:6px;font-size:.78rem;" title="Excluir"
                                        onclick="confirmarExclusao(<?= $f['id'] ?>, '<?= htmlspecialchars(addslashes($f['nome_original'])) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($totalPaginas > 1):
            $queryBase = http_build_query(array_filter([
                'paciente_id'   => $filtroPaciente ?: null,
                'tipo_ficha_id' => $filtroTipo     ?: null,
                'data_de'       => $filtroDataDe   ?: null,
                'data_ate'      => $filtroDataAte  ?: null,
                'q'             => $busca          ?: null,
            ]));
        ?>
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top" style="font-size:.84rem;">
            <span class="text-muted">
                Mostrando <?= $offset + 1 ?>–<?= min($offset + $porPagina, $totalRegistros) ?> de <?= $totalRegistros ?>
            </span>
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
        </div>
        <?php endif; ?>
        <?php endif; ?>
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
</script>
