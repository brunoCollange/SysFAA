<?php
// PHP SEMPRE PRIMEIRO — antes de qualquer HTML
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Auth.php';
Auth::iniciarSessao();

if (Auth::verificar()) {
    header('Location: /SysFAA/dashboard.php');
    exit;
}

$erro = htmlspecialchars($_GET['erro'] ?? '');
$msg  = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysFAA — Acesso ao Sistema</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --azul: #1a56a0;
            --azul-claro: #e8f1fb;
            --azul-mid: #2d6fc4;
            --cinza-bg: #f4f6fb;
            --texto: #1e2d45;
            --borda: #d1dff0;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--cinza-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Painel lateral azul */
        .login-wrapper {
            display: flex;
            width: 860px;
            max-width: 98vw;
            min-height: 520px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(26, 86, 160, .18);
        }

        .login-lateral {
            width: 42%;
            background: linear-gradient(155deg, #1a56a0 0%, #2d6fc4 60%, #3d8fe0 100%);
            padding: 48px 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        /* Decoração geométrica */
        .login-lateral::before {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            border: 40px solid rgba(255, 255, 255, .08);
            bottom: -80px;
            right: -80px;
        }

        .login-lateral::after {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 28px solid rgba(255, 255, 255, .06);
            top: -40px;
            left: -40px;
        }

        .login-lateral .logo-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .login-lateral .logo-icon img {
            width: 150px;
            height: 100px;
        }

        .login-lateral h1 {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 1.9rem;
            line-height: 1.2;
            margin: 0 0 8px;
        }

        .login-lateral p {
            font-size: .9rem;
            opacity: .8;
            margin: 0;
        }

        .login-lateral .rodape-lateral {
            font-size: .78rem;
            opacity: .6;
        }

        /* Formulário */
        .login-form-wrap {
            flex: 1;
            background: #fff;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-wrap h2 {
            font-family: 'Sora', sans-serif;
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--texto);
            margin-bottom: 6px;
        }

        .login-form-wrap .subtitulo {
            font-size: .88rem;
            color: #7a8aaa;
            margin-bottom: 32px;
        }

        .form-label {
            font-size: .82rem;
            font-weight: 500;
            color: var(--texto);
            margin-bottom: 5px;
        }

        .form-control {
            border: 1.5px solid var(--borda);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: .92rem;
            color: var(--texto);
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--azul-mid);
            box-shadow: 0 0 0 3px rgba(45, 111, 196, .12);
        }

        .input-group .form-control {
            border-right: none;
        }

        .input-group .btn-outline-secondary {
            border: 1.5px solid var(--borda);
            border-left: none;
            border-radius: 0 8px 8px 0;
            color: #7a8aaa;
            background: #fafbfd;
        }

        .input-group .btn-outline-secondary:hover {
            background: var(--azul-claro);
            color: var(--azul);
        }

        .btn-entrar {
            background: linear-gradient(90deg, var(--azul) 0%, var(--azul-mid) 100%);
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: .95rem;
            color: #fff;
            width: 100%;
            margin-top: 8px;
            transition: opacity .2s, transform .1s;
        }

        .btn-entrar:hover {
            opacity: .9;
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-entrar:active {
            transform: translateY(0);
        }

        .form-check-label {
            font-size: .83rem;
            color: #5a6a88;
        }

        .form-check-input:checked {
            background-color: var(--azul);
            border-color: var(--azul);
        }

        .alert-erro {
            background: #fff2f2;
            border: 1px solid #f5c2c7;
            border-radius: 8px;
            color: #842029;
            font-size: .87rem;
            padding: 10px 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-sucesso {
            background: #f0fdf4;
            border: 1px solid #b7edc8;
            border-radius: 8px;
            color: #166534;
            font-size: .87rem;
            padding: 10px 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Responsivo */
        @media (max-width: 640px) {
            .login-lateral {
                display: none;
            }

            .login-form-wrap {
                padding: 32px 24px;
            }

            .login-wrapper {
                border-radius: 0;
                min-height: 100vh;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">

        <!-- Lateral decorativa -->
        <div class="login-lateral">
            <div>
                <div class="logo-icon"><img src="/SysFAA/imgs/logoWhite.png"></i></div>
                <h1>SysFAA</h1>
                <p>Sistema de Gestão de Fichas Hospitalares</p>
            </div>
            <div>
                <p style="opacity:.75; font-size:.85rem; line-height:1.6;">
                    Armazene, organize e acesse fichas digitalizadas.
                </p>
            </div>
            <div class="rodape-lateral">v<?= SYSFAA_VERSION ?> &nbsp;•&nbsp; Developed by Bruno Collange</div>
        </div>

        <!-- Formulário -->
        <div class="login-form-wrap">
            <h2>Bem-vindo(a)</h2>
            <p class="subtitulo">Informe suas credenciais para continuar</p>

            <?php if ($erro): ?>
                <div class="alert-erro">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?= $erro ?>
                </div>
            <?php endif; ?>

            <?php if ($msg === 'saiu'): ?>
                <div class="alert-sucesso">
                    <i class="bi bi-check-circle-fill"></i>
                    Você saiu do sistema com sucesso.
                </div>
            <?php endif; ?>

            <form action="processar_login.php" method="POST" novalidate>

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="seu@email.com"
                        autocomplete="email"
                        required
                        value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control"
                            id="senha"
                            name="senha"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleSenha" tabindex="-1">
                            <i class="bi bi-eye" id="iconeSenha"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="lembrar" name="lembrar">
                        <label class="form-check-label" for="lembrar">
                            Manter-me conectado por 30 dias
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-entrar">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar no Sistema
                </button>

            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle visibilidade da senha
        document.getElementById('toggleSenha').addEventListener('click', function() {
            const input = document.getElementById('senha');
            const icone = document.getElementById('iconeSenha');
            if (input.type === 'password') {
                input.type = 'text';
                icone.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icone.className = 'bi bi-eye';
            }
        });
    </script>

</body>

</html>