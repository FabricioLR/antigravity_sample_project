# 🧩 Contexto do Projeto: Web Storage

O **Web Storage** é uma plataforma de gerenciamento de arquivos em nuvem moderna e segura. Este arquivo serve como a **Fonte da Verdade Técnica** para desenvolvedores e agentes de IA, detalhando a arquitetura, stack e regras de conduta do projeto.

---

## 🚀 Tecnologias e Stack Core

-   **Linguagem**: PHP 8.4 (FPM) em containers Docker.
-   **Servidor Web**: Nginx (Dev: porta 80; Prod: porta 1234 com SSL).
-   **Banco de Dados**: PostgreSQL (Gestão de Metadados e Usuários).
-   **Cloud Integration**: Oracle Cloud Infrastructure (OCI) Object Storage SDK.
-   **Front-end**: HTML5 semântico, CSS3 Moderno (Glassmorphism) e Vanilla JS (ES6+).
-   **Testes**: PHPUnit 11 (Unitário e Integração).
-   **Infraestrutura**: Docker & Docker Compose (Ambientes Isolados Dev/Test/Prod).

---

## 🏗️ Arquitetura e Padrões

O projeto segue uma abordagem modular e orientada a interfaces para garantir flexibilidade de infraestrutura:

1.  **Abstração de Armazenamento**:
    -   `App\Storage\StorageInterface`: Contrato único para operações de arquivo.
    -   `App\Storage\StorageFactory`: Decide o driver via variável `STORAGE_TYPE` (`local` ou `oci`).
    -   `App\Storage\LocalStorage` & `App\Storage\OCIStorage`: Implementações concretas.
2.  **Camada de Negócio (Managers)**:
    -   `App\Auth`: Autenticação, controle de sessões e política de troca de senha.
    -   `App\FileManager`: Orquestra operações de arquivos vinculadas ao contexto do usuário.
    -   `App\UserManager`: Gestão administrativa de membros e permissões.

---

## 📂 Mapa do Projeto (Estrutura Crítica)

-   `public/`: Pontos de entrada web e recursos estáticos (CSS/JS).
-   `src/Config/`: Classes de configuração (`Database.php`, `OCIConfig.php`).
-   `src/Storage/`: Abstração e drivers de persistência.
-   `src/`: Núcleo da lógica de negócio (`Auth.php`, `FileManager.php`, `UserManager.php`).
-   `tests/`: Suíte completa de testes (Bootstrap isolado).
-   `docker/`: Definições customizadas de Dockerfile (Dev vs Prod).
-   `.github/workflows/`: Pipelines de CI (GitHub Actions).
-   `Jenkinsfile`: Automação de CD (Deploy Contínuo).

---

## ⚙️ Variáveis de Ambiente Essenciais

-   **`STORAGE_TYPE`**: Define o driver (`local` ou `oci`).
-   **`STORAGE_ROOT`**: Diretório base para armazenamento local.
-   **`OCI_*`**: Conjunto de credenciais para integração com Oracle Cloud.
-   **`DB_*`**: Parâmetros de conexão com o PostgreSQL.

---

## 🤖 Regras de Desenvolvimento e IA

Para garantir a integridade do código e a eficiência na colaboração (Humano/IA), siga estas diretrizes:

1.  **Testes Obrigatórios**: Nenhuma funcionalidade é considerada "pronta" sem testes unitários correspondentes. Utilize `./run-tests.sh` para validação total.
2.  **Commits Atômicos e Unitários**: Realize commits pequenos e focados em uma única funcionalidade ou correção. Evite commits massivos.
3.  **Padronização de Código (PSR-12)**: Todo código PHP deve seguir rigorosamente os padrões PSR-12 para consistência estilística.
4.  **Sincronização com Documentação**: Qualquer mudança na arquitetura, novas variáveis de ambiente ou novos diretórios **devem** ser refletidos imediatamente neste `project.md` e no `README.md`.
5.  **Ciclo de Branch Dev**: O desenvolvimento ocorre na branch `dev`. O `git push origin dev` **só deve ser realizado após todos os testes passarem** com o comando `./run-tests.sh`. Caso algum teste falhe, as correções devem ser feitas localmente até a aprovação total antes de qualquer sincronização com o GitHub.
6.  **Segurança Pró-ativa**: Nunca utilize credenciais hardcoded. Utilize sempre as abstrações em `src/Config/` que consomem variáveis do `.env`.

> **Nota**: Consulte este arquivo antes de qualquer refatoração para garantir que os limites arquiteturais (como a abstração de storage) sejam respeitados.
