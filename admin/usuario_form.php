<?php
// ============================================================
//  Todo o PHP ANTES do header.php para evitar "headers already sent"
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirPerfil('admin');

$db   = Database::get();
$id   = (int)($_GET['id'] ?? 0);
$modo = $id > 0 ? 'editar' : 'criar';

$perfis = $db->query('SELECT id, nome, descricao FROM perfis ORDER BY id ASC')->fetchAll();

// Carrega dados se editando
$usuario = ['nome' => '', 'email' => '', 'perfil_id' => 2, 'ativo' => 1];
if ($modo === 'editar') {
    $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        header('Location: usuarios.php?erro=' . urlencode('Usuário não encontrado.'));
        exit;
    }
    $usuario = $row;
}

$erro  = '';
$dados = $usuario;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome'      => mb_strtoupper(trim($_POST['nome'] ?? ''), 'UTF-8'),
        'email'     => trim($_POST['email']      ?? ''),
        'perfil_id' => (int)($_POST['perfil_id'] ?? 2),
        'senha'     => $_POST['senha']            ?? '',
        'senha2'    => $_POST['senha2']           ?? '',
    ];

    if (mb_strlen($dados['nome']) < 3) {
        $erro = 'O nome deve ter pelo menos 3 caracteres.';
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif ($modo === 'criar' && strlen($dados['senha']) < 8) {
        $erro = 'A senha deve ter no mínimo 8 caracteres.';
    } elseif (!empty($dados['senha']) && $dados['senha'] !== $dados['senha2']) {
        $erro = 'As senhas não conferem.';
    } else {
        $dup = $db->prepare('SELECT id FROM usuarios WHERE email = :email AND id != :id LIMIT 1');
        $dup->execute([':email' => $dados['email'], ':id' => $id]);
        if ($dup->fetch()) {
            $erro = 'Este e-mail já está em uso por outro usuário.';
        }
    }

    if (!$erro) {
        if ($modo === 'criar') {
            $hash = password_hash($dados['senha'], PASSWORD_BCRYPT);
            $db->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, perfil_id)
                 VALUES (:nome, :email, :hash, :perfil)'
            )->execute([
                ':nome'   => $dados['nome'],
                ':email'  => $dados['email'],
                ':hash'   => $hash,
                ':perfil' => $dados['perfil_id'],
            ]);
            $novoId = $db->lastInsertId();
            Auth::registrarAuditoria(Auth::usuario()['id'], 'usuario_criado', "Usuário ID $novoId: {$dados['nome']}");
            header('Location: usuarios.php?ok=' . urlencode("Usuário \"{$dados['nome']}\" criado com sucesso."));
            exit;
        } else {
            $campos = 'nome = :nome, email = :email, perfil_id = :perfil';
            $params = [
                ':nome'   => $dados['nome'],
                ':email'  => $dados['email'],
                ':perfil' => $dados['perfil_id'],
                ':id'     => $id,
            ];
            if (!empty($dados['senha'])) {
                $campos .= ', senha_hash = :hash';
                $params[':hash'] = password_hash($dados['senha'], PASSWORD_BCRYPT);
            }
            $db->prepare("UPDATE usuarios SET $campos WHERE id = :id")->execute($params);
            Auth::registrarAuditoria(Auth::usuario()['id'], 'usuario_editado', "Usuário ID $id: {$dados['nome']}");
            header('Location: usuarios.php?ok=' . urlencode("Usuário \"{$dados['nome']}\" atualizado com sucesso."));
            exit;
        }
    }
}

// Só inclui o header DEPOIS de todos os redirecionamentos possíveis
$paginaTitulo = $modo === 'editar' ? 'Editar Usuário' : 'Novo Usuário';
$paginaAtiva  = 'usuarios';
require_once __DIR__ . '/../includes/header.php';

// Mapa visual dos perfis (mesmas cores usadas em usuarios.php)
$coresPerfil  = ['admin' => '#dc3545', 'administracao' => '#1a56a0', 'recepcao' => '#198754'];
$bgsPerfil    = ['admin' => '#fff2f2', 'administracao' => '#e8f1fb', 'recepcao' => '#e9f7ef'];
$iconesPerfil = ['admin' => 'bi-shield-lock-fill', 'administracao' => 'bi-briefcase-fill', 'recepcao' => 'bi-person-workspace'];
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="usuarios.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:700;"><?= $paginaTitulo ?></h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            <?= $modo === 'editar' ? 'Atualize os dados e permissões deste usuário' : 'Preencha os dados para conceder acesso ao sistema' ?>
        </p>
    </div>
</div>

<form method="POST" novalidate>

<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">

        <!-- Faixa de identificação -->
        <div class="d-flex align-items-center gap-3 p-4" style="background:linear-gradient(135deg,#1a56a0,#123f78);color:#fff;">
            <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.3rem;flex-shrink:0;">
                <?php if ($modo === 'editar' && $dados['nome']): ?>
                    <?= mb_strtoupper(mb_substr($dados['nome'], 0, 1)) ?>
                <?php else: ?>
                    <i class="bi bi-person-plus"></i>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-family:'Sora',sans-serif;font-weight:700;font-size:1.05rem;">
                    <?= $modo === 'editar' ? htmlspecialchars($dados['nome']) : 'Novo Usuário' ?>
                </div>
                <?php if ($modo === 'editar'): ?>
                <span class="badge" style="background:rgba(255,255,255,.18);font-weight:500;font-size:.75rem;">ID #<?= $id ?></span>
                <?php else: ?>
                <span style="font-size:.82rem;opacity:.85;">Conta de acesso ao SysFAA</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">

            <?php if ($erro): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:8px;font-size:.88rem;">
                    <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i><?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

                <div class="form-secao-titulo">Dados da conta</div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Nome completo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;border-color:#d1dff0;">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <input type="text" name="nome" class="form-control border-start-0 ps-0"
                                style="border-radius:0 8px 8px 0;border-color:#d1dff0;text-transform:uppercase;"
                                placeholder="Digite o nome completo" value="<?= htmlspecialchars($dados['nome']) ?>" maxlength="120" required autofocus>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">E-mail <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius:8px 0 0 8px;border-color:#d1dff0;">
                                <i class="bi bi-envelope text-muted"></i>
                            </span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0"
                                style="border-radius:0 8px 8px 0;border-color:#d1dff0;"
                                placeholder="ex.: email@gmail.com" value="<?= htmlspecialchars($dados['email']) ?>" maxlength="150" required>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label mb-2" style="font-weight:500;font-size:.88rem;">Perfil de acesso <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <?php foreach ($perfis as $p):
                            $slug    = $p['nome'];
                            $cor     = $coresPerfil[$slug]  ?? '#6c757d';
                            $bg      = $bgsPerfil[$slug]    ?? '#f8f9fa';
                            $icone   = $iconesPerfil[$slug] ?? 'bi-person-badge';
                            $checked = (int)$dados['perfil_id'] === (int)$p['id'];
                        ?>
                        <div class="col-md-4 col-12">
                            <input type="radio" name="perfil_id" id="perfil-<?= $p['id'] ?>" value="<?= $p['id'] ?>"
                                   class="perfil-radio" <?= $checked ? 'checked' : '' ?> required>
                            <label for="perfil-<?= $p['id'] ?>" class="perfil-card" style="--cor:<?= $cor ?>;--bg:<?= $bg ?>;">
                                <i class="bi <?= $icone ?>"></i>
                                <span class="perfil-card-nome"><?= ucfirst($p['nome']) ?></span>
                                <span class="perfil-card-desc"><?= htmlspecialchars($p['descricao']) ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-secao-titulo">Segurança</div>
                <p class="text-muted mb-3" style="font-size:.83rem;">
                    <?= $modo === 'editar' ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 8 caracteres.' ?>
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">
                            Senha <?= $modo === 'criar' ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                        <div class="input-group">
                            <input type="password" id="senha" name="senha" class="form-control"
                                style="border-radius:8px 0 0 8px;border-color:#d1dff0;"
                                placeholder="••••••••" minlength="8"
                                <?= $modo === 'criar' ? 'required' : '' ?>>
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('senha','ico1')"
                                style="border-color:#d1dff0;border-radius:0 8px 8px 0;">
                                <i class="bi bi-eye" id="ico1"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">
                            Confirmar senha <?= $modo === 'criar' ? '<span class="text-danger">*</span>' : '' ?>
                        </label>
                        <div class="input-group">
                            <input type="password" id="senha2" name="senha2" class="form-control"
                                style="border-radius:8px 0 0 8px;border-color:#d1dff0;"
                                placeholder="••••••••"
                                <?= $modo === 'criar' ? 'required' : '' ?>>
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSenha('senha2','ico2')"
                                style="border-color:#d1dff0;border-radius:0 8px 8px 0;">
                                <i class="bi bi-eye" id="ico2"></i>
                            </button>
                        </div>
                    </div>
                </div>

        </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-3">
    <a href="usuarios.php" class="btn btn-outline-secondary px-4" style="border-radius:8px;">Cancelar</a>
    <button type="submit" class="btn btn-success px-4" style="border-radius:8px;font-weight:600;">
        <i class="bi bi-floppy me-2"></i>Salvar
    </button>
</div>

</form>

<style>
.form-secao-titulo {
    font-family: 'Sora', sans-serif;
    font-weight: 700;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #7a8aaa;
    margin-bottom: 14px;
}
.perfil-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.perfil-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 4px;
    height: 100%;
    padding: 16px 10px;
    border: 1.5px solid #d1dff0;
    border-radius: 10px;
    cursor: pointer;
    transition: border-color .15s, background .15s, box-shadow .15s;
}
.perfil-card i { font-size: 1.3rem; color: var(--cor); margin-bottom: 2px; }
.perfil-card-nome { font-weight: 600; font-size: .86rem; color: #1e2d45; }
.perfil-card-desc { font-size: .74rem; color: #8896ac; line-height: 1.3; }
.perfil-radio:checked + .perfil-card {
    border-color: var(--cor);
    background: var(--bg);
    box-shadow: 0 0 0 4px var(--bg);
}
.perfil-radio:focus-visible + .perfil-card {
    outline: 2px solid var(--cor);
    outline-offset: 2px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
    function toggleSenha(inputId, iconId) {
        const i = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        i.type = i.type === 'password' ? 'text' : 'password';
        ico.className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
</script>