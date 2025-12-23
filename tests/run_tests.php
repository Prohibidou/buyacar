<?php
/**
 * Test Runner - Ejecuta todos los tests
 * 
 * Uso: php tests/run_tests.php
 */

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/AllTests.php';

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║        FLY Car - Tests Automatizados                      ║\n";
echo "║        Principio de Responsabilidad Única                 ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Get all test classes
$testClasses = array_filter(get_declared_classes(), function ($class) {
    return str_starts_with($class, 'Test_');
});

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$results = [];

foreach ($testClasses as $testClass) {
    $test = new $testClass();

    // Run test
    try {
        $test->test();
        $testResults = $test->getResults();

        foreach ($testResults as $result) {
            $totalTests++;
            if ($result['pass']) {
                $passedTests++;
                echo "  ✅ {$result['message']}\n";
            } else {
                $failedTests++;
                echo "  ❌ {$result['message']}\n";
            }
            $results[] = $result;
        }
    } catch (Exception $e) {
        $totalTests++;
        $failedTests++;
        echo "  ❌ $testClass: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  RESULTADOS: $passedTests/$totalTests tests pasaron\n";
if ($failedTests > 0) {
    echo "  ⚠️  $failedTests tests fallaron\n";
} else {
    echo "  ✅ Todos los tests pasaron!\n";
}
echo "═══════════════════════════════════════════════════════════\n\n";

// Exit with error code if tests failed
exit($failedTests > 0 ? 1 : 0);
