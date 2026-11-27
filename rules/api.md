# HTTP APIs and Contracts

- Discover the API paradigm used in the project: RESTful, JSON:API, API Platform, or custom HTTP handlers.
- Preserving public contracts:
  - Preserve routes, HTTP methods, response envelopes, pagination formats, query filters, status codes, and existing data payloads.
  - For breaking changes, propose versioning strategies (URI path, custom header, or query parameter) and a safe migration path.
- Server-side validation:
  - Never trust client-side validation. Strictly validate all incoming payloads and headers on the server.
  - Return standardized error responses (RFC 7807 / RFC 9457 Problem Details or the project's established format).
- Serialization and data protection:
  - Never expose raw database entities directly as API responses.
  - Use DTOs or Symfony Serializer groups (`#[Groups]`) to explicitly control exposed fields.
  - Prevent leakage of internal database details, stack traces, SQL errors, or file paths in production.
- Pagination, sorting, and filtering:
  - All potentially large collections must be paginated (offset/limit or cursor-based).
  - Enforce a strict maximum page size limit to prevent memory exhaustion and DoS vectors.
- Contract documentation:
  - Keep OpenAPI/Swagger specifications synchronized when API documentation tools are configured in the repository. Do not document imaginary parameters or endpoints that do not exist in code.
