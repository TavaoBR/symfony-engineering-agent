# Security Standards in PHP and Symfony

## 1. Core Security Principles
- Defense in Depth: no single layer is sufficient. Validate inputs, protect HTTP boundaries, verify authorization in application logic, and enforce integrity in the database.
- Principle of Least Privilege: services, components, users, and database connections must operate with the minimum required access rights.
- Zero Trust on Client Input: all data originating from HTTP requests (headers, query parameters, body, cookies, uploads) is untrusted until strictly validated.

## 2. Authentication and Authorization in Symfony
- **Firewalls and Authenticators:**
  - Configure explicit firewalls in `config/packages/security.yaml`.
  - Use modern authenticators extending `AbstractAuthenticator` (e.g., `AccessTokenHandler` for Bearer/JWT tokens, FormLogin, or custom authenticators using Passport and Badges).
  - Password hashing must use Symfony's `PasswordHasher` configured with modern algorithms (Argon2id or Bcrypt with appropriate cost factors).
- **Granular Authorization with Voters:**
  - Do not rely solely on role checks (`ROLE_ADMIN`) for actions on business domain entities.
  - Implement `Voter` (`Symfony\Component\Security\Core\Authorization\Voter\Voter`) to verify whether the specific user has permission over the specific resource instance (direct defense against IDOR/BOLA):
    ```php
    $this->denyAccessUnlessGranted(InvoiceVoter::VIEW, $invoice);
    ```
  - Type attributes and subjects strictly when extending `Voter<string, Invoice>`.

## 3. Mitigating OWASP Top 10 Vulnerabilities
- **SQL Injection (SQLi):**
  - Never construct SQL or DQL by concatenating or interpolating user variables into query strings.
  - Always use named parameters:
    ```php
    // Correct:
    $qb->where('u.email = :email')->setParameter('email', $email);
    // Prohibited:
    $qb->where("u.email = '{$email}'");
    ```
- **Broken Object Level Authorization (IDOR / BOLA):**
  - Every endpoint accepting an ID in the URL path (`/api/invoices/{id}`) must verify that the record belongs to the authenticated tenant/user before returning or mutating data.
- **Cross-Site Scripting (XSS):**
  - In Twig, keep automatic output escaping enabled. Never use the `|raw` filter on user input without strict prior sanitization (`HtmlSanitizerInterface`).
  - In JSON APIs, enforce `Content-Type: application/json` headers and use controlled serializers.
- **Cross-Site Request Forgery (CSRF):**
  - Enable CSRF protection for forms and state-mutating requests that rely on cookie-based authentication.
- **Server-Side Request Forgery (SSRF):**
  - Never make outbound HTTP requests to user-supplied URLs without strictly validating the scheme (only `http`/`https`), resolving DNS, and blocking private/loopback/link-local IP ranges (`127.0.0.1`, `10.0.0.0/8`, `192.168.0.0/16`, `169.254.169.254`).
- **Rate Limiting and Brute Force Defense:**
  - Configure rate limiters using `Symfony\Component\RateLimiter\RateLimiterFactory` on sensitive routes (login, password reset, checkout, email/SMS dispatch).

## 4. Secrets Management and File Handling
- **Secrets Management:**
  - Never commit credentials, API keys, or tokens to Git. Use Symfony Secrets Vault (`php bin/console secrets:set <NAME>`) or container environment variables in production.
- **Secure File Uploads:**
  - Never trust the client-provided file name or MIME type header.
  - Generate a cryptographically random file name (UUID/ULID or random hash).
  - Validate the real MIME type using `finfo` or Symfony Validator constraints (`#[Assert\File(mimeTypes: [...])]`).
  - Store uploads outside the web root (`public/`) and serve them via authenticated controller responses or signed cloud URLs.
- **Safe Error Handling:**
  - In production environments, ensure 500 responses return generic error messages without exposing stack traces, SQL queries, or server credentials.
