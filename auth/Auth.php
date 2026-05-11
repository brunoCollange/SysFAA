<?php
// ============================================================
//  SysFAA — Classe de Autenticação
// ============================================================

require_once __DIR__ . '/../config/database.php';

class Auth
{
    // --------------------------------------------------------
    // Inicia sessão segura
    // --------------------------------------------------------
    public static function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false,   // mude para true se usar HTTPS
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    // --------------------------------------------------------
    // Tenta efetuar login
    // --------------------------------------------------------
    public static function login(string $email, string $senha, bool $lembrar = false): array
    {
        $db  = Database::get();
        $sql = 'SELECT u.*, p.nome AS perfil
                FROM usuarios u
                JOIN perfis p ON p.id = u.perfil_id
                WHERE u.email = :email AND u.ativo = 1
                LIMIT 1';

        $stmt = $db->prepare($sql);
        $stmt->execute([':email' => trim($email)]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
            self::registrarAuditoria(null, 'login_falha', "Tentativa com e-mail: $email");
            return ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
        }

        // Regenera ID de sessão para evitar fixação
        session_regenerate_id(true);

        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['perfil']       = $usuario['perfil'];
        $_SESSION['login_em']     = time();

        // Atualiza último acesso
        $db->prepare('UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id')
            ->execute([':id' => $usuario['id']]);

        // Cookie "lembrar-me"
        if ($lembrar) {
            self::criarTokenLembrar($usuario['id']);
        }

        self::registrarAuditoria($usuario['id'], 'login_ok', 'Login realizado com sucesso.');

        return ['ok' => true];
    }

    // --------------------------------------------------------
    // Encerra sessão
    // --------------------------------------------------------
    public static function logout(): void
    {
        self::iniciarSessao();

        if (isset($_SESSION['usuario_id'])) {
            self::registrarAuditoria($_SESSION['usuario_id'], 'logout', 'Sessão encerrada.');
        }

        // Remove cookie lembrar-me se existir
        if (isset($_COOKIE['sysfaa_remember'])) {
            $db = Database::get();
            $db->prepare('DELETE FROM sessoes WHERE token = :token')
                ->execute([':token' => $_COOKIE['sysfaa_remember']]);
            setcookie('sysfaa_remember', '', time() - 3600, '/', '', false, true);
        }
        // Limpa a atividade ao sair
        if (isset($_SESSION['usuario_id'])) {
            $db = Database::get();
            $db->prepare('UPDATE usuarios SET ultima_atividade = NULL WHERE id = :id')
                ->execute([':id' => $_SESSION['usuario_id']]);
        }
        $_SESSION = [];
        session_destroy();
    }

    // --------------------------------------------------------
    // Verifica se está autenticado
    // --------------------------------------------------------
    public static function verificar(): bool
    {
        self::iniciarSessao();

        if (!empty($_SESSION['usuario_id'])) {
            // Atualiza última atividade a cada requisição (máx 1x por minuto)
            $ultimaAtt = $_SESSION['ultima_att'] ?? 0;
            if (time() - $ultimaAtt > 60) {
                try {
                    $db = Database::get();
                    $db->prepare('UPDATE usuarios SET ultima_atividade = NOW() WHERE id = :id')
                        ->execute([':id' => $_SESSION['usuario_id']]);
                    $_SESSION['ultima_att'] = time();
                } catch (\Throwable) {
                }
            }
            return true;
        }

        // Tenta restaurar via cookie lembrar-me
        if (isset($_COOKIE['sysfaa_remember'])) {
            return self::restaurarSessaoCookie($_COOKIE['sysfaa_remember']);
        }

        return false;
    }

    // --------------------------------------------------------
    // Redireciona se não autenticado
    // --------------------------------------------------------
    public static function exigirLogin(string $redirect = '/SysFAA/auth/login.php'): void
    {
        if (!self::verificar()) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    // --------------------------------------------------------
    // Retorna dados do usuário logado
    // --------------------------------------------------------
    public static function usuario(): array
    {
        return [
            'id'    => $_SESSION['usuario_id']   ?? null,
            'nome'  => $_SESSION['usuario_nome'] ?? '',
            'perfil' => $_SESSION['perfil']       ?? '',
        ];
    }

    // --------------------------------------------------------
    // Checa permissão por perfil
    // --------------------------------------------------------
    public static function temPermissao(string|array $perfis): bool
    {
        $perfilAtual = $_SESSION['perfil'] ?? '';
        $lista = is_array($perfis) ? $perfis : [$perfis];
        return in_array($perfilAtual, $lista, true);
    }

    public static function exigirPerfil(string|array $perfis): void
    {
        self::exigirLogin();
        if (!self::temPermissao($perfis)) {
            http_response_code(403);
            die('Acesso negado.');
        }
    }

    // --------------------------------------------------------
    // Privados — helpers
    // --------------------------------------------------------
    private static function criarTokenLembrar(int $usuarioId): void
    {
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', time() + COOKIE_REMEMBER);

        $db = Database::get();
        $db->prepare('INSERT INTO sessoes (token, usuario_id, expira_em) VALUES (:t,:u,:e)')
            ->execute([':t' => $token, ':u' => $usuarioId, ':e' => $expira]);

        setcookie('sysfaa_remember', $token, time() + COOKIE_REMEMBER, '/', '', false, true);
    }

    private static function restaurarSessaoCookie(string $token): bool
    {
        $db   = Database::get();
        $stmt = $db->prepare(
            'SELECT s.usuario_id, u.nome, p.nome AS perfil
             FROM sessoes s
             JOIN usuarios u ON u.id = s.usuario_id
             JOIN perfis   p ON p.id = u.perfil_id
             WHERE s.token = :token AND s.expira_em > NOW() AND u.ativo = 1
             LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();

        if (!$row) return false;

        session_regenerate_id(true);
        $_SESSION['usuario_id']   = $row['usuario_id'];
        $_SESSION['usuario_nome'] = $row['nome'];
        $_SESSION['perfil']       = $row['perfil'];
        $_SESSION['login_em']     = time();

        $db->prepare('UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id')
            ->execute([':id' => $row['usuario_id']]);

        return true;
    }

    public static function registrarAuditoria(?int $usuarioId, string $acao, string $descricao = ''): void
    {
        try {
            $db = Database::get();
            $db->prepare(
                'INSERT INTO auditoria (usuario_id, acao, descricao, ip)
                 VALUES (:u, :a, :d, :ip)'
            )->execute([
                ':u'  => $usuarioId,
                ':a'  => $acao,
                ':d'  => $descricao,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable) {
            // Não quebra o fluxo principal se o log falhar
        }
    }
}
