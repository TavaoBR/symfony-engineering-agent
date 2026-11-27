---
name: secure-coding
description: Mitigate OWASP Top 10 vulnerabilities, enforce 100% parameterized queries, and sanitize inputs in PHP.
---

# Skill: PHP Secure Coding (Secure Coding)

## 1. Objective and Responsibility
Apply defensive programming standards in PHP 8.2+ to systematically prevent OWASP Top 10 vulnerabilities (SQLi, XSS, SSRF, Command Injection, Path Traversal), handling data, uploads, and cryptography with proven security.

## 2. Scope and Limitations
- **Scope:** Input sanitization, contextual output escaping, strict database parameterization, secure file uploads, SSRF prevention in HTTP clients, path manipulation defenses, and secret management.
- **Limitations:** Does not configure framework firewalls or voters (delegated to `symfony-security`).

## 3. Required Technical Knowledge
- Strict typing and validation with PHP 8.2+ and Symfony Validator.
- HTML sanitization using `Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface`.
- Native cryptographic primitives: `random_bytes()`, `hash_equals()`, `password_hash()`, `sodium_*`.
- File and stream security: `finfo`, `basename()`, `realpath()`.
- Secure HTTP client usage (`Symfony\Contracts\HttpClient\HttpClientInterface`) with controlled DNS resolution.

## 4. Development Standards and Conventions
- Every PHP file must begin with `declare(strict_types=1);`.
- Use immutable DTOs (`readonly class`) to parse and validate user inputs before passing to domain services.
- Never use `eval()`, `create_function()`, or `unserialize()` on untrusted input (use strict `json_decode()`).
- Never invoke shell execution functions (`exec`, `passthru`, `system`, `shell_exec`, backticks).

## 5. Architectural Rules and Code Organization
- Input sanitization occurs at the application boundary (DTO / Validator).
- Output escaping occurs at the rendering boundary (Twig, JSON encoder).
- File upload handlers reside in `src/Service/File/` and check target paths against canonical whitelists.

## 6. PHP and Symfony Best Practices
- **SSRF Defense:** Before dispatching outbound HTTP requests to user-provided URLs, verify that the resolved IP does not belong to private IP ranges (RFC 1918: `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`), loopback (`127.0.0.0/8`, `::1`), or link-local (`169.254.169.254`).
- **Path Traversal Defense:** Use `basename()` on input filenames and verify that `realpath($destinationPath)` starts with the canonical storage root path.
- **Secure Uploads:** Extract real MIME types using `finfo_file()` and rename files with random UUIDs stored outside web document roots.

## 7. Examples of Correct and Incorrect Implementations

### Example 1: Preventing Path Traversal in File Downloads
**Incorrect (Vulnerable to Path Traversal):**
```php
// Incorrect: Allows reading system files via "../../etc/passwd" or "../.env"
#[Route('/download', methods: ['GET'])]
public function download(Request $request): BinaryFileResponse
{
    $filename = $request->query->get('file');
    return new BinaryFileResponse('/var/app/storage/' . $filename);
}
```

**Correct (Canonical confinement and directory whitelisting):**
```php
#[Route('/download', methods: ['GET'])]
public function download(Request $request): BinaryFileResponse
{
    $filename = $request->query->get('file');
    if (!is_string($filename) || empty($filename)) {
        throw new BadRequestHttpException('Invalid file name.');
    }

    $safeFilename = basename($filename);
    $storageDir = realpath('/var/app/storage');

    if ($storageDir === false) {
        throw new \RuntimeException('Storage directory unavailable.');
    }

    $filePath = realpath($storageDir . DIRECTORY_SEPARATOR . $safeFilename);

    // Verify the file exists and is strictly confined within the storage directory
    if ($filePath === false || !str_starts_with($filePath, $storageDir . DIRECTORY_SEPARATOR)) {
        throw new NotFoundHttpException('File not found.');
    }

    return new BinaryFileResponse($filePath);
}
```

### Example 2: Preventing SSRF in Outbound HTTP Requests
**Incorrect (Vulnerable to SSRF):**
```php
// Incorrect: Allows attacker to access cloud metadata "http://169.254.169.254/latest/meta-data/"
public function fetchWebhook(string $userProvidedUrl): string
{
    return file_get_contents($userProvidedUrl);
}
```

**Correct (Scheme validation and private IP range blocking):**
```php
public function fetchWebhook(string $userProvidedUrl, HttpClientInterface $httpClient): string
{
    $parsed = parse_url($userProvidedUrl);
    if (!isset($parsed['scheme'], $parsed['host']) || !in_array($parsed['scheme'], ['http', 'https'], true)) {
        throw new \InvalidArgumentException('Invalid URL scheme. Only HTTP and HTTPS are permitted.');
    }

    $host = $parsed['host'];
    $ip = gethostbyname($host);

    // Verify resolved IP is public
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        throw new \SecurityException('Access to private or reserved IP ranges is strictly prohibited.');
    }

    $response = $httpClient->request('GET', $userProvidedUrl, [
        'timeout' => 5,
        'max_redirects' => 0, // Prevent redirecting into internal network
    ]);

    return $response->getContent();
}
```

## 8. Technical Decision Criteria
- **Secrets Management:** Never store credentials in Git. Use `php bin/console secrets:set <KEY>` for encrypted secret storage.
- **Input Validation:** Use Symfony Validator constraints on DTOs before passing data to services.

## 9. Validation and Testing Procedures
- Unit tests with malicious payloads (SQLi, XSS `<script>`, `../../` traversal).
- Static security analysis:
  ```bash
  composer audit
  ./vendor/bin/phpstan analyse -l 8 src
  ```

## 10. Error Handling and Exceptions
- Exceptions must never leak internal paths, stack traces, or raw queries in production.
- Log security incidents at `CRITICAL` or `ALERT` level without logging user passwords or tokens.

## 11. Objective Quality and Completion Criteria
- `composer audit` passes with zero known CVEs.
- Zero shell execution functions present in the codebase.
- 100% of inputs validated with typed constraints and outputs properly escaped.
