<?php

declare(strict_types=1);

namespace Rocks\Http;

/**
 * An immutable snapshot of an incoming HTTP request.
 *
 * Built from superglobals in production, and from named arguments when a
 * controller needs to be run internally (see Rocks\Hub::render_page).
 *
 * As with $_POST, PHP turns dots in parameter names into underscores, so
 * `hub.mode` arrives as `hub_mode`.
 */
final class Request
{
    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $post
     * @param array<string, string> $headers Keys are lowercased.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly array $headers = [],
        public readonly string $body = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }
        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $server => $name) {
            if (isset($_SERVER[$server])) {
                $headers[$name] = (string) $_SERVER[$server];
            }
        }

        return new self(
            method:  $method,
            path:    self::normalizePath($path),
            query:   $_GET,
            post:    $_POST,
            headers: $headers,
            body:    (string) file_get_contents('php://input'),
        );
    }

    /** Collapse a trailing slash so /hub/ and /hub are the same route. */
    private static function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    /** A query string parameter, or null if it is absent or not a single value. */
    public function query(string $name): ?string
    {
        return self::scalar($this->query[$name] ?? null);
    }

    /** A form body parameter, or null if it is absent or not a single value. */
    public function post(string $name): ?string
    {
        return self::scalar($this->post[$name] ?? null);
    }

    /** POST body first, then query string. */
    public function input(string $name): ?string
    {
        return $this->post($name) ?? $this->query($name);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    private static function scalar(mixed $value): ?string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
