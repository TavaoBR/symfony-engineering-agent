<?php

declare(strict_types=1);

namespace App\Tests\Contract;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * Trait OpenApiContractTestTrait
 *
 * Provides automated schema regression and contract verification for Symfony WebTestCase.
 * Validates actual HTTP response status codes, headers, and JSON payloads against OpenAPI 3.0 / 3.1 specifications.
 * Detects breaking contract changes, missing required fields, type mismatches, and undocumented properties.
 */
trait OpenApiContractTestTrait
{
    /**
     * In-memory cache of parsed OpenAPI specifications.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $cachedOpenApiSpecs = [];

    /**
     * Asserts that the HTTP response from KernelBrowser strictly matches the OpenAPI specification.
     *
     * @param KernelBrowser $client The test HTTP client after executing request()
     * @param string $path The API route path (e.g. '/api/v1/projects' or '/api/v1/projects/1')
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param int|null $expectedStatusCode Optional explicit expected status code
     * @param string|null $specPath Custom path to openapi.yaml or openapi.json file
     */
    protected function assertResponseMatchesOpenApi(
        KernelBrowser $client,
        string $path,
        string $method = 'GET',
        ?int $expectedStatusCode = null,
        ?string $specPath = null
    ): void {
        $response = $client->getResponse();
        $actualStatusCode = $response->getStatusCode();

        if (null !== $expectedStatusCode) {
            $this->assertSame(
                $expectedStatusCode,
                $actualStatusCode,
                sprintf('HTTP status code mismatch for [%s %s]. Expected %d, got %d.', strtoupper($method), $path, $expectedStatusCode, $actualStatusCode)
            );
        }

        $spec = $this->loadOpenApiSpecification($specPath);
        $methodLower = strtolower($method);

        // 1. Locate matching path in OpenAPI spec
        $specPaths = $spec['paths'] ?? [];
        $matchedPathPattern = $this->matchOpenApiPathPattern($path, array_keys($specPaths));

        if (null === $matchedPathPattern) {
            $this->fail(sprintf(
                'OpenAPI Contract Error: Path [%s] is not documented in the OpenAPI specification. Documented paths: %s',
                $path,
                implode(', ', array_keys($specPaths))
            ));
        }

        $pathItem = $specPaths[$matchedPathPattern];
        if (!isset($pathItem[$methodLower])) {
            $this->fail(sprintf(
                'OpenAPI Contract Error: Method [%s] for path [%s] (pattern: %s) is not defined in the OpenAPI specification.',
                strtoupper($method),
                $path,
                $matchedPathPattern
            ));
        }

        $operation = $pathItem[$methodLower];
        $responses = $operation['responses'] ?? [];

        // 2. Locate response definition by status code or 'default'
        $statusCodeStr = (string) $actualStatusCode;
        $responseSpec = $responses[$statusCodeStr] ?? $responses['default'] ?? null;

        if (null === $responseSpec) {
            $documentedCodes = implode(', ', array_keys($responses));
            $this->fail(sprintf(
                'OpenAPI Contract Violation: Status code %d is not documented for [%s %s]. Documented response codes: [%s].',
                $actualStatusCode,
                strtoupper($method),
                $matchedPathPattern,
                $documentedCodes
            ));
        }

        // Resolve $ref if response itself is a reference
        if (isset($responseSpec['$ref'])) {
            $responseSpec = $this->resolveOpenApiRef($spec, $responseSpec['$ref']);
        }

        // 3. Handle 204 No Content
        if (204 === $actualStatusCode) {
            $content = (string) $response->getContent();
            $this->assertEmpty($content, sprintf('Expected empty body for 204 No Content on [%s %s], got payload: %s', strtoupper($method), $path, $content));
            return;
        }

        // 4. Validate Content-Type header
        $contentSpecs = $responseSpec['content'] ?? [];
        if (empty($contentSpecs)) {
            // No content body defined for this response
            return;
        }

        $actualContentType = (string) $response->headers->get('Content-Type', '');
        $matchedContentType = null;
        $schema = null;

        foreach ($contentSpecs as $contentType => $mediaTypeObject) {
            if (str_contains($actualContentType, $contentType)) {
                $matchedContentType = $contentType;
                $schema = $mediaTypeObject['schema'] ?? null;
                break;
            }
        }

        if (null === $matchedContentType) {
            $documentedTypes = implode(', ', array_keys($contentSpecs));
            $this->fail(sprintf(
                'OpenAPI Contract Violation: Content-Type [%s] on [%s %s] does not match any documented media type. Expected one of: [%s].',
                $actualContentType,
                strtoupper($method),
                $matchedPathPattern,
                $documentedTypes
            ));
        }

        if (null === $schema) {
            return;
        }

        // 5. Parse and validate JSON body
        $rawContent = (string) $response->getContent();
        $this->assertJson($rawContent, sprintf('Response body for [%s %s] must be valid JSON.', strtoupper($method), $path));

        /** @var mixed $data */
        $data = json_decode($rawContent, true, 512, JSON_THROW_ON_ERROR);

        $this->validateDataAgainstOpenApiSchema($data, $schema, $spec, sprintf('[%s %s] -> body', strtoupper($method), $path));
    }

    /**
     * Asserts that an error response strictly conforms to RFC 7807 / RFC 9457 Problem Details schema.
     */
    protected function assertProblemDetailsMatchesOpenApi(
        KernelBrowser $client,
        string $path,
        string $method = 'GET',
        int $expectedStatusCode = 422,
        ?string $specPath = null
    ): void {
        $response = $client->getResponse();
        $this->assertSame($expectedStatusCode, $response->getStatusCode(), sprintf('Expected Problem Details status %d, got %d.', $expectedStatusCode, $response->getStatusCode()));

        $contentType = (string) $response->headers->get('Content-Type', '');
        $this->assertTrue(
            str_contains($contentType, 'application/problem+json') || str_contains($contentType, 'application/json'),
            sprintf('Content-Type must be application/problem+json or application/json, got: %s', $contentType)
        );

        $raw = (string) $response->getContent();
        $this->assertJson($raw);
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        // Standard RFC 7807 attributes
        $this->assertArrayHasKey('type', $data, 'Problem Details must contain "type" URI string.');
        $this->assertArrayHasKey('title', $data, 'Problem Details must contain "title" string.');
        $this->assertArrayHasKey('status', $data, 'Problem Details must contain "status" integer.');
        $this->assertSame($expectedStatusCode, $data['status'], 'Problem Details "status" property must match HTTP status code.');

        if (422 === $expectedStatusCode || 400 === $expectedStatusCode) {
            $hasViolations = isset($data['violations']) || isset($data['invalid_params']) || isset($data['errors']);
            $this->assertTrue($hasViolations, 'Validation Problem Details should contain "violations", "invalid_params", or "errors" property.');
        }

        // Validate against OpenAPI if spec is present
        $this->assertResponseMatchesOpenApi($client, $path, $method, $expectedStatusCode, $specPath);
    }

    /**
     * Recursively validates a PHP data structure (array/scalar) against an OpenAPI schema array.
     */
    protected function validateDataAgainstOpenApiSchema(
        mixed $data,
        array $schema,
        array $spec,
        string $breadcrumb
    ): void {
        // 1. Resolve $ref if present
        if (isset($schema['$ref'])) {
            $schema = $this->resolveOpenApiRef($spec, (string) $schema['$ref']);
        }

        // 2. Handle anyOf / oneOf / allOf
        if (isset($schema['allOf']) && is_array($schema['allOf'])) {
            foreach ($schema['allOf'] as $index => $subSchema) {
                $this->validateDataAgainstOpenApiSchema($data, $subSchema, $spec, $breadcrumb . ".allOf[{$index}]");
            }
            return;
        }

        // 3. Determine expected type
        $expectedType = $schema['type'] ?? null;
        $nullable = (bool) ($schema['nullable'] ?? false);

        if (null === $data) {
            if ($nullable) {
                return;
            }
            $this->fail(sprintf('OpenAPI Schema Violation: Null value received at %s, but field is not nullable.', $breadcrumb));
        }

        if (null === $expectedType && isset($schema['properties'])) {
            $expectedType = 'object';
        }

        switch ($expectedType) {
            case 'object':
                $this->assertIsArray($data, sprintf('OpenAPI Schema Violation: Expected object at %s, got %s.', $breadcrumb, gettype($data)));

                // Check required fields
                $requiredFields = (array) ($schema['required'] ?? []);
                foreach ($requiredFields as $reqField) {
                    $this->assertArrayHasKey(
                        $reqField,
                        $data,
                        sprintf('OpenAPI Schema Violation: Missing required property "%s" at %s.', $reqField, $breadcrumb)
                    );
                }

                // Check defined properties
                $properties = (array) ($schema['properties'] ?? []);
                foreach ($properties as $propName => $propSchema) {
                    if (array_key_exists($propName, $data)) {
                        $this->validateDataAgainstOpenApiSchema(
                            $data[$propName],
                            $propSchema,
                            $spec,
                            $breadcrumb . "->{$propName}"
                        );
                    }
                }

                // Check additionalProperties: false
                $additionalProperties = $schema['additionalProperties'] ?? true;
                if (false === $additionalProperties) {
                    $extraKeys = array_diff(array_keys($data), array_keys($properties));
                    $this->assertEmpty(
                        $extraKeys,
                        sprintf(
                            'OpenAPI Contract Breach: Undocumented properties [%s] detected at %s with additionalProperties=false.',
                            implode(', ', $extraKeys),
                            $breadcrumb
                        )
                    );
                }
                break;

            case 'array':
                $this->assertIsArray($data, sprintf('OpenAPI Schema Violation: Expected array at %s, got %s.', $breadcrumb, gettype($data)));

                if (isset($schema['items']) && is_array($schema['items'])) {
                    $itemSchema = $schema['items'];
                    foreach ($data as $idx => $item) {
                        $this->validateDataAgainstOpenApiSchema(
                            $item,
                            $itemSchema,
                            $spec,
                            $breadcrumb . "[{$idx}]"
                        );
                    }
                }
                break;

            case 'string':
                $this->assertIsString($data, sprintf('OpenAPI Schema Violation: Expected string at %s, got %s.', $breadcrumb, gettype($data)));
                if (isset($schema['format'])) {
                    $this->validateStringFormat($data, (string) $schema['format'], $breadcrumb);
                }
                if (isset($schema['enum']) && is_array($schema['enum'])) {
                    $this->assertContains(
                        $data,
                        $schema['enum'],
                        sprintf('OpenAPI Schema Violation: Value "%s" at %s not in enum [%s].', $data, $breadcrumb, implode(', ', $schema['enum']))
                    );
                }
                break;

            case 'integer':
                $this->assertIsInt($data, sprintf('OpenAPI Schema Violation: Expected integer at %s, got %s.', $breadcrumb, gettype($data)));
                break;

            case 'number':
                $isNumber = is_float($data) || is_int($data);
                $this->assertTrue($isNumber, sprintf('OpenAPI Schema Violation: Expected number at %s, got %s.', $breadcrumb, gettype($data)));
                break;

            case 'boolean':
                $this->assertIsBool($data, sprintf('OpenAPI Schema Violation: Expected boolean at %s, got %s.', $breadcrumb, gettype($data)));
                break;
        }
    }

    /**
     * Validates string format constraints (uuid, date-time, email).
     */
    protected function validateStringFormat(string $value, string $format, string $breadcrumb): void
    {
        switch ($format) {
            case 'uuid':
                $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
                $this->assertMatchesRegularExpression($uuidRegex, $value, sprintf('Value at %s must be a valid UUID v1-v5 format.', $breadcrumb));
                break;

            case 'date-time':
                $parsed = date_create($value);
                $this->assertNotFalse($parsed, sprintf('Value "%s" at %s must be a valid ISO 8601 date-time string.', $value, $breadcrumb));
                break;

            case 'email':
                $this->assertNotFalse(filter_var($value, FILTER_VALIDATE_EMAIL), sprintf('Value "%s" at %s must be a valid email address.', $value, $breadcrumb));
                break;
        }
    }

    /**
     * Resolves a JSON pointer $ref from the OpenAPI document.
     * Example: '#/components/schemas/ProjectResponse'
     *
     * @return array<string, mixed>
     */
    protected function resolveOpenApiRef(array $spec, string $ref): array
    {
        if (!str_starts_with($ref, '#/')) {
            throw new \InvalidArgumentException(sprintf('External or relative references not supported in test trait: %s', $ref));
        }

        $parts = explode('/', substr($ref, 2));
        $current = $spec;

        foreach ($parts as $part) {
            if (!isset($current[$part]) || !is_array($current[$part])) {
                throw new \RuntimeException(sprintf('Could not resolve reference pointer "%s" in OpenAPI specification.', $ref));
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * Matches a concrete request path against OpenAPI path patterns with parameters.
     * E.g. '/api/v1/projects/42' matches '/api/v1/projects/{id}'
     *
     * @param list<string> $specPaths
     */
    protected function matchOpenApiPathPattern(string $actualPath, array $specPaths): ?string
    {
        // 1. Exact match first
        if (in_array($actualPath, $specPaths, true)) {
            return $actualPath;
        }

        // 2. Regex parameter match (e.g. {id}, {uuid})
        foreach ($specPaths as $pattern) {
            $regex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '[^/]+', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $actualPath)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Loads and caches the OpenAPI specification from YAML or JSON.
     *
     * @return array<string, mixed>
     */
    protected function loadOpenApiSpecification(?string $customPath = null): array
    {
        $resolvedPath = $customPath;

        if (null === $resolvedPath) {
            $candidates = [
                'config/openapi.yaml',
                'config/openapi.json',
                'docs/openapi.yaml',
                'docs/openapi.json',
                'openapi.yaml',
                'openapi.json',
                'templates/contracts/openapi.yaml.dist',
                'templates/contracts/openapi.json.dist',
                __DIR__ . '/openapi.yaml.dist',
                __DIR__ . '/openapi.json.dist',
            ];

            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    $resolvedPath = $candidate;
                    break;
                }
            }
        }

        if (null === $resolvedPath || !file_exists($resolvedPath)) {
            $this->fail(sprintf(
                'OpenAPI Specification file not found. Checked: [%s]. Specify path via $specPath or create config/openapi.yaml.',
                implode(', ', $candidates ?? [$customPath])
            ));
        }

        if (isset(self::$cachedOpenApiSpecs[$resolvedPath])) {
            return self::$cachedOpenApiSpecs[$resolvedPath];
        }

        $content = (string) file_get_contents($resolvedPath);

        if (str_ends_with($resolvedPath, '.json') || str_starts_with(trim($content), '{')) {
            $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } else {
            // YAML parsing
            if (class_exists(Yaml::class)) {
                $parsed = Yaml::parse($content);
            } elseif (function_exists('yaml_parse')) {
                $parsed = yaml_parse($content);
            } else {
                $this->fail('YAML parsing requires Symfony Yaml component (symfony/yaml) or ext-yaml.');
            }
        }

        if (!is_array($parsed)) {
            $this->fail(sprintf('Invalid OpenAPI specification in %s: failed to parse as array.', $resolvedPath));
        }

        self::$cachedOpenApiSpecs[$resolvedPath] = $parsed;

        return $parsed;
    }
}
