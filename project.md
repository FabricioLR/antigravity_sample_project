# Resumo do Projeto: Web Storage

O **Web Storage** é uma plataforma de gerenciamento de arquivos em nuvem moderna e segura. Ela fornece aos usuários um ambiente privado para realizar upload, download, edição, renomeação e exclusão de documentos e dados pessoais. A interface possui um design moderno baseado em "Glassmorphism" com suporte a dark/light mode.

## Tecnologias e Stack

- **Back-end**: PHP 8.4 (FPM) executando dentro de containers.
- **Servidor Web**: Nginx (ambiente de desenvolvimento via porta 80; produção via porta 1234 com SSL habilitado).
- **Banco de Dados**: PostgreSQL.
- **Front-end**: HTML5 semântico, CSS3 Moderno (Grid/Flexbox) e Javascript (Vanilla ES6+).
- **Testes**: PHPUnit para testes unitários.
- **Infraestrutura/Virtualização**: Docker e Docker Compose (com suporte a ambientes de desenvolvimento, teste e produção).

## Regras de Desenvolvimento

1. **Testes Obrigatórios**: Sempre criar testes para novas implementações.
2. **Commits Automáticos**: Sempre fazer commit das alterações caso todos os testes passem com sucesso.
3. **Desenvolvimento em Branch Dev**: Todo o desenvolvimento deve ser realizado na branch `dev`. Após cada commit local, deve-se realizar o `git push origin dev` imediatamente.

> **Nota**: Este arquivo serve como referência principal da stack e da arquitetura base. Deve ser consultado antes de propor ou realizar qualquer nova implementação na aplicação para garantir consistência tecnológica.
