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
        'nome'      => trim($_POST['nome']       ?? ''),
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
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="usuarios.php" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:700;"><?= $paginaTitulo ?></h4>
        <?php if ($modo === 'editar'): ?>
            <p class="text-muted mb-0" style="font-size:.85rem;">ID #<?= $id ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body p-4">

                <?php if ($erro): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:8px;font-size:.88rem;">
                        <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i><?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Nome completo <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control" style="border-radius:8px;border-color:#d1dff0;"
                            placeholder="Digite o Nome completo" value="<?= htmlspecialchars($dados['nome']) ?>" maxlength="120" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">E-mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" style="border-radius:8px;border-color:#d1dff0;"
                             placeholder="ex.: email@gmail.com" value="<?= htmlspecialchars($dados['email']) ?>" maxlength="150" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:500;font-size:.88rem;">Perfil de acesso <span class="text-danger">*</span></label>
                        <select name="perfil_id" class="form-select" style="border-radius:8px;border-color:#d1dff0;" required>
                            <?php foreach ($perfis as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $dados['perfil_id'] == $p['id'] ? 'selected' : '' ?>>
                                    <?= ucfirst($p['nome']) ?> — <?= $p['descricao'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr class="my-4" style="border-color:#e8edf5;">

                    <p class="text-muted mb-3" style="font-size:.83rem;">
                        <?= $modo === 'editar' ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 8 caracteres.' ?>
                    </p>

                    <div class="mb-3">
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

                    <div class="mb-4">
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

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius:8px;font-family:'Sora',sans-serif;font-weight:600;">
                            <i class="bi <?= $modo === 'criar' ? 'bi-person-plus' : 'bi-check-lg' ?> me-2"></i>
                            <?= $modo === 'criar' ? 'Criar Usuário' : 'Salvar Alterações' ?>
                        </button>
                        <a href="usuarios.php" class="btn btn-outline-secondary" style="border-radius:8px;">Cancelar</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
    function toggleSenha(inputId, iconId) {
        const i = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        i.type = i.type === 'password' ? 'text' : 'password';
        ico.className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
</script>