<?php
require_once __DIR__ . '/config.php';

try {
    $dsn = "sqlsrv:server=tcp:" . DB_HOST . ",1433;Database=" . DB_NAME;
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Requête de test
    $stmt = $conn->query("SELECT GETDATE() AS current_time");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Connexion réussie à Azure SQL Database.<br>";
    echo "Heure actuelle du serveur SQL : " . $row['current_time'];
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage();
}
