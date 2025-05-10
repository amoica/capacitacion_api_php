<?php

require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
// require_once __DIR__ . '/../controllers/PostController.php'; // si añades Posts

// 1) Parsear URI y método
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/api/public';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri    = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

$controller = new UserController();
$auth       = new AuthController();
// $posts     = new PostController(); // si tienes PostController

/**
 * Middleware: extrae token de Authorization (case-insensitive y sin warning)
 * @return array Datos de usuario o detiene la ejecución con 401
 */
function authenticate() {
    // 1) Intentamos con variables de servidor (más rápido, evita warnings)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? '';

    // 2) Si no vino en $_SERVER, caemos a getallheaders()
    if (! $authHeader) {
        foreach (getallheaders() as $name => $value) {
            if (strtolower($name) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
    }

    // 3) Limpiamos “Bearer ”
    $token = trim(str_ireplace('Bearer', '', $authHeader));

    $auth = new AuthController();
    $user = $auth->verifyToken($token);
    if (! $user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido']);
        exit;  // corta la ejecución
    }
    return $user;
}

// —————— RUTAS PÚBLICAS ——————

// LOGIN público (no requiere token)
if ($uri === '/login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $auth->login($data);
    return;
}

// REGISTER público (no requiere token)
if ($uri === '/register' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $auth->register($data);
    return;
}

// —————— RUTAS PROTEGIDAS ——————

// Aquí ya exigimos token válido
$me = authenticate();

switch (true) {

    // Listar todos los usuarios
    case $uri === '/users' && $method === 'GET':
        $controller->getAll();
        break;

    // Obtener usuario por ID
    case preg_match('/\/users\/(\d+)/', $uri, $m) && $method === 'GET':
        $controller->get($m[1]);
        break;

    // Crear usuario (ej. admin)
    case $uri === '/users' && $method === 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $data['user_id'] = $me['id'];
        $controller->create($data);
        break;

    // Actualizar usuario
    case preg_match('/\/users\/(\d+)/', $uri, $m) && $method === 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $controller->update($m[1], $data);
        break;

    // Borrar usuario
    case preg_match('/\/users\/(\d+)/', $uri, $m) && $method === 'DELETE':
        $controller->delete($m[1]);
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'message' => 'Ruta no encontrada',
            'uri'     => $uri
        ]);
        break;
}