# Security Policy

## Supported versions

Security fixes are accepted for the latest released **1.x** line and for `main`.

## Reporting a vulnerability

Please open a private security advisory on the GitHub repository, or email the maintainer listed in `composer.json`.

Do not disclose vulnerabilities publicly until a fix has been released.

## Rendering model

Parity schemas produce HTML attributes and class strings that are escaped before output. Slot content and rich text are treated as trusted Blade/HTML from the theme, matching normal Laravel component semantics. See `docs/security.md` for the audit of unescaped Blade output.
