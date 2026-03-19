# SysFAA — Sistema de Gestão de Fichas Hospitalares
## Módulo 1: Banco de Dados + Autenticação

---

## Estrutura de Arquivos (Módulo 1)

```
sysfaa/
├── .htaccess                  ← Segurança Apache
├── sysfaa_banco.sql           ← Script do banco de dados
├── dashboard.php              ← Página principal (pós-login)
│
├── config/
│   ├── config.php             ← Configurações globais
│   └── database.php           ← Conexão PDO (singleton)
│
├── auth/
│   ├── Auth.php               ← Classe de autenticação
│   ├── login.php              ← Tela de login
│   ├── processar_login.php    ← Processa POST do login
│   └── logout.php             ← Encerra sessão
│
├── includes/
│   ├── header.php             ← Navbar + sidebar reutilizável
│   └── footer.php             ← Rodapé reutilizável
│
└── uploads/
    └── fichas/                ← PDFs ficam aqui (criado manualmente)
```

---

## Instalação

### 1. Banco de dados
```sql
-- No phpMyAdmin ou MySQL CLI:
source /caminho/para/sysfaa_banco.sql
```

### 2. Configuração
Edite `config/config.php` e ajuste:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sysfaa');
define('DB_USER', 'seu_usuario_mysql');
define('DB_PASS', 'sua_senha_mysql');
```

### 3. Permissões de pasta
```bash
mkdir -p uploads/fichas
chmod 755 uploads/fichas
```

### 4. Primeiro acesso
- URL: `http://seu-servidor/auth/login.php`
- E-mail: `admin@gmail.com`
- Senha: `admin`
- **⚠️ Trocar a senha no primeiro login!**

---

## Perfis de acesso
| Perfil        | Permissões                                 |
|---------------|--------------------------------------------|
| admin         | Acesso total + painel de administração     |
| administracao | Visualiza, edita  e faz upload de fichas   |
| recepcao      | Apenas visualiza fichas                    |
