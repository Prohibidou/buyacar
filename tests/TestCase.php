<?php
/**
 * TestCase Base Class
 * Clase base para todos los tests automatizados
 */

class TestCase
{
    protected string $baseUrl = 'http://localhost/concesionaria_laravel/api.php';
    protected ?string $sessionCookie = null;
    protected array $results = [];
    protected string $testName = '';

    // Test users
    protected array $clienteCredentials = [
        'email' => 'cliente@test.com',
        'password' => 'password'
    ];

    protected array $vendedorCredentials = [
        'email' => 'vendedor@flycar.com',
        'password' => 'password'
    ];

    protected array $adminCredentials = [
        'email' => 'admin@flycar.com',
        'password' => 'password'
    ];

    /**
     * Make an API request
     */
    protected function api(string $action, array $data = [], string $method = 'GET'): array
    {
        $ch = curl_init();

        if ($method === 'GET') {
            $data['action'] = $action;
            $url = $this->baseUrl . '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            $data['action'] = $action;
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if ($this->sessionCookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->sessionCookie);
        }

        curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/test_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/test_cookies.txt');

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Extract session cookie
        if (preg_match('/Set-Cookie:\s*PHPSESSID=([^;]+)/i', $header, $matches)) {
            $this->sessionCookie = 'PHPSESSID=' . $matches[1];
        }

        curl_close($ch);

        $decoded = json_decode($body, true);
        return $decoded ?? ['error' => 'Invalid JSON response', 'raw' => $body];
    }

    /**
     * Login as a specific role
     */
    protected function loginAs(string $role): bool
    {
        $credentials = match ($role) {
            'CLIENTE' => $this->clienteCredentials,
            'VENDEDOR' => $this->vendedorCredentials,
            'ADMINISTRADOR' => $this->adminCredentials,
            default => $this->clienteCredentials
        };

        $response = $this->api('login', $credentials, 'POST');
        return $response['success'] ?? false;
    }

    /**
     * Logout
     */
    protected function logout(): void
    {
        $this->api('logout');
        $this->sessionCookie = null;
    }

    /**
     * Assert that a condition is true
     */
    protected function assertTrue(bool $condition, string $message): void
    {
        $this->results[] = [
            'pass' => $condition,
            'message' => $message
        ];
    }

    /**
     * Assert that a condition is false
     */
    protected function assertFalse(bool $condition, string $message): void
    {
        $this->assertTrue(!$condition, $message);
    }

    /**
     * Assert that two values are equal
     */
    protected function assertEquals($expected, $actual, string $message): void
    {
        $pass = $expected === $actual;
        $this->results[] = [
            'pass' => $pass,
            'message' => $message . ($pass ? '' : " (expected: $expected, got: $actual)")
        ];
    }

    /**
     * Assert that a value is not null/empty
     */
    protected function assertNotEmpty($value, string $message): void
    {
        $this->assertTrue(!empty($value), $message);
    }

    /**
     * Assert array has key
     */
    protected function assertArrayHasKey(string $key, array $array, string $message): void
    {
        $this->assertTrue(array_key_exists($key, $array), $message);
    }

    /**
     * Run all test methods
     */
    public function run(): array
    {
        $this->results = [];
        $methods = get_class_methods($this);

        foreach ($methods as $method) {
            if (str_starts_with($method, 'test')) {
                $this->testName = $method;
                try {
                    $this->setUp();
                    $this->$method();
                    $this->tearDown();
                } catch (Exception $e) {
                    $this->results[] = [
                        'pass' => false,
                        'message' => "$method: Exception - " . $e->getMessage()
                    ];
                }
            }
        }

        return $this->results;
    }

    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        // Override in child classes
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        $this->logout();
    }

    /**
     * Get test results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get test class name
     */
    public function getName(): string
    {
        return get_class($this);
    }
}
