<?php
/**
 * Punto de entrada principal para la API REST de Cotizaciones.
 *
 * Este script carga el autoloader de Composer, las variables de entorno
 * (desde el archivo .env), establece la conexión a la base de datos
 * y delega el manejo de la solicitud HTTP a la clase ApiRouter.
 *
 * Proporciona un único punto de acceso para todas las operaciones de la API
 * (GET, POST, PUT) relacionadas con las cotizaciones del dólar.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php'; // Carga el autoloader de Composer

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // __DIR__ es la ruta del directorio actual
$dotenv->load();

use App\Database; // Usa la clase Database desde el namespace App
use App\ApiRouter; // Usa la clase ApiRouter desde el namespace App

// Las constantes que antes estaban en constants.php ahora se obtienen de $_ENV o getenv()
// Para mysqli, podemos usar getenv() o directamente $_ENV

// 1. Conectar a la base de datos
// Asegúrate de que las variables de entorno están cargadas antes de instanciar Database
// Las constantes de conexión ya no son necesarias aquí, la clase Database las leerá.
$database = new Database(); // Database leerá directamente de $_ENV

$mysqli = $database->getConnection();

// 2. Instanciar el router de la API y manejar la solicitud
$apiRouter = new ApiRouter($mysqli);
$apiRouter->handleRequest();

// 3. Cerrar conexión a la base de datos (opcional, PHP lo hace automáticamente)
$mysqli->close();
?>