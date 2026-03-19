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
    "SELECT p.id, p.nome, p.criado_em,
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

<!-- Cabeçalho da página -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1" style="font-family:'Sora',sans-serif;font-weight:700;">Pacientes</h4>
        <p class="text-muted mb-0" style="font-size:.88rem;">
            <?= number_format($totalRegistros, 0, ',', '.') ?> paciente<?= $totalRegistros !== 1 ? 's' : '' ?> cadastrado<?= $totalRegistros !== 1 ? 's' : '' ?>
        </p>
    </div>
    <?php if (Auth::temPermissao(['admin','medico','recepcao'])): ?>
    <a href="cadastrar.php" class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;font-size:.9rem;">
        <i class="bi bi-person-plus"></i> Novo Paciente
    </a>
    <?php endif; ?>
</div>

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

<!-- Barra de busca -->
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
    <div class="card-body p-3">
        <form method="GET" action="" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;border-color:#d1dff0;">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input
                    type="text"
                    name="q"
                    class="form-control border-start-0 ps-0"
                    placeholder="Buscar por nome..."
                    value="<?= htmlspecialchars($busca) ?>"
                    style="border-color:#d1dff0;border-radius:0 8px 8px 0;"
                    autofocus
                >
            </div>
            <button type="submit" class="btn btn-primary px-4" style="border-radius:8px;white-space:nowrap;">
                Buscar
            </button>
            <?php if ($busca): ?>
            <a href="listar.php" class="btn btn-outline-secondary px-3" style="border-radius:8px;" title="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-0">
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
                    <tr style="background:#f8fafd;color:#7a8aaa;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;">
                        <th class="border-0 ps-4 py-3">#</th>
                        <th class="border-0 py-3">Nome do Paciente</th>
                        <th class="border-0 py-3 text-center">Fichas</th>
                        <th class="border-0 py-3">Cadastrado em</th>
                        <th class="border-0 py-3 pe-4 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pacientes as $i => $p): ?>
                    <tr>
                        <td class="ps-4 text-muted" style="font-size:.8rem;">
                            <?= $offset + $i + 1 ?>
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
                        <td class="text-center">
                            <?php if ($p['total_fichas'] > 0): ?>
                            <a href="/SysFAA/fichas/listar.php?paciente_id=<?= $p['id'] ?>"
                               class="badge text-decoration-none"
                               style="background:#e8f1fb;color:#1a56a0;border-radius:20px;padding:4px 10px;font-size:.8rem;font-weight:600;">
                                <?= $p['total_fichas'] ?> ficha<?= $p['total_fichas'] > 1 ? 's' : '' ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted" style="font-size:.82rem;">
                            <?= date('d/m/Y', strtotime($p['criado_em'])) ?>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="/SysFAA/fichas/listar.php?paciente_id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   style="border-radius:6px;font-size:.78rem;"
                                   title="Ver fichas">
                                    <i class="bi bi-file-earmark-medical"></i>
                                </a>
                                <?php if (Auth::temPermissao(['admin','medico'])): ?>
                                <a href="editar.php?id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary"
                                   style="border-radius:6px;font-size:.78rem;"
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (Auth::temPermissao('admin')): ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        style="border-radius:6px;font-size:.78rem;"
                                        title="Excluir"
                                        onclick="confirmarExclusao(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nome'])) ?>')">
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
        <?php if ($totalPaginas > 1): ?>
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top" style="font-size:.84rem;">
            <span class="text-muted">
                Mostrando <?= $offset + 1 ?>–<?= min($offset + $porPagina, $totalRegistros) ?>
                de <?= $totalRegistros ?>
            </span>
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
        </div>
        <?php endif; ?>
        <?php endif; ?>
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
</script>
