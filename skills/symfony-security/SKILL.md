---
name: symfony-security
description: Configure Symfony security firewalls, authenticators, granular resource Voters, and rate limiters.
---

# Skill: Symfony Framework Security (Symfony Security)

## 1. Objective and Responsibility
Design, implement, and audit native authentication, granular authorization, transport hardening, and perimeter defense in the Symfony framework (6.4 / 7.x+), ensuring strict access control and abuse protection across APIs and web applications.

## 2. Scope and Limitations
- **Scope:** Firewall configuration in `security.yaml`, custom authenticators, token handling (`AccessTokenHandler`), domain authorization voters, rate limiting (`RateLimiter`), CSRF protection, secure cookies, and role hierarchies.
- **Limitations:** Does not replace low-level PHP input sanitization (delegated to `secure-coding`) or organizational threat modeling (delegated to `security-engineer`).

## 3. Required Technical Knowledge
- Symfony Security Component: Passport architecture, Badges (`UserBadge`, `PasswordAuthenticatedUserBadge`, `CustomCredentialsBadge`), Listeners, and Handlers.
- Implementation of `Symfony\Component\Security\Core\Authorization\Voter\Voter`.
- Rate Limiting via `Symfony\Component\RateLimiter\RateLimiterFactory`.
- Password hashing with `Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface`.
- HTTP Security Headers: Content-Security-Policy (CSP), Strict-Transport-Security (HSTS), X-Content-Type-Options, X-Frame-Options.

## 4. Development Standards and Conventions
- Voters must extend `Symfony\Component\Security\Core\Authorization\Voter\Voter` specifying generic types: `extends Voter<string, MyEntity>`.
- Use public constants on Voters for permission attributes: `public const string VIEW = 'VIEW'; public const string EDIT = 'EDIT';`.
- In controllers, invoke `$this->denyAccessUnlessGranted(OrderVoter::VIEW, $order);` or the attribute `#[IsGranted(OrderVoter::VIEW, subject: 'order')]`.
- API firewalls must be configured with `stateless: true`.

## 5. Architectural Rules and Code Organization
- Voters reside in `src/Security/Voter/`.
- Authenticators reside in `src/Security/Authenticator/`.
- User Providers and Handlers reside in `src/Security/`.
- Never inject `EntityManagerInterface` into a Voter if the required relation can be reached directly from the `$subject`. Keep Voters fast and in-memory.

## 6. PHP and Symfony Best Practices
- **Rate Limiting Authentication:** Apply composite rate limiters (by client IP and username) on login routes to mitigate brute force and credential stuffing attacks.
- **Session and Token Cookies:** Configure `secure: auto`, `httponly: true`, and `samesite: lax` or `strict` in `config/packages/framework.yaml`.
- **Prevent User Enumeration:** Return identical generic error messages for "user not found" and "invalid password" (e.g., "Invalid credentials").

## 7. Examples of Correct and Incorrect Implementations

### Example: Authorization Voter (IDOR Defense)
**Incorrect (Fragile role-only check in Controller):**
```php
// Incorrect: Any authenticated user can access any invoice by guessing the ID
#[Route('/api/invoices/{id}', methods: ['GET'])]
public function getInvoice(Invoice $invoice): JsonResponse
{
    // Security flaw: checks only role, not whether the invoice belongs to the user!
    $this->denyAccessUnlessGranted('ROLE_USER');
    return $this->json($invoice);
}
```

**Correct (Granular Voter checking entity ownership):**
```php
namespace App\Security\Voter;

use App\Entity\Invoice;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Invoice>
 */
final class InvoiceVoter extends Voter
{
    public const string VIEW = 'INVOICE_VIEW';
    public const string EDIT = 'INVOICE_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof Invoice;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Invoice $invoice */
        $invoice = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($invoice, $user),
            self::EDIT => $this->canEdit($invoice, $user),
            default => false,
        };
    }

    private function canView(Invoice $invoice, User $user): bool
    {
        // Admins have global access
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Users can only view invoices belonging to their customer account
        return $invoice->getCustomer()?->getUser()?->getId() === $user->getId();
    }

    private function canEdit(Invoice $invoice, User $user): bool
    {
        return $this->canView($invoice, $user) && $invoice->isDraft();
    }
}
```

And in the Controller:
```php
#[Route('/api/invoices/{id}', methods: ['GET'])]
public function getInvoice(Invoice $invoice): JsonResponse
{
    $this->denyAccessUnlessGranted(InvoiceVoter::VIEW, $invoice);
    return $this->json($invoice, context: ['groups' => ['invoice:read']]);
}
```

## 8. Technical Decision Criteria
- **`access_control` in `security.yaml` vs `Voter`:**
  - Use `access_control` for macro route boundaries (e.g., `^/admin` requires `ROLE_ADMIN`, `^/api` requires `IS_AUTHENTICATED_FULLY`).
  - Use `Voter` for any authorization involving specific entities, business rules, resource states, or tenant ownership.
- **When to use RateLimiter:**
  - Mandatory on: login, password reset, email verification, checkout, and support contact endpoints.

## 9. Validation and Testing Procedures
- Write functional tests using `WebTestCase` covering 3 mandatory scenarios for each protected endpoint:
  1. Unauthenticated request -> Must return HTTP 401 Unauthorized.
  2. User A requesting User B's resource -> Must return HTTP 403 Forbidden.
  3. User A requesting User A's resource -> Must return HTTP 200 OK.
- Run targeted tests:
  ```bash
  php bin/phpunit tests/Controller/InvoiceControllerTest.php
  ```

## 10. Error Handling and Exceptions
- `AccessDeniedException` must be intercepted and formatted into RFC 7807/9457 JSON with status 403:
  ```json
  {
    "type": "https://tools.ietf.org/html/rfc9110#section-15.5.4",
    "title": "Access Denied",
    "status": 403,
    "detail": "You do not have permission to access this invoice."
  }
  ```

## 11. Objective Quality and Completion Criteria
- Zero state-mutating or sensitive endpoints exposed without authentication or voters.
- `security.yaml` validated via `php bin/console debug:firewall`.
- Negative authorization tests passing with 100% coverage.
