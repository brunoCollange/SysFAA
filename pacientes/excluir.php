<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
Auth::exigirPerfil('admin','administracao');

$db = Database::get();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar.php?erro=' . urlencode('ID inválido.'));
    exit;
}

// Busca paciente
$stmt = $db->prepare('SELECT nome FROM pacientes WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: listar.php?erro=' . urlencode('Paciente não encontrado.'));
    exit;
}

// Verifica se há fichas vinculadas
$fichas = $db->prepare('SELECT COUNT(*) FROM fichas WHERE paciente_id = :id');
$fichas->execute([':id' => $id]);
$qtdFichas = (int)$fichas->fetchColumn();

if ($qtdFichas > 0) {
    header('Location: listar.php?erro=' . urlencode(
        "Não é possível excluir \"{$paciente['nome']}\" pois possui $qtdFichas ficha(s) vinculada(s). Remova as fichas antes."
    ));
    exit;
}

// Exclui
$db->prepare('DELETE FROM pacientes WHERE id = :id')->execute([':id' => $id]);
Auth::registrarAuditoria(Auth::usuario()['id'], 'paciente_excluido', "Paciente ID $id: {$paciente['nome']}");

header('Location: listar.php?ok=' . urlencode("Paciente \"{$paciente['nome']}\" excluído com sucesso."));
exit;
