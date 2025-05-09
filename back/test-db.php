<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/services/DatabaseService.php';

try {
    $db = DatabaseService::getInstance();
    $conn = $db->getConnection();

    // Test basic connection
    echo "Testing database connection...\n";
    $result = $conn->query("SELECT 1");
    echo "Basic query successful\n";

    // Test tables
    echo "\nTesting table access...\n";

    // Test MATIERE table
    $stmt = $conn->query("SELECT COUNT(*) FROM MATIERE");
    $count = $stmt->fetchColumn();
    echo "MATIERE table: $count records found\n";

    // Test CLASSE table
    $stmt = $conn->query("SELECT COUNT(*) FROM CLASSE");
    $count = $stmt->fetchColumn();
    echo "CLASSE table: $count records found\n";

    // Test EXAM table
    $stmt = $conn->query("SELECT COUNT(*) FROM EXAM");
    $count = $stmt->fetchColumn();
    echo "EXAM table: $count records found\n";

    echo "\nAll tests completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
