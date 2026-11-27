# Workflow: External API Integration

1. Identify provider, target service, API version, and consult official documentation.
2. Confirm endpoints, HTTP methods, authentication schemes, scopes, headers, request payloads, response schemas, error codes, rate limits, and webhook requirements in official documentation.
3. Never infer an endpoint or payload structure by analogy with another third-party product.
4. If official documentation does not confirm a detail, mark it explicitly as unconfirmed rather than implementing assumptions as fact.
5. Isolate external HTTP communication inside a dedicated client or gateway service configured via Symfony Dependency Injection (`HttpClientInterface`).
6. Configure explicit connection and response timeouts, safe retry strategies, and idempotency keys per API contracts.
7. Never log API tokens, client secrets, or sensitive request/response data.
8. Implement tests with controlled responses (`MockHttpClient`) and contract validation when applicable.
9. Document required environment variables and configuration placeholders in `.env.example` without real secret values.
10. Never perform real live API calls or financial transactions without explicit user authorization.
