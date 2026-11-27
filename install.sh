#!/usr/bin/env bash
# ==============================================================================
# Symfony Engineering Agent — Multi-Platform Installer (Linux & macOS)
# ==============================================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_PROJECT=""
GLOBAL=false

usage() {
    echo "Usage: $0 [--global] [--target-project /path/to/symfony-app]"
    echo "  --global                  Install globally for Antigravity (~/.gemini/config/) and Claude Code (~/.claude/commands/)"
    echo "  --target-project <path>   Install into a specific Symfony project directory"
    exit 1
}

while [[ "$#" -gt 0 ]]; do
    case $1 in
        --global) GLOBAL=true ;;
        --target-project) TARGET_PROJECT="$2"; shift ;;
        -h|--help) usage ;;
        *) echo "Unknown parameter: $1"; usage ;;
    esac
    shift
done

echo -e "\033[36m==========================================================\033[0m"
echo -e "\033[36m  Symfony Engineering Agent — Multi-Platform Installer\033[0m"
echo -e "\033[36m==========================================================\033[0m"

# 1. Global Installation
if [ "$GLOBAL" = true ] || [ -z "$TARGET_PROJECT" ]; then
    echo -e "\n\033[33m[1/2] Installing globally for Google Antigravity...\033[0m"
    GEMINI_PLUGIN_DIR="$HOME/.gemini/config/plugins/symfony-engineering-agent"
    GEMINI_SKILLS_DIR="$HOME/.gemini/config/skills"

    mkdir -p "$GEMINI_PLUGIN_DIR"
    mkdir -p "$GEMINI_SKILLS_DIR"

    cp "$SCRIPT_DIR/plugin.json" "$GEMINI_PLUGIN_DIR/"
    cp "$SCRIPT_DIR/AGENTS.md" "$GEMINI_PLUGIN_DIR/"
    cp -r "$SCRIPT_DIR/rules" "$GEMINI_PLUGIN_DIR/"
    cp -r "$SCRIPT_DIR/agents" "$GEMINI_PLUGIN_DIR/"
    cp -r "$SCRIPT_DIR/skills" "$GEMINI_PLUGIN_DIR/"
    cp -r "$SCRIPT_DIR/templates" "$GEMINI_PLUGIN_DIR/"
    cp -r "$SCRIPT_DIR/workflows" "$GEMINI_PLUGIN_DIR/"

    # Copy skills into global skills directory for instant /slash availability
    cp -r "$SCRIPT_DIR/skills/"* "$GEMINI_SKILLS_DIR/"
    echo -e "\033[32m  -> Antigravity plugin and slash skills installed in: $GEMINI_PLUGIN_DIR\033[0m"

    echo -e "\n\033[33m[2/2] Installing globally for Claude Code CLI...\033[0m"
    CLAUDE_COMMANDS_DIR="$HOME/.claude/commands"
    mkdir -p "$CLAUDE_COMMANDS_DIR"
    cp "$SCRIPT_DIR/.claude/commands/"*.md "$CLAUDE_COMMANDS_DIR/"
    echo -e "\033[32m  -> Claude Code slash commands installed in: $CLAUDE_COMMANDS_DIR\033[0m"
fi

# 2. Target Project Installation
if [ -n "$TARGET_PROJECT" ]; then
    if [ ! -d "$TARGET_PROJECT" ]; then
        echo -e "\033[31mError: Target project path does not exist: $TARGET_PROJECT\033[0m"
        exit 1
    fi

    echo -e "\n\033[33m[*] Installing into target Symfony project: $TARGET_PROJECT\033[0m"
    AGENTS_DIR="$TARGET_PROJECT/.agents"
    mkdir -p "$AGENTS_DIR"
    cp -r "$SCRIPT_DIR/rules" "$AGENTS_DIR/"
    cp -r "$SCRIPT_DIR/agents" "$AGENTS_DIR/"
    cp -r "$SCRIPT_DIR/skills" "$AGENTS_DIR/"
    cp -r "$SCRIPT_DIR/templates" "$AGENTS_DIR/"
    cp -r "$SCRIPT_DIR/workflows" "$AGENTS_DIR/"
    cp "$SCRIPT_DIR/AGENTS.md" "$TARGET_PROJECT/"

    # Cursor
    mkdir -p "$TARGET_PROJECT/.cursor/rules"
    cp "$SCRIPT_DIR/.cursor/rules/symfony-engineering-agent.mdc" "$TARGET_PROJECT/.cursor/rules/"

    # Claude Code
    cp "$SCRIPT_DIR/CLAUDE.md" "$TARGET_PROJECT/"
    mkdir -p "$TARGET_PROJECT/.claude/commands"
    cp "$SCRIPT_DIR/.claude/commands/"*.md "$TARGET_PROJECT/.claude/commands/"

    # GitHub Copilot
    mkdir -p "$TARGET_PROJECT/.github"
    cp "$SCRIPT_DIR/.github/copilot-instructions.md" "$TARGET_PROJECT/.github/"

    echo -e "\033[32m  -> Successfully configured target project!\033[0m"
fi

echo -e "\n\033[32m==========================================================\033[0m"
echo -e "\033[32m  Installation Completed Successfully!\033[0m"
echo -e "  Available slash commands in your AI chat:"
echo -e "   /symfony      - Orchestrator (Full task delivery)"
echo -e "   /architecture - Symfony Architect (DTOs, clean architecture)"
echo -e "   /backend      - Backend Developer (Controllers, services)"
echo -e "   /doctrine     - Database Engineer (Entities, migrations)"
echo -e "   /tests        - Quality Engineer (WebTestCase, PHPStan)"
echo -e "   /security     - Security Specialists (Voters, firewalls)"
echo -e "   /performance  - Performance Specialists (N+1, indexes)"
echo -e "   /audit        - Application Security Auditor (IDOR, CVEs)"
echo -e "   /debug        - Debugger (Reproduction & fixes)"
echo -e "   /refactor     - Refactoring Specialist (Modernization)"
echo -e "   /review       - Code Reviewer (Diff & anti-pattern check)"
echo -e "\033[32m==========================================================\033[0m"
