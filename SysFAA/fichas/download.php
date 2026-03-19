<?php
// ============================================================
//  SysFAA — Download de PDF
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirLogin('/SysFAA/auth/login.php');

$db = Database::get();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM fichas WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$ficha = $stmt->fetch();

if (!$ficha) {
    http_response_code(404);
    die('Ficha não encontrada.');
}

$caminho = UPLOAD_PATH . $ficha['nome_arquivo'];

if (!file_exists($caminho)) {
    http_response_code(404);
    die('Arquivo não encontrado no servidor.');
}

Auth::registrarAuditoria(Auth::usuario()['id'], 'download', "Ficha ID $id: {$ficha['nome_original']}");

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . addslashes($ficha['nome_original']) . '"');
header('Content-Length: ' . filesize($caminho));
header('Cache-Control: private');

readfile($caminho);
exit;
