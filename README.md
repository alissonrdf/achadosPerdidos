# Sistema de Achados e Perdidos

Este é um sistema web para gerenciar itens encontrados e perdidos, permitindo o cadastro, edição e visualização de itens, categorias e usuários. O sistema é acessível para qualquer pessoa visualizar os itens encontrados, enquanto operações administrativas são restritas a usuários autenticados, com diferentes permissões.

## Funcionalidades

- **CRUD de Itens Encontrados**: Adicionar, editar, listar e excluir itens encontrados.
- **CRUD de Categorias**: Gerenciar categorias de itens, com ícones específicos para cada uma.
- **CRUD de Usuários**: Cadastro e gerenciamento de usuários, com diferentes níveis de permissão (administrador e usuário comum).
- **Autenticação e Controle de Acesso**: Usuários comuns podem gerenciar itens e categorias, enquanto administradores têm acesso completo ao sistema, incluindo o gerenciamento de usuários.
- **Upload e Processamento de Imagens**: Imagens enviadas são processadas e salvas no formato WebP para otimização.

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
│   └── image_utils.php              # Utilitários para processar e salvar imagens no formato WebP
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

## Guia de Uso

1. **Login**: Acesse `login.php` para entrar no sistema.
2. **Dashboard**: Após o login, você será direcionado para `dashboard.php`, onde pode acessar todas as funcionalidades de acordo com suas permissões.
3. **Gerenciamento de Itens**:
   - Acesse a seção "Itens" para adicionar, editar, listar ou excluir itens encontrados.
   - Filtre itens por data e categoria na página inicial (`index.php`).
4. **Gerenciamento de Categorias**: Acesse a seção "Categorias" para gerenciar as categorias dos itens.
5. **Gerenciamento de Usuários**: Administradores podem acessar a seção "Usuários" para gerenciar contas de usuários.

## Boas Práticas de Segurança

- **Senhas**: As senhas são armazenadas utilizando `password_hash()` para garantir a segurança dos dados.
- **Controle de Acesso**: As permissões são verificadas em cada página para garantir que usuários não autorizados não acessem recursos restritos.
- **Configuração de `config.php`**: O arquivo `config.php` é gerado automaticamente durante a instalação. Mantenha este arquivo seguro e restrinja seu acesso.

## Possíveis Melhorias

- **Logs de Atividade**: Implementar uma tabela de logs para registrar atividades dos usuários, como adições e edições de itens, para auditoria e monitoramento.
- **Notificações por E-mail**: Enviar notificações para usuários sobre itens adicionados ou atualizações de status.
- **Suporte a Vários Idiomas**: Permitir que o sistema seja usado em diferentes idiomas.
- **Aplicativo Mobile**: Criar uma versão mobile para facilitar o uso em smartphones.

## Manutenção

1. **Backup do Banco de Dados**: Faça backups regulares do banco de dados para evitar perda de dados.
2. **Atualização de Segurança**: Mantenha o PHP e o MySQL atualizados para corrigir possíveis vulnerabilidades.

## Licença

Este sistema é um projeto acadêmico ou institucional e pode ser modificado conforme necessário para atender aos requisitos específicos da organização.

---