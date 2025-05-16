<?php

/**
 * Gestionnaire pour les requêtes OPTIONS (CORS pre-flight)
 * Ce fichier répond simplement avec les en-têtes CORS appropriés
 */

// En-têtes CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 heures de cache pour les pre-flight

// Répondre avec un statut 200 et arrêter l'exécution
http_response_code(200);
exit;
