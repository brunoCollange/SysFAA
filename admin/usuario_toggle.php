<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirPerfil('admin');

$db = Database::get();
$id = (int)($_GET['id'] ?? 0);

if ($id === Auth::usuario()['id']) {
    header('Location: usuarios.php?erro=' . urlencode('Você não pode desativar sua própria conta.'));
    exit;
}

$stmt = $db->prepare('SELECT nome, ativo FROM usuarios WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$u = $stmt->fetch();

if (!$u) {
    header('Location: usuarios.php?erro=' . urlencode('Usuário não encontrado.'));
    exit;
}

$novoStatus = $u['ativo'] ? 0 : 1;
$db->prepare('UPDATE usuarios SET ativo = :s WHERE id = :id')
   ->execute([':s' => $novoStatus, ':id' => $id]);

$acao = $novoStatus ? 'usuario_ativado' : 'usuario_desativado';
Auth::registrarAuditoria(Auth::usuario()['id'], $acao, "Usuário ID $id: {$u['nome']}");

$msg = $novoStatus
    ? "Usuário \"{$u['nome']}\" ativado com sucesso."
    : "Usuário \"{$u['nome']}\" desativado com sucesso.";

header('Location: usuarios.php?ok=' . urlencode($msg));
exit;
