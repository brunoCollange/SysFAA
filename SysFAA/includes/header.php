<?php
// ============================================================
//  SysFAA — Header / Navbar (inclua no topo de cada página)
//  Variáveis esperadas antes do include:
//    $paginaTitulo  — título da aba/browser
//    $paginaAtiva   — slug da página para marcar o menu
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/Auth.php';
Auth::exigirLogin('/SysFAA/auth/login.php');

$usuario = Auth::usuario();
$paginaTitulo = $paginaTitulo ?? 'SysFAA';
$paginaAtiva  = $paginaAtiva  ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($paginaTitulo) ?> — SysFAA</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --azul:      #1a56a0;
            --azul-mid:  #2d6fc4;
            --cinza-bg:  #f4f6fb;
            --texto:     #1e2d45;
            --borda:     #d1dff0;
            --sidebar-w: 240px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--cinza-bg);
            color: var(--texto);
        }

        /* ---- Sidebar ---- */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: linear-gradient(175deg, #1a3a6a 0%, #1a56a0 100%);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 5px 5px;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-logo span {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
            color: #fff;
        }

        .sidebar-menu {
            list-style: none;
            padding: 16px 12px;
            margin: 0;
            flex: 1;
        }

        .sidebar-menu .menu-section {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: rgba(255,255,255,.4);
            padding: 14px 8px 6px;
            margin-top: 4px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: .88rem;
            color: rgba(255,255,255,.75);
            transition: background .15s, color .15s;
            margin-bottom: 2px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.ativo {
            background: rgba(255,255,255,.14);
            color: #fff;
        }

        .sidebar-menu a.ativo { font-weight: 600; }
        .sidebar-menu a i { font-size: 1rem; flex-shrink: 0; }

        .sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid rgba(255,255,255,.1);
            font-size: .82rem;
            color: rgba(255,255,255,.55);
        }

        .sidebar-footer strong {
            color: rgba(255,255,255,.85);
            display: block;
        }

        /* ---- Conteúdo principal ---- */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--borda);
            padding: 12px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar h6 {
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            margin: 0;
            color: var(--texto);
        }

        .page-body {
            padding: 28px;
            flex: 1;
        }

        /* ---- Botão hamburguer (só aparece no mobile) ---- */
        .btn-hamburguer {
            display: none;
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--texto);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            line-height: 1;
        }

        .btn-hamburguer:hover { background: var(--cinza-bg); }

        /* ---- Overlay escuro por trás da sidebar no mobile ---- */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 1039;
        }

        .sidebar-overlay.visivel { display: block; }

        /* ---- Responsivo ---- */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }

            .sidebar.aberta {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .btn-hamburguer {
                display: block;
            }

            .topbar {
                padding: 12px 16px;
            }

            .page-body {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<!-- Overlay (fecha a sidebar ao clicar fora) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="fecharSidebar()"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><img src="/SysFAA/imgs/logoWhite.png" style="width:80px;object-fit:contain;"></div>
        <span>SysFAA</span>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-section">Principal</li>
        <li>
            <a href="/SysFAA/dashboard.php" class="<?= $paginaAtiva === 'dashboard' ? 'ativo' : '' ?>">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="/SysFAA/fichas/listar.php" class="<?= $paginaAtiva === 'fichas' ? 'ativo' : '' ?>">
                <i class="bi bi-file-earmark-medical"></i> Fichas
            </a>
        </li>
        <li>
            <a href="/SysFAA/pacientes/listar.php" class="<?= $paginaAtiva === 'pacientes' ? 'ativo' : '' ?>">
                <i class="bi bi-person-vcard"></i> Pacientes
            </a>
        </li>
        <li>
            <a href="/SysFAA/fichas/upload.php" class="<?= $paginaAtiva === 'upload' ? 'ativo' : '' ?>">
                <i class="bi bi-cloud-upload"></i> Upload de Ficha
            </a>
        </li>

        <?php if (Auth::temPermissao('admin')): ?>
        <li class="menu-section">Administração</li>
        <li>
            <a href="/SysFAA/admin/usuarios.php" class="<?= $paginaAtiva === 'usuarios' ? 'ativo' : '' ?>">
                <i class="bi bi-people"></i> Usuários
            </a>
        </li>
        <li>
            <a href="/SysFAA/admin/tipos_ficha.php" class="<?= $paginaAtiva === 'tipos' ? 'ativo' : '' ?>">
                <i class="bi bi-tags"></i> Tipos de Ficha
            </a>
        </li>
        <li>
            <a href="/SysFAA/admin/auditoria.php" class="<?= $paginaAtiva === 'auditoria' ? 'ativo' : '' ?>">
                <i class="bi bi-journal-text"></i> Auditoria
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <strong><?= htmlspecialchars($usuario['nome']) ?></strong>
        <?= ucfirst($usuario['perfil']) ?>
        &nbsp;·&nbsp; <a href="/SysFAA/auth/logout.php" style="color:rgba(255,255,255,.55);">Sair</a><br>
        <b>Developed by Bruno Collange <br>&copy; Colliveir - 2026</b>
    </div>
</nav>

<!-- CONTEÚDO PRINCIPAL -->
<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <!-- Botão hamburguer — só aparece no mobile -->
            <button class="btn-hamburguer" id="btnHamburguer" onclick="abrirSidebar()" title="Menu">
                <i class="bi bi-list"></i>
            </button>
            <h6><?= htmlspecialchars($paginaTitulo) ?></h6>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted d-none d-md-inline" style="font-size:.82rem;">
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars($usuario['nome']) ?>
            </span>
            <a href="/SysFAA/auth/logout.php" class="btn btn-sm btn-outline-secondary" title="Sair">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="page-body">

<script>
function abrirSidebar() {
    document.getElementById('sidebar').classList.add('aberta');
    document.getElementById('sidebarOverlay').classList.add('visivel');
    document.body.style.overflow = 'hidden'; // trava scroll da página
}

function fecharSidebar() {
    document.getElementById('sidebar').classList.remove('aberta');
    document.getElementById('sidebarOverlay').classList.remove('visivel');
    document.body.style.overflow = '';
}

// Fecha a sidebar ao clicar em qualquer link do menu (boa UX no mobile)
document.querySelectorAll('.sidebar-menu a').forEach(function(link) {
    link.addEventListener('click', fecharSidebar);
});
</script>
