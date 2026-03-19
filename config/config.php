<?php
// ============================================================
//  SysFAA — Configurações Globais
// ============================================================

define('SYSFAA_VERSION', '1.0.0');
define('SYSFAA_NOME',    'SysFAA');

// ------------------------------------------------------------
// Banco de dados — ajuste conforme seu servidor
// ------------------------------------------------------------
define('DB_HOST',    'localhost');
define('DB_NAME',    'sysfaa');
define('DB_USER',    'root');       // troque pelo usuário do MySQL
define('DB_PASS',    '');           // troque pela senha do MySQL
define('DB_CHARSET', 'utf8mb4');

// ------------------------------------------------------------
// Caminhos
// ------------------------------------------------------------
define('ROOT_PATH',    dirname(__DIR__));
define('UPLOAD_PATH',  ROOT_PATH . '/uploads/fichas/');
define('UPLOAD_URL',   '/uploads/fichas/');

// Tamanho máximo de upload (bytes) — padrão 20 MB
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024);

// ------------------------------------------------------------
// Sessão
// ------------------------------------------------------------
define('SESSION_NAME',     'sysfaa_sess');
define('SESSION_LIFETIME', 28800);   // 8 horas em segundos
define('COOKIE_REMEMBER',  2592000); // 30 dias em segundos

// ------------------------------------------------------------
// Fuso horário
// ------------------------------------------------------------
date_default_timezone_set('America/Sao_Paulo');
