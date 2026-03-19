<?php
// ============================================================
//  SysFAA — Processar Login (POST)
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Auth.php';

Auth::iniciarSessao();

// Se já estiver logado, vai pro dashboard
if (Auth::verificar()) {
    header('Location: /SysFAA/dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email  = filter_input(INPUT_POST, 'email',  FILTER_SANITIZE_EMAIL) ?? '';
    $senha  = $_POST['senha'] ?? '';
    $lembrar = isset($_POST['lembrar']);

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $resultado = Auth::login($email, $senha, $lembrar);
        if ($resultado['ok']) {
            header('Location: /SysFAA/dashboard.php');
            exit;
        }
        $erro = $resultado['msg'];
    }
}

// Se acessar via GET sem estar logado, redireciona para a tela de login
header('Location: login.php' . ($erro ? '?erro=' . urlencode($erro) : ''));
exit;
