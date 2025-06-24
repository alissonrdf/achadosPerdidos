# Sistema de Achados e Perdidos

Este é um sistema web para gerenciar itens encontrados e perdidos, permitindo o cadastro, edição e visualização de itens, categorias e usuários. O sistema é acessível para qualquer pessoa visualizar os itens encontrados, enquanto operações administrativas são restritas a usuários autenticados, com diferentes permissões.

## Funcionalidades

- **CRUD de Itens Encontrados**: Adicionar, editar, listar e excluir itens encontrados.
- **CRUD de Categorias**: Gerenciar categorias de itens, com ícones específicos para cada uma.
- **CRUD de Usuários**: Cadastro e gerenciamento de usuários, com diferentes níveis de permissão (administrador e usuário comum).
- **Autenticação e Controle de Acesso**: Usuários comuns podem gerenciar itens e categorias, enquanto administradores têm acesso completo ao sistema, incluindo o gerenciamento de usuários.
- **Upload e Processamento de Imagens**: Imagens enviadas são processadas e salvas no formato WebP para otimização.
- **Auditoria Detalhada de Logs**: Todas as ações administrativas são registradas em logs detalhados, incluindo o tipo de entidade afetada, alterações realizadas e informações de contexto.
- **Relatórios de Auditoria**: Administradores podem acessar relatórios completos dos logs do sistema, com recursos de filtragem, paginação e exportação para CSV.

## Estrutura de Diretórios

Abaixo está a estrutura de diretórios e arquivos do sistema, com descrições de cada elemento:

```
/achadosPerdidos
├── categorias/                      # CRUD de categorias
│   ├── add_category.php             # Adição de uma nova categoria
│   ├── delete_category.php          # Exclusão lógica de uma categoria
│   ├── edit_category.php            # Edição de uma categoria
│   └── list_categories.php          # Listagem de categorias
│
├── css/
│   └── style.css                    # Arquivo CSS principal para estilização do sistema
│
├── itens/                           # CRUD de itens encontrados
│   ├── add_item.php                 # Adição de um novo item encontrado
│   ├── delete_item.php              # Exclusão lógica de um item encontrado
│   ├── edit_item.php                # Edição de um item encontrado
│   └── list_items.php               # Listagem de itens encontrados
│
├── sql/                             
│   └── database_setup.sql           # Arquivo SQL contendo as tabelas que serão criadas na instalação do sistema
│
├── uploads/                         # Pasta para armazenar imagens enviadas (convertidas para WebP)
│
├── usuarios/                        # CRUD de usuários (acessível apenas para administradores)
│   ├── add_user.php                 # Adição de um novo usuário
│   ├── delete_user.php              # Exclusão lógica de um usuário
│   ├── edit_user.php                # Edição de um usuário
│   └── list_users.php               # Listagem de usuários
│
├── utils/                           # Funções auxiliares do sistema
│   ├── image_utils.php              # Utilitários para processar e salvar imagens no formato WebP
│   └── image_modal.php              # Componente de modal reutilizável para exibição de imagens
│
├── config.php                       # Configurações globais (criado após a instalação do sistema)
├── dashboard.php                    # Painel principal do sistema (acesso condicional com base no papel do usuário)
├── db.php                           # Conexão com o banco de dados
├── index.php                        # Página inicial, listagem de itens encontrados
├── install.php                      # Script de instalação do sistema e configuração inicial
├── login.php                        # Página de login do sistema
├── logout.php                       # Página para deslogar o usuário
└── README.md                        # Documentação e instruções gerais (opcional)
```

## Instalação

1. **Requisitos**: Certifique-se de ter PHP, MySQL e um servidor web (como Apache ou Nginx) configurados.
2. **Configuração do Banco de Dados**:
   - Crie um banco de dados MySQL.
   - Execute o arquivo `sql/database_setup.sql` para criar as tabelas necessárias.
3. **Instalação Automática**:
   - Acesse o arquivo `install.php` no navegador após descompactar o sistema no servidor. Este script configurará o banco de dados e criará um usuário administrador inicial.
   - Após a instalação, exclua o arquivo `install.php` por motivos de segurança.
4. **Configuração do Servidor**:
   - Configure o servidor web para apontar para a pasta do sistema.
   - Certifique-se de que a pasta `uploads/` tenha permissões de gravação para permitir o upload de imagens.

## Configuração de Usuários e Permissões

- **Administrador**: Tem acesso completo ao sistema, incluindo o gerenciamento de outros usuários.
- **Usuário Comum**: Pode gerenciar itens e categorias, mas não tem acesso ao CRUD de usuários.

## Estrutura do Banco de Dados

O arquivo `sql/database_setup.sql` contém todas as instruções para criar as tabelas necessárias, incluindo:
- **Tabela `usuarios`**: Armazena informações de login e permissões dos usuários.
- **Tabela `categorias`**: Armazena categorias dos itens, com ícones padrão.
- **Tabela `itens`**: Armazena os itens encontrados, com referências para as categorias e o usuário que cadastrou o item.
- **Tabela `logs`**: Registra ações executadas no sistema para fins de auditoria, com os seguintes campos principais:
  - `user_id`: Usuário responsável pela ação.
  - `entity_id`: ID da entidade afetada (item, categoria ou usuário).
  - `entity_type`: Tipo da entidade afetada (`item`, `categoria`, `usuario`).
  - `action`: Ação realizada (`create`, `edit`, `delete`, `login`, etc).
  - `reason`: Motivo ou descrição da ação.
  - `changes`: Detalhamento das alterações realizadas (em formato JSON), exceto para senhas, que não são registradas por segurança.
  - `status`: Resultado da ação (`success` ou `error`).
  - `ip_address` e `user_agent`: Informações do ambiente de quem realizou a ação.
  - `created_at`: Data/hora do registro.

### Exemplo de registro de log de edição
```json
{
  "user_id": 2,
  "entity_id": 5,  
  "entity_type": "categoria",
  "action": "edit_category",
  "reason": "Categoria editada",
  "changes": {
    "nome": { "de": "Carteiras", "para": "Carteiras e Bolsas" },
    "imagem_categoria": { "de": "carteira.webp", "para": "carteira_bolsa.webp" }
  },
  "status": "success",
  "ip_address": "127.0.0.1",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
  "created_at": "2025-06-13 14:32:45"
}
```

> **Nota:** Para ações de edição de senha, o campo `changes` registra apenas a indicação da alteração, sem valores.

## Auditoria e Centralização de Logs

Todas as ações administrativas relevantes (criação, edição, exclusão, login, logout, etc.) são registradas na tabela `logs`. Para facilitar manutenção e evitar erros, o sistema centraliza o registro de logs na função `logAction` (em `utils/log_utils.php`).

**Como usar:**

```php
logAction($pdo, [
    'user_id'     => $userId, // obrigatório
    'entity_id'   => $entidadeId, // pode ser null
    'entity_type' => 'item'|'categoria'|'usuario'|null, // tipo da entidade
    'action'      => 'create_item'|'edit_user'|etc, // ação realizada
    'reason'      => 'Descrição da ação',
    'changes'     => json_encode([...]) ou null, // mudanças relevantes
    'status'      => 'success'|'error',
    'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
]);
```

- **Nunca use `registerLog` diretamente nos CRUDs.**
- A função `logAction` faz validação, corte de campos e preenche valores padrão.
- Valores inválidos para `status` serão automaticamente convertidos para `'success'`.
- Campos longos (`ip_address` e `user_agent`) são truncados automaticamente.
- Se precisar alterar a estrutura dos logs, basta modificar a função central.

## Guia de Uso

1. **Login**: Acesse `login.php` para entrar no sistema.
2. **Dashboard**: Após o login, você será direcionado para `dashboard.php`, onde pode acessar todas as funcionalidades de acordo com suas permissões.
3. **Gerenciamento de Itens**:
   - Acesse a seção "Itens" para adicionar, editar, listar ou excluir itens encontrados.
   - Filtre itens por data e categoria na página inicial (`index.php`).
4. **Gerenciamento de Categorias**: Acesse a seção "Categorias" para gerenciar as categorias dos itens.
5. **Gerenciamento de Usuários**: Administradores podem acessar a seção "Usuários" para gerenciar contas de usuários.
6. **Relatórios de Logs**: Administradores podem acessar a seção "Relatórios" para visualizar e exportar relatórios de logs.

## Boas Práticas de Segurança

- **Senhas**: As senhas são armazenadas utilizando `password_hash()` para garantir a segurança dos dados.
- **Controle de Acesso**: As permissões são verificadas em cada página para garantir que usuários não autorizados não acessem recursos restritos.
- **Configuração de `config.php`**: O arquivo `config.php` é gerado automaticamente durante a instalação. Mantenha este arquivo seguro e restrinja seu acesso.

## Possíveis Melhorias

- **Notificações por E-mail**: Enviar notificações para usuários sobre itens adicionados ou atualizações de status.
- **Suporte a Vários Idiomas**: Permitir que o sistema seja usado em diferentes idiomas.
- **Aplicativo Mobile**: Criar uma versão mobile para facilitar o uso em smartphones.

## Manutenção

1. **Backup do Banco de Dados**: Faça backups regulares do banco de dados para evitar perda de dados.
2. **Atualização de Segurança**: Mantenha o PHP e o MySQL atualizados para corrigir possíveis vulnerabilidades.

## Licença

Este sistema é um projeto acadêmico ou institucional e pode ser modificado conforme necessário para atender aos requisitos específicos da organização.

---