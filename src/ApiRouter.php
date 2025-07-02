<?php

namespace App;

use mysqli;

/**
 * Class ApiRouter
 *
 * Gestiona el enrutamiento de las solicitudes HTTP para la API de cotizaciones.
 * Soporta:
 *  - POST   /api.php/login         ⇒ Autenticación (genera JWT)
 *  - GET    /api.php[?fecha=...]   ⇒ Listar o leer cotizaciones
 *  - POST   /api.php               ⇒ Crear cotización (roles: admin, editor)
 *  - PUT    /api.php               ⇒ Actualizar cotización (roles: admin, editor)
 *  - DELETE /api.php[?fecha=...]   ⇒ Eliminar cotización (roles: admin, editor)
 *
 * Controla JWT, permisos por método/rol y devuelve JSON con códigos HTTP adecuados.
 *
 * @package App
 */
class ApiRouter
{
    /** @var mysqli Conexión MySQLi */
    private mysqli $mysqli;

    /** @var Auth Servicio de autenticación JWT */
    private Auth $auth;

    /** @var object|null Payload del token JWT después de autenticar */
    private ?object $userData = null;

    /**
     * ApiRouter constructor.
     *
     * @param mysqli $mysqli Conexión a la base de datos.
     * @param Auth   $auth   Servicio de autenticación JWT.
     */
    public function __construct(mysqli $mysqli, Auth $auth)
    {
        $this->mysqli = $mysqli;
        $this->auth   = $auth;
    }

    /**
     * Maneja la petición HTTP, determina ruta/método y ejecuta el handler.
     *
     * @return void
     */
    public function handleRequest(): void
    {
        // 1) Extraer método y ruta limpia
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $prefix = '/api.php';
        $path   = substr($uri, strrpos($uri, $prefix) + strlen($prefix));

        // 2) Preflight CORS (responde OPTIONS sin más lógica)
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // 3) Ruta pública: login
        if ($method === 'POST' && $path === '/login') {
            $this->handleLoginRequest();
            return;
        }

        // 4) Autenticación JWT para cualquier otra ruta
        $bearer = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s(\S+)/', $bearer, $m)) {
            http_response_code(401);
            echo json_encode(['error' => 'Sin token']);
            exit;
        }
        $payload = $this->auth->verifyToken($m[1]);
        if ($payload === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido o expirado']);
            exit;
        }
        $this->userData = $payload;

        // 5) Control de acceso por método + roles
        $allowedMethods = $this->auth->getAllowedMethods($payload->roles ?? []);
        if (!in_array($method, $allowedMethods, true)) {
            http_response_code(403);
            echo json_encode(['error' => "No tienes permiso para $method"]);
            exit;
        }

        // 6) Enrutamiento protegido
        switch ("{$method} {$path}") {
            case 'GET ':
            case 'GET':
                $this->handleGetRequest();
                break;

            case 'POST ':
            case 'POST':
                $this->handlePostRequest();
                break;

            case 'PUT ':
            case 'PUT':
                $this->handlePutRequest();
                break;

            case 'DELETE ':
            case 'DELETE':
                $this->handleDeleteRequest();
                break;

            default:
                http_response_code(404);
                echo json_encode(['error' => 'Ruta no encontrada']);
        }
    }

    /**
     * Lee y decodifica JSON del cuerpo de la petición.
     * Responde 400 y termina si el JSON es inválido.
     *
     * @return array Datos decodificados.
     */
    private function getJsonInput(): array
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON inválido']);
            exit;
        }
        return $data;
    }

    /**
     * Handler de POST /api.php/login.
     * Valida credenciales y genera un JWT.
     *
     * @return void
     */
    private function handleLoginRequest(): void
    {
        $data = $this->getJsonInput();
        if (empty($data['username']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Usuario y contraseña requeridos']);
            return;
        }

        $user = $this->auth->authenticate($data['username'], $data['password']);
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciales inválidas']);
            return;
        }

        $token = $this->auth->generateToken($user);
        echo json_encode(['token' => $token]);
    }

    /**
     * Handler de GET /api.php[?fecha=...].
     * Obtiene una cotización por fecha o lista todas.
     *
     * @return void
     */
    private function handleGetRequest(): void
    {
        if (isset($_GET['fecha'])) {
            $fecha = $_GET['fecha'];
            $stmt  = $this->mysqli->prepare(
                "SELECT fecha, apertura, cierre, bcv FROM dolar WHERE fecha = ?"
            );
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Error en GET por fecha: ' . $this->mysqli->error]);
                return;
            }
            $stmt->bind_param('s', $fecha);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                echo json_encode($res->fetch_assoc());
            } else {
                http_response_code(404);
                echo json_encode(['error' => "No existe cotización para $fecha"]);
            }
            $stmt->close();
            return;
        }

        $res = $this->mysqli->query("SELECT fecha, apertura, cierre, bcv FROM dolar ORDER BY fecha DESC");
        if (!$res) {
            http_response_code(500);
            echo json_encode(['error' => 'Error en GET all: ' . $this->mysqli->error]);
            return;
        }
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode($rows);
    }

    /**
     * Handler de POST /api.php.
     * Inserta una nueva cotización.
     *
     * @return void
     */
    private function handlePostRequest(): void
    {
        $data     = $this->getJsonInput();
        $fecha    = $data['fecha']    ?? null;
        $apertura = $data['apertura'] ?? 0.0;
        $cierre   = $data['cierre']   ?? 0.0;
        $bcv      = $data['bcv']      ?? null;

        if (!$fecha || !is_numeric($bcv)) {
            http_response_code(400);
            echo json_encode(['error' => 'Fecha y BCV (numérico) requeridos']);
            return;
        }

        // Verificar existencia
        $chk = $this->mysqli->prepare("SELECT id FROM dolar WHERE fecha = ?");
        $chk->bind_param('s', $fecha);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => "Ya existe cotización para $fecha"]);
            $chk->close();
            return;
        }
        $chk->close();

        $stmt = $this->mysqli->prepare(
            "INSERT INTO dolar (fecha, apertura, cierre, bcv) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('sddd', $fecha, $apertura, $cierre, $bcv);
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                'message' => 'Cotización añadida',
                'id'      => $this->mysqli->insert_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al insertar: ' . $stmt->error]);
        }
        $stmt->close();
    }

    /**
     * Handler de PUT /api.php.
     * Actualiza cotización existente según campos enviados.
     *
     * @return void
     */
    private function handlePutRequest(): void
    {
        $data     = $this->getJsonInput();
        $fecha    = $data['fecha']    ?? null;
        $apertura = $data['apertura'] ?? null;
        $cierre   = $data['cierre']   ?? null;
        $bcv      = $data['bcv']      ?? null;

        if (!$fecha) {
            http_response_code(400);
            echo json_encode(['error' => 'La fecha es requerida']);
            return;
        }

        $sets   = [];
        $params = [];
        $types  = '';

        if (is_numeric($apertura)) {
            $sets[]   = 'apertura = ?';
            $params[] = $apertura;
            $types   .= 'd';
        }
        if (is_numeric($cierre)) {
            $sets[]   = 'cierre = ?';
            $params[] = $cierre;
            $types   .= 'd';
        }
        if (is_numeric($bcv)) {
            $sets[]   = 'bcv = ?';
            $params[] = $bcv;
            $types   .= 'd';
        }

        if (empty($sets)) {
            http_response_code(400);
            echo json_encode(['error' => 'Ningún campo válido para actualizar']);
            return;
        }

        $params[] = $fecha;
        $types   .= 's';
        $sql      = 'UPDATE dolar SET ' . implode(', ', $sets) . ' WHERE fecha = ?';

        $stmt = $this->mysqli->prepare($sql);
        $bind = array_merge([$types], $params);
        $refs = [];
        foreach ($bind as $i => $v) {
            $refs[$i] = &$bind[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['message' => "Cotización de $fecha actualizada"]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => "No se encontró o no cambió $fecha"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar: ' . $stmt->error]);
        }
        $stmt->close();
    }

    /**
     * Handler de DELETE /api.php[?fecha=...].
     * Elimina la cotización por fecha.
     *
     * @return void
     */
    private function handleDeleteRequest(): void
    {
        if (!isset($_GET['fecha'])) {
            http_response_code(400);
            echo json_encode(['error' => 'La fecha es requerida para eliminar']);
            return;
        }
        $fecha = $_GET['fecha'];
        $stmt  = $this->mysqli->prepare("DELETE FROM dolar WHERE fecha = ?");
        $stmt->bind_param('s', $fecha);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['message' => "Cotización de $fecha eliminada"]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => "No existe cotización para $fecha"]);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar: ' . $stmt->error]);
        }
        $stmt->close();
    }
}