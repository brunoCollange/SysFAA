# SysFAA — Sistema de Gestão de Fichas Hospitalares

Sistema web para gerenciamento de fichas hospitalares em PDF, com controle de acesso por perfis, auditoria de ações e upload de documentos.

---

## Requisitos

| Ferramenta | Versão mínima |
|---|---|
| XAMPP (ou equivalente) | 8.x |
| PHP | 8.2+ |
| MySQL / MariaDB | 10.4+ |
| Navegador moderno | Chrome, Firefox, Edge |

---

## Instalação no Windows (XAMPP)

### 1. Clone o repositório

Abra o CMD ou Git Bash dentro da pasta `C:\xampp\htdocs\` e rode:

```bash
git clone https://github.com/brunoCollange/SysFAA.git
```

Ou baixe o ZIP pelo GitHub e extraia em `C:\xampp\htdocs\SysFAA\`.

---

### 2. Importe o banco de dados

Abra o phpMyAdmin em `http://localhost/phpmyadmin` e siga os passos:

1. Clique em **Novo** no painel esquerdo
2. Digite o nome: `sysfaa`
3. Selecione o agrupamento: `utf8mb4_unicode_ci`
4. Clique em **Criar**
5. Com o banco `sysfaa` selecionado, vá na aba **Importar**
6. Clique em **Escolher arquivo** e selecione `sysfaa.sql`
7. Clique em **Importar**

O arquivo `sysfaa.sql` já contém toda a estrutura das tabelas e os usuários padrão do sistema.

---

### 3. Configure a conexão com o banco

Abra o arquivo `config/config.php` e ajuste as credenciais conforme seu servidor:

```php
define('DB_HOST',    'localhost');   // geralmente localhost no XAMPP
define('DB_NAME',    'sysfaa');      // nome do banco criado
define('DB_USER',    'root');        // usuário padrão do XAMPP
define('DB_PASS',    '');            // senha vazia no XAMPP padrão
```

> No XAMPP padrão o usuário é `root` com senha em branco. Se você alterou, ajuste aqui.

---

### 4. Crie a pasta de uploads

A pasta onde os PDFs são salvos precisa existir. Crie manualmente se não existir:

```
C:\xampp\htdocs\SysFAA\uploads\fichas\
```

Ou via CMD:

```cmd
mkdir C:\xampp\htdocs\SysFAA\uploads\fichas
```

---

### 5. Configure o Apache (opcional)

O arquivo `.htaccess` já está configurado. Se o Apache não estiver com `mod_rewrite` ativado, abra o `httpd.conf` do XAMPP e confirme que essa linha não está comentada:

```
LoadModule rewrite_module modules/mod_rewrite.so
```

---

### 6. Acesse o sistema

Com o XAMPP rodando (Apache + MySQL), abra no navegador:

```
http://localhost/SysFAA/auth/login.php
```

---

## Primeiro acesso

Use as credenciais padrão para entrar pela primeira vez:

| Campo | Valor |
|---|---|
| E-mail | `admin@gmail.com` |
| Senha | `admin` |

> Troque a senha imediatamente após o primeiro login. Isso pode ser feito a qualquer momento pelo menu do usuário, no canto superior direito de qualquer tela (**nome do usuário → Alterar Senha**).

---

## Perfis de acesso

| Perfil | Permissões |
|---|---|
| `admin` | Acesso total + painel de administração, usuários e auditoria |
| `administracao` | Gerencia pacientes e fichas (criar, editar, excluir, upload) |
| `recepcao` | Apenas visualiza e faz download de fichas |

---

## Funcionalidades

- **Dashboard**: cartões de resumo (fichas, pacientes, uploads do dia, usuários online), lista das fichas mais recentes e gráfico de distribuição por tipo de ficha.
- **Pacientes**: cadastro, edição e listagem com busca por nome. Clicar em um paciente abre um modal com os dados completos e ações (ver fichas, editar, excluir).
- **Fichas**: upload de PDFs vinculados a um paciente e tipo, com filtros por paciente, tipo e período. Clicar em uma ficha abre um modal com detalhes e ações (abrir PDF, baixar, excluir).
- **Tipos de Ficha**: cadastro de categorias de ficha com cor identificadora, usada em badges por todo o sistema.
- **Usuários** *(admin)*: criação e edição de contas, ativação/desativação e definição de perfil de acesso.
- **Auditoria** *(admin)*: histórico de ações do sistema (login, uploads, exclusões, alterações), com filtros por usuário, ação e período.
- **Menu do usuário**: no canto superior direito, permite alterar a própria senha (modal, sem recarregar a página) e sair do sistema.

Todas as telas de listagem seguem o mesmo padrão visual: barra de busca/filtro integrada ao card da tabela, linhas clicáveis que abrem um modal de detalhes (em vez de colunas de ações com ícones), e contagem de registros exibida abaixo do card.

---

## Estrutura de arquivos

```
SysFAA/
├── .htaccess                     ← Configuração de segurança Apache
├── dashboard.php                 ← Página principal (pós-login)
├── sysfaa.sql                    ← Banco de dados completo
│
├── config/
│   ├── config.php                ← Configurações globais e credenciais DB
│   └── database.php              ← Conexão PDO singleton
│
├── auth/
│   ├── Auth.php                  ← Classe de autenticação e sessão
│   ├── login.php                 ← Tela de login
│   ├── processar_login.php       ← Processa POST do login
│   ├── logout.php                ← Encerra sessão
│   └── alterar_senha.php         ← Endpoint (AJAX/JSON) para troca da própria senha
│
├── admin/
│   ├── usuarios.php              ← Gerenciamento de usuários
│   ├── usuario_form.php          ← Formulário de criação/edição de usuário
│   ├── usuario_toggle.php        ← Ativa/desativa usuário
│   ├── tipos_ficha.php           ← Gerenciamento de tipos de ficha
│   └── auditoria.php             ← Log de ações do sistema
│
├── pacientes/
│   ├── listar.php                ← Lista de pacientes
│   ├── cadastrar.php             ← Cadastro de paciente
│   ├── editar.php                ← Edição de paciente
│   └── excluir.php               ← Exclusão de paciente
│
├── fichas/
│   ├── listar.php                ← Lista de fichas por paciente
│   ├── upload.php                ← Tela de upload de PDF
│   ├── processar_upload.php      ← Processa o envio do arquivo
│   ├── visualizar.php            ← Visualiza o PDF no navegador
│   ├── download.php              ← Faz download do PDF
│   └── excluir.php               ← Exclusão de ficha
│
├── includes/
│   ├── header.php                ← Navbar, sidebar e menu do usuário (reutilizáveis)
│   └── footer.php                ← Rodapé + modal de alteração de senha (reutilizáveis)
│
├── imgs/
│   └── logoWhite.png             ← Logo do sistema
│
└── uploads/
    └── fichas/                   ← PDFs enviados ficam aqui (criar manualmente)
```

---


## Problemas comuns

**Página em branco ou erro 500**
- Verifique se o Apache e MySQL estão rodando no XAMPP
- Confirme as credenciais em `config/config.php`
- Ative a exibição de erros temporariamente no PHP: `display_errors = On` no `php.ini`

**Erro ao importar o banco**
- Confirme que o banco `sysfaa` foi criado antes de importar
- Verifique se a versão do MySQL é 10.4 ou superior

**Upload de PDF não funciona**
- Confirme que a pasta `uploads/fichas/` existe
- No Linux/Mac, verifique as permissões: `chmod 755 uploads/fichas/`

**Erro de mod_rewrite**
- Confirme que `mod_rewrite` está ativo no Apache do XAMPP
