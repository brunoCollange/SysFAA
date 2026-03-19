<?php
// ============================================================
//  SysFAA — Processar Upload de PDF (chamado via fetch/AJAX)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/Auth.php';

Auth::iniciarSessao();
header('Content-Type: application/json; charset=utf-8');

// Só aceita POST autenticado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verificar()) {
    echo json_encode(['ok' => false, 'msg' => 'Acesso negado.']);
    exit;
}

$usuario = Auth::usuario();
$db      = Database::get();

// ── Valida campos do formulário ─────────────────────────────
$pacienteId  = (int)($_POST['paciente_id']   ?? 0);
$tipoFichaId = (int)($_POST['tipo_ficha_id'] ?? 0);
$dataFicha   = trim($_POST['data_ficha']     ?? '');

if (!$pacienteId || !$tipoFichaId || !$dataFicha) {
    echo json_encode(['ok' => false, 'msg' => 'Campos obrigatórios não informados.']);
    exit;
}

// Valida data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFicha) || !strtotime($dataFicha)) {
    echo json_encode(['ok' => false, 'msg' => 'Data inválida.']);
    exit;
}

// Verifica se paciente e tipo existem no banco
$stmtP = $db->prepare('SELECT id FROM pacientes WHERE id = :id LIMIT 1');
$stmtP->execute([':id' => $pacienteId]);
if (!$stmtP->fetch()) {
    echo json_encode(['ok' => false, 'msg' => 'Paciente não encontrado.']);
    exit;
}

$stmtT = $db->prepare('SELECT id FROM tipos_ficha WHERE id = :id AND ativo = 1 LIMIT 1');
$stmtT->execute([':id' => $tipoFichaId]);
if (!$stmtT->fetch()) {
    echo json_encode(['ok' => false, 'msg' => 'Tipo de ficha inválido.']);
    exit;
}

// ── Valida arquivo enviado ───────────────────────────────────
if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    $erros = [
        UPLOAD_ERR_INI_SIZE   => 'Arquivo excede o limite do servidor (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'Arquivo excede o limite do formulário.',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto.',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada no servidor.',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar arquivo no servidor.',
    ];
    $codigo = $_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(['ok' => false, 'msg' => $erros[$codigo] ?? 'Erro desconhecido no upload.']);
    exit;
}

$arquivo      = $_FILES['arquivo'];
$nomeOriginal = $arquivo['name'];
$tmpPath      = $arquivo['tmp_name'];
$tamanho      = $arquivo['size'];

// Verifica tipo MIME real (não só extensão)
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeReal = finfo_file($finfo, $tmpPath);
finfo_close($finfo);

if ($mimeReal !== 'application/pdf') {
    echo json_encode(['ok' => false, 'msg' => '"' . $nomeOriginal . '" não é um PDF válido.']);
    exit;
}

if ($tamanho > MAX_UPLOAD_SIZE) {
    echo json_encode(['ok' => false, 'msg' => 'Arquivo excede o limite de 20 MB.']);
    exit;
}

// ── Salva o arquivo com nome único ──────────────────────────
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

$nomeUnico  = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.pdf';
$destino    = UPLOAD_PATH . $nomeUnico;

if (!move_uploaded_file($tmpPath, $destino)) {
    error_log("[SysFAA] Falha ao mover arquivo para: $destino");
    echo json_encode(['ok' => false, 'msg' => 'Falha ao salvar o arquivo no servidor.']);
    exit;
}

// ── Registra no banco de dados ───────────────────────────────
try {
    $db->prepare(
        'INSERT INTO fichas
            (paciente_id, tipo_ficha_id, usuario_id, nome_original, nome_arquivo, tamanho_bytes, data_ficha)
         VALUES
            (:pid, :tid, :uid, :orig, :arq, :tam, :data)'
    )->execute([
        ':pid'  => $pacienteId,
        ':tid'  => $tipoFichaId,
        ':uid'  => $usuario['id'],
        ':orig' => $nomeOriginal,
        ':arq'  => $nomeUnico,
        ':tam'  => $tamanho,
        ':data' => $dataFicha,
    ]);

    $fichaId = $db->lastInsertId();
    Auth::registrarAuditoria($usuario['id'], 'upload', "Ficha ID $fichaId: $nomeOriginal");

    echo json_encode(['ok' => true, 'id' => $fichaId]);

} catch (\Throwable $e) {
    // Remove arquivo físico se o banco falhou
    if (file_exists($destino)) unlink($destino);
    error_log('[SysFAA] Erro ao inserir ficha: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro ao registrar ficha no banco de dados.']);
}
