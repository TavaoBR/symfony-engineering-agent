# Guia Completo de Instalação, Configuração e Execução na sua IA

Este documento é o guia prático passo a passo para instalar, configurar e executar o **Symfony Engineering Agent** no seu ambiente de desenvolvimento e ferramenta de IA favorita (Google Antigravity, Claude Code, Cursor IDE, GitHub Copilot, Windsurf, Cline).

---

## Índice do Guia

1. [Perguntas Fundamentais de Instalação](#1-perguntas-fundamentais-de-instalação)
2. [Pré-Requisitos do Sistema](#2-pré-requisitos-do-sistema)
3. [Instalação Automatizada (Windows, Linux, macOS)](#3-instalação-automatizada-windows-linux-macos)
4. [Instalação Manual por Ferramenta de IA](#4-instalação-manual-por-ferramenta-de-ia)
   - [4.1 Google Antigravity / Gemini CLI](#41-google-antigravity--gemini-cli)
   - [4.2 Claude Code CLI (Anthropic)](#42-claude-code-cli-anthropic)
   - [4.3 Cursor IDE](#43-cursor-ide)
   - [4.4 GitHub Copilot (VS Code / JetBrains)](#44-github-copilot-vs-code--jetbrains)
   - [4.5 Windsurf, Cline, Roo Code](#45-windsurf-cline-roo-code)
5. [Como Chamar os Agents Usando Comandos `/`](#5-como-chamar-os-agents-usando-comandos-)
6. [Catálogo Completo dos Comandos `/`](#6-catálogo-completo-dos-comandos-)
7. [Exemplos Práticos do Dia a Dia](#7-exemplos-práticos-do-dia-a-dia)
8. [Como Atualizar o Ecossistema](#8-como-atualizar-o-ecossistema)
9. [Procedimento de Teste de Instalação](#9-procedimento-de-teste-de-instalação)
10. [Solução de Problemas (Troubleshooting)](#10-solução-de-problemas-troubleshooting)

---

## 1. Perguntas Fundamentais de Instalação

### Basta executar um `git clone` do repositório?
**Não.** O `git clone` apenas baixa os arquivos para o seu disco. Para que a sua IA reconheça os agentes, as skills e os comandos `/`, os arquivos precisam estar no diretório que a ferramenta de IA escaneia (ex: `~/.gemini/config/plugins/` ou `.agents/` para Antigravity; `~/.claude/commands/` para Claude Code; `.cursor/rules/` para Cursor).

### Em qual diretório devo clonar o projeto?
Você pode clonar este repositório em qualquer pasta de sua preferência (ex: `~/tools/symfony-engineering-agent` ou `D:\gow\symfony-engineering-agent`). A partir dele, você executa o instalador (`install.ps1` ou `install.sh`) para registrar os agentes na IA.

### Existe alguma pasta específica que a IA reconhece automaticamente?
Sim! Dependendo da IA:
- **Google Antigravity / Gemini CLI:**
  - *Global:* `~/.gemini/config/plugins/` ou `~/.gemini/config/skills/` (no Windows: `%USERPROFILE%\.gemini\config\`).
  - *Por Projeto:* Pasta `.agents/` ou `.gemini/` na raiz do seu projeto Symfony.
- **Claude Code CLI:**
  - *Global:* `~/.claude/commands/` (no Windows: `%USERPROFILE%\.claude\commands\`).
  - *Por Projeto:* Arquivo `CLAUDE.md` e pasta `.claude/commands/` na raiz do projeto.
- **Cursor IDE:**
  - *Por Projeto:* Pasta `.cursor/rules/` na raiz do projeto.
- **GitHub Copilot:**
  - *Por Projeto:* Pasta `.github/copilot-instructions.md` na raiz do projeto.

### Como a IA identifica e carrega os agentes e Skills?
- **Skills e Comandos `/`:** A IA escaneia pastas contendo arquivos `SKILL.md` com cabeçalho YAML (`--- name: <comando> ---`). Cada skill válida é registrada como um comando `/` executável no chat.
- **Regras e Agentes:** O arquivo `AGENTS.md` funciona como o manifesto central que lista todos os agentes, papéis e matriz de especialidades.

### É necessário reiniciar a ferramenta de IA após a instalação?
- **Claude Code:** Recarrega imediatamente novos comandos ao iniciar uma nova sessão ou digitar `/help`.
- **Antigravity / Cursor:** Na maioria das vezes reconhece dinamicamente; se o comando `/` não aparecer imediatamente no autocomplete do chat, feche e reabra o terminal/janela para atualizar o índice de comandos.

---

## 2. Pré-Requisitos do Sistema

- **PHP 8.2+** instalado no PATH (`php -v`).
- **Composer 2.2+** instalado no PATH (`composer --version`).
- Extensões PHP obrigatórias: `pdo`, `ctype`, `iconv`, `json`, `tokenizer`, `xml`, `mbstring`.
- Git instalado no PATH (`git --version`).

---

## 3. Instalação Automatizada (Windows, Linux, macOS)

Fornecemos scripts de instalação automatizados que configuram tudo com um único comando:

### No Windows (PowerShell)

Abra o PowerShell como seu usuário habitual e execute:

```powershell
# 1. Clone o repositório se ainda não o fez:
git clone https://github.com/your-org/symfony-engineering-agent.git
cd symfony-engineering-agent

# 2. Para instalar GLOBALMENTE na sua máquina (Antigravity + Claude Code):
.\install.ps1 -Global

# 3. OU para instalar diretamente dentro do seu projeto Symfony existente:
.\install.ps1 -TargetProject "C:\caminho\para\seu-projeto-symfony"
```

### No Linux & macOS (Bash)

Abra o terminal e execute:

```bash
# 1. Clone o repositório:
git clone https://github.com/your-org/symfony-engineering-agent.git
cd symfony-engineering-agent
chmod +x install.sh

# 2. Para instalar GLOBALMENTE (Antigravity + Claude Code):
./install.sh --global

# 3. OU para instalar em um projeto Symfony específico:
./install.sh --target-project /caminho/para/seu-projeto-symfony
```

---

## 4. Instalação Manual por Ferramenta de IA

Se você preferir configurar manualmente sem executar o script:

### 4.1 Google Antigravity / Gemini CLI
O ecossistema é um **Plugin nativo** do Antigravity (declarado em `plugin.json`):
1. Copie a pasta inteira do projeto para a pasta global de plugins:
   - Windows: `Copy-Item -Path . -Destination "$env:USERPROFILE\.gemini\config\plugins\symfony-engineering-agent" -Recurse`
   - Linux/macOS: `cp -r . ~/.gemini/config/plugins/symfony-engineering-agent`
2. Para que os comandos de barra (`/symfony`, `/backend`, etc.) fiquem disponíveis no chat globalmente, copie as pastas de `skills/` para a pasta global de skills:
   - Windows: `Copy-Item -Path "skills\*" -Destination "$env:USERPROFILE\.gemini\config\skills\" -Recurse`
   - Linux/macOS: `cp -r skills/* ~/.gemini/config/skills/`

### 4.2 Claude Code CLI (Anthropic)
1. Para habilitar os comandos `/` globalmente em qualquer pasta onde você rodar `claude`:
   - Windows: `Copy-Item -Path ".claude\commands\*.md" -Destination "$env:USERPROFILE\.claude\commands\" -Force`
   - Linux/macOS: `mkdir -p ~/.claude/commands && cp .claude/commands/*.md ~/.claude/commands/`
2. Copie `CLAUDE.md`, `rules/`, `agents/` e `skills/` para a raiz do seu projeto Symfony.

### 4.3 Cursor IDE
1. Na raiz do seu projeto Symfony, crie a pasta `.cursor/rules/`.
2. Copie `.cursor/rules/symfony-engineering-agent.mdc`, `rules/`, `agents/` e `skills/` para dentro do seu projeto.
3. No Cursor, abra as configurações e certifique-se de que **Codebase Indexing** está ativado.

### 4.4 GitHub Copilot
1. Crie a pasta `.github/` no seu projeto Symfony.
2. Copie `.github/copilot-instructions.md` para `.github/copilot-instructions.md`.

---

## 5. Como Chamar os Agents Usando Comandos `/`

Com os arquivos instalados, basta abrir a caixa de mensagem do chat da sua IA e digitar `/`. O autocompletar exibirá a lista de comandos disponíveis.

### Estrutura de Invocação:
```text
/<comando> <sua instrução ou especificação detalhada>
```

Você não precisa decorar classes ou estruturas internas. O comando carrega automaticamente o agente correspondente, injeta as skills especializadas e define as regras de validação.

---

## 6. Catálogo Completo dos Comandos `/`

| Comando | Agente Acionado | Skills Injetadas | Finalidade Principal | Exemplo de Uso |
|:---|:---|:---|:---|:---|
| **/symfony** | `orchestrator` | `architecture-design`, `project-discovery`, `code-review` | Orquestração ponta a ponta de tarefas completas. Monta a equipe de agentes e valida tudo. | `/symfony Implemente um módulo de gestão de clientes com API, banco e testes.` |
| **/architecture** | `symfony-architect` | `architecture-design`, `api-design` | Definição de limites de camadas, interfaces, DTOs e registro de ADRs. | `/architecture Projete a arquitetura do fluxo de cancelamento de pedidos.` |
| **/backend** | `symfony-backend-developer` | `symfony-application`, `testing-quality` | Criação de controllers enxutos (`#[MapRequestPayload]`), services e comandos CLI. | `/backend Crie um CRUD de categorias de produtos com validações e testes.` |
| **/doctrine** | `database-engineer` | `doctrine-persistence`, `database-performance` | Modelagem de entidades, relacionamentos, repositórios e migrations seguras. | `/doctrine Mapeie a entidade Invoice com relacionamento para InvoiceItem e gere a migration.` |
| **/tests** | `quality-engineer` | `testing-quality`, `code-review` | Criação e execução de testes unitários, funcionais (`WebTestCase`) e análise estática (PHPStan). | `/tests Crie testes funcionais cobrindo cenários 201, 422 e 403 do endpoint de pagamentos.` |
| **/security** | `symfony-security-specialist`, `php-security-specialist` | `symfony-security`, `secure-coding` | Implementação de Voters, firewalls, rate limiters e proteção OWASP. | `/security Crie um Voter para garantir que apenas o autor do post possa editá-lo.` |
| **/performance**| `performance-engineer`, `database-performance-specialist`| `performance-profiling`, `database-performance` | Eliminação de N+1 queries com Fetch Joins, índices de banco e profiling. | `/performance O endpoint /api/orders está lento. Elimine as queries N+1 do repositório.` |
| **/audit** | `application-security-auditor`, `security-engineer` | `security-audit`, `security-review` | Auditoria de segurança contra IDOR/BOLA, vazamento de dados e CVEs via `composer audit`. | `/audit Audite os endpoints de /api/invoices verificando riscos de IDOR entre clientes.` |
| **/debug** | `debugger` | `debugging`, `testing-quality` | Análise de stack traces, teste de regressão antes do fix e correção cirúrgica. | `/debug Corrija a exceção de null pointer ao processar pagamentos sem telefone.` |
| **/refactor** | `refactoring-specialist` | `safe-refactoring`, `testing-quality` | Modernização de código legado sem alterar comportamento ou quebrar contratos. | `/refactor Refatore a classe OrderService usando o padrão Strategy e PHP 8.2+.` |
| **/review** | `code-reviewer` | `code-review`, `secure-coding` | Revisão crítica do `git diff` contra anti-patterns de IA, falta de tipagem e falhas. | `/review Revise as alterações do git diff antes de enviarmos para o Pull Request.` |

---

## 7. Exemplos Práticos do Dia a Dia

### Exemplo 1: Implementação Backend Direta
```text
/backend Implemente o endpoint POST /api/v1/coupons para aplicar cupons de desconto.
Receba um DTO CouponRequest validado com o código do cupom e valor.
Retorne um DTO CouponResponse com status HTTP 200.
```

### Exemplo 2: Otimização de Performance
```text
/performance Analise o método findLatestOrders() em OrderRepository.
Identifique consultas N+1 ao carregar os clientes e os itens do pedido e adicione Fetch Joins com addSelect().
```

### Exemplo 3: Auditoria de Segurança
```text
/audit Faça uma varredura completa nos endpoints de /api/projects/{id}.
Verifique se qualquer usuário logado consegue ver projetos de outras empresas e proponha a criação do Voter necessário.
```

### Exemplo 4: Orquestração Automática Completa
```text
/symfony Precisamos criar o módulo de Subscriptions (Assinaturas).
O usuário pode assinar um plano, cancelar e atualizar o cartão.
Envolva o arquiteto para os DTOs, o engenheiro de banco para as tabelas, o desenvolvedor backend para a API e o especialista em segurança para proteger os endpoints.
Execute todos os testes e linters antes de finalizar.
```

---

## 8. Como Atualizar o Ecossistema

Quando houver novas versões, novas skills ou melhorias nos agentes no repositório GitHub:

```bash
# 1. Acesse a pasta onde você clonou o agente:
cd symfony-engineering-agent

# 2. Puxe as atualizações mais recentes:
git pull origin main

# 3. Reexecute o instalador para atualizar os arquivos na sua IA:
# No Windows:
.\install.ps1 -Global
# No Linux/macOS:
./install.sh --global
```

---

## 9. Procedimento de Teste de Instalação

Para confirmar que tudo está instalado e operando perfeitamente:

1. **Teste do Autocomplete:** Abra a caixa de chat da sua IA (Antigravity ou Claude Code) e digite `/`. Verifique se os comandos `/symfony`, `/backend`, `/doctrine`, `/security`, `/performance` aparecem na lista de sugestões.
2. **Teste de Execução Rápida:** Digite no chat:
   ```text
   /backend Qual é a sua responsabilidade e quais skills você utiliza no Symfony Engineering Agent?
   ```
   **Resultado Esperado:** O modelo deve responder assumindo o papel do **Symfony Backend Developer**, citando a skill `skills/symfony-application`, as regras de skinny controllers, tipagem estrita e DTOs com `#[MapRequestPayload]`.
3. **Teste de Orquestração:** Digite no chat:
   ```text
   /symfony Planeje a criação de um endpoint GET /api/healthcheck no Symfony.
   ```
   **Resultado Esperado:** O Orchestrator deve responder com um plano objetivo identificando os testes e linters necessários.

---

## 10. Solução de Problemas (Troubleshooting)

- **Os comandos `/` não aparecem na minha IA:**
  - *No Claude Code:* Verifique se a pasta `~/.claude/commands` contém os arquivos `.md` e reinicie a sessão digitando `/help`.
  - *No Antigravity:* Verifique se as pastas estão em `~/.gemini/config/skills/` ou se o projeto contém `.agents/skills/`. Feche e reabra o painel de chat.
- **A IA diz que não conhece o comando:**
  - Certifique-se de que o caminho dos arquivos não contém caracteres corrompidos e que os arquivos possuem o cabeçalho YAML `--- name: <comando> ---`.
- **Permissão negada ao rodar `install.ps1` no Windows:**
  - Execute no PowerShell: `Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass` e tente novamente.
