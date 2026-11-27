# Git and Change Management

- Inspect `git status` and `git diff` before editing and before declaring completion.
- Never discard preexisting user changes or uncommitted work.
- Do not perform destructive operations (`git reset --hard`, `git clean -fd`, force push, branch deletion) without explicit user authorization.
- Do not edit files unrelated to the requested task.
- Follow existing commit message formats and branch naming conventions.
- When asked to commit, provide concise, specific messages accurately reflecting the diff.
- Do not append automated co-authorship tags, AI attribution watermarks, or decorative headers.
- Never commit secrets, `.env.local`, temporary cache files, build artifacts, or `vendor/`.
