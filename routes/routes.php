<?php

require_once __DIR__ . '/../controllers/UserController.php';

// 1) Parsear el path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 2) Definir el prefijo a recortar
$basePath = '/api/public';

// 3) Quitar el prefijo de la ruta
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// 4) Limpiar la ruta
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}



$method = $_SERVER['REQUEST_METHOD'];
$controller = new UserController();

error_log("DEBUG: \$uri = " . $uri . ", Method = " . $method);


// 5) Usar la ruta "limpia" en el switch
switch (true) {

    case $uri === '/users' && $method === 'GET':
        $controller->getAll();
        break;

    case preg_match('/\/users\/(\d+)/', $uri, $matches) && $method === 'GET':
        $controller->get($matches[1]);
        break;

    case $uri === '/users' && $method === 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $controller->create($data);
        break;

    case preg_match('/\/users\/(\d+)/', $uri, $matches) && $method === 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $controller->update($matches[1], $data);
        break;

    case preg_match('/\/users\/(\d+)/', $uri, $matches) && $method === 'DELETE':
        $controller->delete($matches[1]);
        break;

    default:
    http_response_code(404);
    echo json_encode([
        'message' => 'Ruta no encontrada',
        'uri'     => $uri
    ]);
    break;
}
