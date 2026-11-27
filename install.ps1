<#
.SYNOPSIS
    Installs the Symfony Engineering Agent into your local AI environment (Google Antigravity, Claude Code, Cursor, Copilot).
.DESCRIPTION
    Supports global machine installation (Antigravity & Claude Code) or target project integration.
.PARAMETER TargetProject
    Optional. Path to an existing Symfony application directory to install the agent into.
.PARAMETER Global
    Optional switch. Installs the agent globally for Google Antigravity (~/.gemini/config/) and Claude Code (~/.claude/commands/).
.EXAMPLE
    .\install.ps1 -Global
.EXAMPLE
    .\install.ps1 -TargetProject "D:\projects\my-symfony-app"
#>
param(
    [string]$TargetProject = "",
    [switch]$Global = $false
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host '==========================================================' -ForegroundColor Cyan
Write-Host "  Symfony Engineering Agent — Multi-Platform Installer" -ForegroundColor Cyan
Write-Host '==========================================================' -ForegroundColor Cyan

# 1. Global Installation (Antigravity & Claude Code)
if ($Global -or ([string]::IsNullOrEmpty($TargetProject))) {
    Write-Host "`n[1/2] Installing globally for Google Antigravity..." -ForegroundColor Yellow
    $GeminiPluginDir = Join-Path $env:USERPROFILE ".gemini\config\plugins\symfony-engineering-agent"
    $GeminiSkillsDir = Join-Path $env:USERPROFILE ".gemini\config\skills"

    # Create destination directories
    New-Item -ItemType Directory -Path $GeminiPluginDir -Force | Out-Null
    New-Item -ItemType Directory -Path $GeminiSkillsDir -Force | Out-Null

    # Copy plugin files
    Copy-Item -Path (Join-Path $ScriptDir "plugin.json") -Destination $GeminiPluginDir -Force
    Copy-Item -Path (Join-Path $ScriptDir "AGENTS.md") -Destination $GeminiPluginDir -Force
    Copy-Item -Path (Join-Path $ScriptDir "rules") -Destination $GeminiPluginDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "agents") -Destination $GeminiPluginDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "skills") -Destination $GeminiPluginDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "templates") -Destination $GeminiPluginDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "workflows") -Destination $GeminiPluginDir -Recurse -Force

    # Copy slash command skills directly to global skills folder for instant /slash availability
    Get-ChildItem -Path (Join-Path $ScriptDir "skills") -Directory | ForEach-Object {
        $DestSkill = Join-Path $GeminiSkillsDir $_.Name
        Copy-Item -Path $_.FullName -Destination $DestSkill -Recurse -Force
    }
    Write-Host "  -> Antigravity plugin and slash command skills installed in: $GeminiPluginDir" -ForegroundColor Green

    Write-Host "`n[2/2] Installing globally for Claude Code CLI..." -ForegroundColor Yellow
    $ClaudeCommandsDir = Join-Path $env:USERPROFILE ".claude\commands"
    New-Item -ItemType Directory -Path $ClaudeCommandsDir -Force | Out-Null

    Get-ChildItem -Path (Join-Path $ScriptDir ".claude\commands\*.md") | ForEach-Object {
        Copy-Item -Path $_.FullName -Destination $ClaudeCommandsDir -Force
    }
    Write-Host "  -> Claude Code slash commands installed in: $ClaudeCommandsDir" -ForegroundColor Green
}

# 2. Target Project Installation
if (-not [string]::IsNullOrEmpty($TargetProject)) {
    if (-not (Test-Path $TargetProject)) {
        Write-Error "Target project path does not exist: $TargetProject"
        exit 1
    }

    Write-Host "`n[*] Installing into target Symfony project: $TargetProject" -ForegroundColor Yellow

    # Copy rules, agents, skills into .agents/ or project root
    $AgentsDir = Join-Path $TargetProject ".agents"
    New-Item -ItemType Directory -Path $AgentsDir -Force | Out-Null
    Copy-Item -Path (Join-Path $ScriptDir "rules") -Destination $AgentsDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "agents") -Destination $AgentsDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "skills") -Destination $AgentsDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "templates") -Destination $AgentsDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "workflows") -Destination $AgentsDir -Recurse -Force
    Copy-Item -Path (Join-Path $ScriptDir "AGENTS.md") -Destination $TargetProject -Force

    # Cursor setup
    $CursorRulesDir = Join-Path $TargetProject ".cursor\rules"
    New-Item -ItemType Directory -Path $CursorRulesDir -Force | Out-Null
    Copy-Item -Path (Join-Path $ScriptDir ".cursor\rules\symfony-engineering-agent.mdc") -Destination $CursorRulesDir -Force

    # Claude Code project setup
    Copy-Item -Path (Join-Path $ScriptDir "CLAUDE.md") -Destination $TargetProject -Force
    $TargetClaudeCommands = Join-Path $TargetProject ".claude\commands"
    New-Item -ItemType Directory -Path $TargetClaudeCommands -Force | Out-Null
    Copy-Item -Path (Join-Path $ScriptDir ".claude\commands\*.md") -Destination $TargetClaudeCommands -Force

    # GitHub Copilot setup
    $GithubDir = Join-Path $TargetProject ".github"
    New-Item -ItemType Directory -Path $GithubDir -Force | Out-Null
    Copy-Item -Path (Join-Path $ScriptDir ".github\copilot-instructions.md") -Destination $GithubDir -Force

    Write-Host "  -> Successfully configured target project!" -ForegroundColor Green
}

Write-Host "`n==========================================================" -ForegroundColor Cyan
Write-Host "  Installation Completed Successfully!" -ForegroundColor Green
Write-Host "  Available slash commands in your AI chat:" -ForegroundColor White
Write-Host '   /symfony      - Orchestrator (Full task delivery)' -ForegroundColor Gray
Write-Host '   /architecture - Symfony Architect (DTOs, clean architecture)' -ForegroundColor Gray
Write-Host '   /backend      - Backend Developer (Controllers, services)' -ForegroundColor Gray
Write-Host '   /doctrine     - Database Engineer (Entities, migrations)' -ForegroundColor Gray
Write-Host '   /tests        - Quality Engineer (WebTestCase, PHPStan)' -ForegroundColor Gray
Write-Host '   /security     - Security Specialists (Voters, firewalls)' -ForegroundColor Gray
Write-Host '   /performance  - Performance Specialists (N+1, indexes)' -ForegroundColor Gray
Write-Host '   /audit        - Application Security Auditor (IDOR, CVEs)' -ForegroundColor Gray
Write-Host '   /debug        - Debugger (Reproduction & fixes)' -ForegroundColor Gray
Write-Host '   /refactor     - Refactoring Specialist (Modernization)' -ForegroundColor Gray
Write-Host '   /review       - Code Reviewer (Diff & anti-pattern check)' -ForegroundColor Gray
Write-Host '==========================================================' -ForegroundColor Cyan
