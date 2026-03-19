<?php
$paginaTitulo = 'Dashboard';
$paginaAtiva  = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$db = Database::get();

// Totais
$totalFichas    = $db->query('SELECT COUNT(*) FROM fichas')->fetchColumn();
$totalPacientes = $db->query('SELECT COUNT(*) FROM pacientes')->fetchColumn();
// Considera online quem teve atividade nos últimos 15 minutos
$totalUsuarios  = $db->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1 AND ultima_atividade >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)")->fetchColumn();
$fichasHoje     = $db->query("SELECT COUNT(*) FROM fichas WHERE DATE(criado_em) = CURDATE()")->fetchColumn();

// Últimas fichas
$ultimasFichas = $db->query(
    'SELECT f.id, f.nome_original, f.criado_em, f.data_ficha,
            p.nome AS paciente, t.nome AS tipo, t.cor
     FROM fichas f
     JOIN pacientes p ON p.id = f.paciente_id
     JOIN tipos_ficha t ON t.id = f.tipo_ficha_id
     ORDER BY f.criado_em DESC
     LIMIT 8'
)->fetchAll();

// Fichas por tipo
$porTipo = $db->query(
    'SELECT t.nome, t.cor, COUNT(f.id) AS total
     FROM tipos_ficha t
     LEFT JOIN fichas f ON f.tipo_ficha_id = t.id
     WHERE t.ativo = 1
     GROUP BY t.id
     ORDER BY total DESC'
)->fetchAll();
?>

<!-- Cards de resumo -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Fichas cadastradas', $totalFichas,    'bi-file-earmark-medical', '#1a56a0', '#e8f1fb'],
        ['Pacientes',          $totalPacientes, 'bi-person-vcard',         '#198754', '#e9f7ef'],
        ['Uploads hoje',       $fichasHoje,     'bi-cloud-upload',         '#fd7e14', '#fff4e6'],
        ['Usuários online',    $totalUsuarios,  'bi-people',               '#6f42c1', '#f3eeff'],
    ];
    foreach ($cards as [$label, $valor, $icon, $cor, $bg]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div style="background:<?= $bg ?>;border-radius:12px;width:52px;height:52px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi <?= $icon ?>" style="font-size:1.5rem;color:<?= $cor ?>;"></i>
                    </div>
                    <div>
                        <div style="font-size:1.6rem;font-family:'Sora',sans-serif;font-weight:700;color:var(--texto);line-height:1.1;">
                            <?= number_format($valor, 0, ',', '.') ?>
                        </div>
                        <div style="font-size:.82rem;color:#7a8aaa;"><?= $label ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">

    <!-- Tabela: últimas fichas -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:600;">Fichas Recentes</h6>
                <a href="/SysFAA/fichas/listar.php" class="btn btn-sm btn-outline-primary" style="border-radius:7px;font-size:.8rem;">
                    Ver todas
                </a>
            </div>
            <div class="card-body px-4 pt-3">
                <?php if (empty($ultimasFichas)): ?>
                    <p class="text-muted text-center py-4" style="font-size:.9rem;">
                        <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>
                        Nenhuma ficha cadastrada ainda.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.87rem;">
                            <thead>
                                <tr style="color:#7a8aaa;border-bottom:1px solid #e8edf5;">
                                    <th class="fw-500 border-0">Paciente</th>
                                    <th class="fw-500 border-0">Tipo</th>
                                    <th class="fw-500 border-0">Data Ficha</th>
                                    <th class="fw-500 border-0">Enviado em</th>
                                    <th class="border-0"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasFichas as $f): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($f['paciente']) ?></td>
                                        <td>
                                            <span class="badge" style="background:<?= $f['cor'] ?>22;color:<?= $f['cor'] ?>;font-weight:500;font-size:.78rem;border-radius:6px;padding:4px 8px;">
                                                <?= htmlspecialchars($f['tipo']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($f['data_ficha'])) ?></td>
                                        <td><?= date('d/m H:i', strtotime($f['criado_em'])) ?></td>
                                        <td>
                                            <a href="/fichas/visualizar.php?id=<?= $f['id'] ?>"
                                                class="btn btn-sm btn-outline-secondary"
                                                style="border-radius:6px;font-size:.78rem;">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cards: fichas por tipo -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <h6 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:600;">Fichas por Tipo</h6>
            </div>
            <div class="card-body px-4 pt-3">
                <?php foreach ($porTipo as $t):
                    $pct = $totalFichas > 0 ? round($t['total'] / $totalFichas * 100) : 0;
                ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.85rem;">
                            <span><?= htmlspecialchars($t['nome']) ?></span>
                            <span style="color:#7a8aaa;"><?= $t['total'] ?></span>
                        </div>
                        <div class="progress" style="height:6px;border-radius:4px;background:#eef2f9;">
                            <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $t['cor'] ?>;border-radius:4px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($porTipo)): ?>
                    <p class="text-muted text-center py-3" style="font-size:.88rem;">Sem dados ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /row -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>