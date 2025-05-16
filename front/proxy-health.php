<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'Proxy health check successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'script_filename' => $_SERVER['SCRIPT_FILENAME'],
    'document_root' => $_SERVER['DOCUMENT_ROOT']
]);