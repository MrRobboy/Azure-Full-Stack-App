<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'schoolpea';
$db_user = 'root';
$db_pass = '';

try {
	$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	// Log error but don't expose details
	error_log("Database connection failed: " . $e->getMessage());
	die("Database connection failed. Please try again later.");
}

// Function to get count from a table
function getCount($table)
{
	global $pdo;
	try {
		$stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
		$result = $stmt->fetch();
		return $result['count'] ?? 0;
	} catch (PDOException $e) {
		error_log("Error getting count from $table: " . $e->getMessage());
		return 0;
	}
}
