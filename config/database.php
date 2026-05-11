<?php
// ============================================================
//  SysFAA — Conexão com o Banco de Dados (PDO Singleton)
// ============================================================

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Em produção, nunca exponha detalhes do erro
                error_log('[SysFAA] Falha na conexão: ' . $e->getMessage());
                http_response_code(500);
                die('Erro interno. Contate o administrador.');
            }
        }
        return self::$instance;
    }

    // Evita clone e deserialização
    private function __clone() {}
    public function __wakeup() { throw new \Exception('Não permitido.'); }
}
