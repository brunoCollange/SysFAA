<?php
// ============================================================
//  SysFAA — Excluir Ficha (somente admin)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirPerfil('admin');

$db = Database::get();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM fichas WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$ficha = $stmt->fetch();

if (!$ficha) {
    header('Location: listar.php?erro=' . urlencode('Ficha não encontrada.'));
    exit;
}

// Remove arquivo físico
$caminho = UPLOAD_PATH . $ficha['nome_arquivo'];
if (file_exists($caminho)) {
    unlink($caminho);
}

// Remove do banco
$db->prepare('DELETE FROM fichas WHERE id = :id')->execute([':id' => $id]);
Auth::registrarAuditoria(Auth::usuario()['id'], 'excluir_ficha', "Ficha ID $id: {$ficha['nome_original']}");

header('Location: listar.php?ok=' . urlencode("Ficha \"{$ficha['nome_original']}\" excluída com sucesso."));
exit;
