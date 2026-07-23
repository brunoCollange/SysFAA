<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Auth.php';

Auth::iniciarSessao();

header('Content-Type: application/json; charset=utf-8');

if (!Auth::verificar()) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$usuarioId       = Auth::usuario()['id'];
$senhaAtual      = $_POST['senha_atual']    ?? '';
$novaSenha       = $_POST['nova_senha']     ?? '';
$confirmarSenha  = $_POST['confirmar_senha'] ?? '';

$db   = Database::get();
$stmt = $db->prepare('SELECT senha_hash FROM usuarios WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $usuarioId]);
$hashAtual = $stmt->fetchColumn();

if (!$hashAtual || !password_verify($senhaAtual, $hashAtual)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Senha atual incorreta.']);
    exit;
}

if (strlen($novaSenha) < 8) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A nova senha deve ter no mínimo 8 caracteres.']);
    exit;
}

if ($novaSenha !== $confirmarSenha) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'As senhas não conferem.']);
    exit;
}

if (password_verify($novaSenha, $hashAtual)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A nova senha deve ser diferente da senha atual.']);
    exit;
}

$novoHash = password_hash($novaSenha, PASSWORD_BCRYPT);
$db->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id')
   ->execute([':hash' => $novoHash, ':id' => $usuarioId]);

Auth::registrarAuditoria($usuarioId, 'senha_alterada', 'Usuário alterou a própria senha.');

echo json_encode(['sucesso' => true, 'mensagem' => 'Senha alterada com sucesso.']);
