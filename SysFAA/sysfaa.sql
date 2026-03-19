-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 12/03/2026 às 15:45
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sysfaa`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `acao` varchar(60) NOT NULL,
  `descricao` varchar(500) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `acao`, `descricao`, `ip`, `criado_em`) VALUES
(1, NULL, 'login_falha', 'Tentativa com e-mail: admin@sysfaa.local', '::1', '2026-03-12 09:29:36'),
(2, NULL, 'login_falha', 'Tentativa com e-mail: email@hospital.com', '::1', '2026-03-12 09:32:11'),
(3, NULL, 'login_falha', 'Tentativa com e-mail: email@hospital.com', '::1', '2026-03-12 09:52:06'),
(4, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 09:53:30'),
(5, 3, 'usuario_editado', 'Usuário ID 2: teste teste', '::1', '2026-03-12 10:09:05'),
(6, 3, 'usuario_editado', 'Usuário ID 2: teste teste', '::1', '2026-03-12 10:12:03'),
(7, 3, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 10:12:10'),
(8, 2, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 10:12:18'),
(9, 2, 'paciente_criado', 'Paciente ID 1: teste teste', '::1', '2026-03-12 10:16:30'),
(10, 2, 'paciente_criado', 'Paciente ID 2: teste teste ss', '::1', '2026-03-12 10:19:46'),
(11, 2, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 10:23:00'),
(12, NULL, 'login_falha', 'Tentativa com e-mail: admin@gmail.com', '::1', '2026-03-12 10:23:10'),
(13, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 10:23:33'),
(14, 3, 'upload', 'Ficha ID 1: ficha_em_branco.pdf', '::1', '2026-03-12 10:30:18'),
(15, 3, 'visualizar', 'Ficha ID 1: ficha_em_branco.pdf', '::1', '2026-03-12 10:30:29'),
(16, 3, 'excluir_ficha', 'Ficha ID 1: ficha_em_branco.pdf', '::1', '2026-03-12 10:34:09'),
(17, 3, 'paciente_excluido', 'Paciente ID 1: teste teste', '::1', '2026-03-12 10:34:15'),
(18, 3, 'paciente_excluido', 'Paciente ID 2: teste teste ss', '::1', '2026-03-12 10:34:17'),
(19, 3, 'usuario_editado', 'Usuário ID 2: teste', '::1', '2026-03-12 10:36:12'),
(20, 3, 'tipo_editado', 'ID 3: FAA Padrão', '::1', '2026-03-12 10:43:05'),
(21, 3, 'tipo_editado', 'ID 4: Evolução Médica', '::1', '2026-03-12 10:46:52'),
(22, 3, 'tipo_editado', 'ID 5: Evolução de Enfermagem', '::1', '2026-03-12 10:47:03'),
(23, 3, 'tipo_editado', 'ID 2: Medicação', '::1', '2026-03-12 10:47:12'),
(24, 3, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 10:48:29'),
(25, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 10:48:55'),
(26, 3, 'usuario_criado', 'Usuário ID 4: recepcao', '::1', '2026-03-12 10:50:03'),
(27, 3, 'tipo_criado', 'Curativo', '::1', '2026-03-12 10:51:25'),
(28, 3, 'paciente_criado', 'Paciente ID 3: teste da silva', '::1', '2026-03-12 10:53:52'),
(29, 3, 'upload', 'Ficha ID 2: ficha_em_branco.pdf', '::1', '2026-03-12 10:55:46'),
(30, 3, 'visualizar', 'Ficha ID 2: ficha_em_branco.pdf', '::1', '2026-03-12 11:01:14'),
(31, 3, 'download', 'Ficha ID 2: ficha_em_branco.pdf', '::1', '2026-03-12 11:01:21'),
(32, 3, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:01:44'),
(33, 4, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:01:50'),
(34, 4, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:02:38'),
(35, NULL, 'login_falha', 'Tentativa com e-mail: administracao@gmail.com', '::1', '2026-03-12 11:02:52'),
(36, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:03:35'),
(37, 3, 'usuario_editado', 'Usuário ID 2: administracao', '::1', '2026-03-12 11:05:14'),
(38, 3, 'tipo_criado', 'Notificação', '::1', '2026-03-12 11:07:12'),
(39, 3, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:07:37'),
(40, NULL, 'login_falha', 'Tentativa com e-mail: administracao@gmail.com', '::1', '2026-03-12 11:08:02'),
(41, 2, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:08:20'),
(42, 2, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:08:34'),
(43, 2, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:08:46'),
(44, 2, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:08:55'),
(45, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:09:14'),
(46, 3, 'usuario_editado', 'Usuário ID 3: Administrador', '::1', '2026-03-12 11:09:47'),
(47, 3, 'tipo_excluido', 'Evolução de Enfermagem', '::1', '2026-03-12 11:10:39'),
(48, 3, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:17:27'),
(49, NULL, 'login_falha', 'Tentativa com e-mail: administracao@gmail.com', '::1', '2026-03-12 11:17:43'),
(50, NULL, 'login_falha', 'Tentativa com e-mail: administracao@gmail.com', '::1', '2026-03-12 11:17:56'),
(51, 2, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:18:09'),
(52, 2, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:18:19'),
(53, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:18:24'),
(54, 3, 'usuario_editado', 'Usuário ID 2: administracao', '::1', '2026-03-12 11:18:40'),
(55, 3, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:18:52'),
(56, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:29:56'),
(57, 2, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:31:22'),
(58, 3, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:31:43'),
(59, 2, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:31:52'),
(60, 2, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:31:55'),
(61, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:32:00'),
(62, 3, 'logout', 'Sessão encerrada.', '::1', '2026-03-12 11:43:51'),
(63, 3, 'login_ok', 'Login realizado com sucesso.', '::1', '2026-03-12 11:44:12'),
(64, 3, 'excluir_ficha', 'Ficha ID 2: ficha_em_branco.pdf', '::1', '2026-03-12 11:45:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fichas`
--

CREATE TABLE `fichas` (
  `id` int(10) UNSIGNED NOT NULL,
  `paciente_id` int(10) UNSIGNED NOT NULL,
  `tipo_ficha_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `tamanho_bytes` int(10) UNSIGNED NOT NULL,
  `data_ficha` date NOT NULL,
  `observacao` varchar(500) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pacientes`
--

INSERT INTO `pacientes` (`id`, `nome`, `criado_em`) VALUES
(3, 'teste da silva', '2026-03-12 10:53:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfis`
--

CREATE TABLE `perfis` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perfis`
--

INSERT INTO `perfis` (`id`, `nome`, `descricao`, `criado_em`) VALUES
(1, 'admin', 'Acesso total ao sistema', '2026-03-12 09:14:01'),
(2, 'administracao', 'Acesso total às fichas (criar, editar, excluir)', '2026-03-12 09:14:01'),
(3, 'recepcao', 'Apenas visualização e download de fichas', '2026-03-12 09:14:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessoes`
--

CREATE TABLE `sessoes` (
  `token` char(64) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `expira_em` datetime NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_ficha`
--

CREATE TABLE `tipos_ficha` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cor` varchar(7) NOT NULL DEFAULT '#0d6efd',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tipos_ficha`
--

INSERT INTO `tipos_ficha` (`id`, `nome`, `cor`, `ativo`, `criado_em`) VALUES
(1, 'Internação', '#0d6efd', 1, '2026-03-12 09:14:01'),
(2, 'Medicação', '#198754', 1, '2026-03-12 09:14:01'),
(3, 'FAA Padrão', '#dc3545', 1, '2026-03-12 09:14:01'),
(4, 'Evolução Médica', '#fd7e14', 1, '2026-03-12 09:14:01'),
(6, 'Curativo', '#e1fd0d', 1, '2026-03-12 10:51:25'),
(7, 'Notificação', '#fd0ddd', 1, '2026-03-12 11:07:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `perfil_id` int(10) UNSIGNED NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_acesso` datetime DEFAULT NULL,
  `ultima_atividade` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `perfil_id`, `ativo`, `criado_em`, `ultimo_acesso`, `ultima_atividade`) VALUES
(2, 'administracao', 'administracao@gmail.com', '$2y$10$nKVQryD5msVKCpGswtlLpuT5n5pGd3QFUTOQc.6C9EK5gTXVoxje.', 2, 1, '2026-03-12 09:31:40', '2026-03-12 11:31:52', NULL),
(3, 'Administrador', 'admin@gmail.com', '$2y$10$6s7F9nPD5cEEf8fJFxdo6utqTl22zL8rG4mHq/CkDxmcWoTKI9DyK', 1, 1, '2026-03-12 09:53:15', '2026-03-12 11:44:12', '2026-03-12 11:45:25'),
(4, 'recepcao', 'recepcao@gmail.com', '$2y$10$8ZdQ8lUrQn0JWcN.xKqe.etynYY2ATiDGcS1WuoYIzIESwtze74.O', 3, 1, '2026-03-12 10:50:03', '2026-03-12 11:01:50', '2026-03-12 11:01:50');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_acao` (`acao`),
  ADD KEY `idx_data` (`criado_em`);

--
-- Índices de tabela `fichas`
--
ALTER TABLE `fichas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ficha_usuario` (`usuario_id`),
  ADD KEY `idx_paciente` (`paciente_id`),
  ADD KEY `idx_tipo` (`tipo_ficha_id`),
  ADD KEY `idx_data` (`data_ficha`);

--
-- Índices de tabela `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nome` (`nome`);

--
-- Índices de tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `sessoes`
--
ALTER TABLE `sessoes`
  ADD PRIMARY KEY (`token`),
  ADD KEY `fk_sessao_usuario` (`usuario_id`);

--
-- Índices de tabela `tipos_ficha`
--
ALTER TABLE `tipos_ficha`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuario_perfil` (`perfil_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de tabela `fichas`
--
ALTER TABLE `fichas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `tipos_ficha`
--
ALTER TABLE `tipos_ficha`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `fichas`
--
ALTER TABLE `fichas`
  ADD CONSTRAINT `fk_ficha_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `fk_ficha_tipo` FOREIGN KEY (`tipo_ficha_id`) REFERENCES `tipos_ficha` (`id`),
  ADD CONSTRAINT `fk_ficha_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `sessoes`
--
ALTER TABLE `sessoes`
  ADD CONSTRAINT `fk_sessao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
