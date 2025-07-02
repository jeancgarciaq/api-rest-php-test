<?php
declare(strict_types=1);

/**
 * Punto de entrada principal para la API REST de Cotizaciones.
 * - Front-controller único: todas las rutas bajo /api.php
 * - Carga autoload, .env, DB y Auth
 * - Manejo de CORS + preflight OPTIONS
 * - Handler global de excepciones para devolver siempre JSON limpio
 */

// 1) Desactivar salida de errores HTML en producción
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// 2) Autoload + carga de .env
require_once __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();

// Namespaces
use App\Database;
use App\ApiRouter;
use App\Auth;

// 3) Handler global de excepciones para devolver JSON
set_exception_handler(function (\Throwable $e) {
    http_response_code($e->getCode() ?: 500);
    header('Content-Type: application/json; charset=UTF-8');
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['error' => $e->getMessage()]);
    exit;
});

// 4) Cabeceras CORS + JSON
header_remove('Permissions-Policy');
header('Permissions-Policy: interest-cohort=()');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// 5) Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 6) Debugging de arranque y rutas (se mantendrá en error_log, no en salida al cliente)
error_log(sprintf(
    "→ api.php iniciado; REQUEST_URI=%s  PATH_INFO=%s",
    $_SERVER['REQUEST_URI'] ?? '(nulo)',
    $_SERVER['PATH_INFO']    ?? '(nulo)'
));

// ==== Inicializar dependencias ====

// 7) Conexión a BD
$db     = new Database();
$mysqli = $db->getConnection();

// 8) Configuración JWT desde .env
$jwtSecret = $_ENV['JWT_SECRET'] ?? '';
$jwtTtl    = isset($_ENV['JWT_TTL']) ? (int) $_ENV['JWT_TTL'] : 3600;
if (empty($jwtSecret)) {
    error_log('⚠️ JWT_SECRET no definido en .env; usando valor por defecto (inseguro)');
    $jwtSecret = 'default_insecure_secret';
}

// 9) Ruta al fichero de usuarios
$usersFile = __DIR__ . '/users.json';
if (!is_readable($usersFile)) {
    error_log("⚠️ No se puede leer users.json en {$usersFile}");
}

// 10) Instancia Auth con lectura de JSON y bcrypt
$auth = new Auth($jwtSecret, $jwtTtl, $usersFile);

// ==== Enrutamiento de la API ====

// Extraer método y ruta limpia
$method = $_SERVER['REQUEST_METHOD'];
$path   = $_SERVER['PATH_INFO'] ?? '/';

error_log(sprintf("→ Router> método=%s  path=%s", $method, $path));

// Disparar el router
$router = new ApiRouter($mysqli, $auth);
$router->handleRequest($method, $path);