<?php

// Define el namespace para esta clase
namespace App; 

// Asegurarse de que mysqli esté disponible en este scope
use mysqli;

/**
 * Clase ApiRouter
 *
 * Gestiona el enrutamiento de las solicitudes HTTP (GET, POST, PUT)
 * para la API de cotizaciones y se comunica con la base de datos.
 */
class ApiRouter {/**
     * @var mysqli La instancia de conexión a la base de datos.
     */
    private $mysqli;

    /**
     * Constructor de la clase ApiRouter.
     *
     * @param mysqli $mysqliConnection La conexión a la base de datos mysqli.
     */
    public function __construct($mysqliConnection) {
        $this->mysqli = $mysqliConnection;
    }

    /**
     * Maneja la solicitud HTTP entrante.
     *
     * Establece las cabeceras HTTP necesarias para CORS y JSON,
     * y delega la solicitud al método apropiado según el verbo HTTP.
     *
     * @return void
     */
    public function handleRequest() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->handleGetRequest();
                break;
            case 'POST':
                $this->handlePostRequest();
                break;
            case 'PUT':
                $this->handlePutRequest();
                break;
            case 'OPTIONS': // Manejar las solicitudes OPTIONS para CORS
                http_response_code(200);
                break;
            default:
                http_response_code(405); // Método no permitido
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
    }

    /**
     * Maneja las solicitudes GET para consultar cotizaciones.
     *
     * Puede consultar una cotización específica por fecha o todas las cotizaciones.
     * Responde con datos JSON o un mensaje de error.
     *
     * @return void
     */
    private function handleGetRequest() {
        if (isset($_GET['fecha'])) {
            $fecha = $_GET['fecha'];
            $stmt = $this->mysqli->prepare("SELECT fecha, apertura, cierre, bcv FROM dolar WHERE fecha = ?");
            $stmt->bind_param("s", $fecha);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode($row);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Cotización no encontrada para la fecha " . $fecha]);
            }
            $stmt->close();
        } else {
            $result = $this->mysqli->query("SELECT fecha, apertura, cierre, bcv FROM dolar ORDER BY fecha DESC");
            if ($result->num_rows > 0) {
                $cotizaciones = [];
                while ($row = $result->fetch_assoc()) {
                    $cotizaciones[] = $row;
                }
                echo json_encode($cotizaciones);
            } else {
                http_response_code(200);
                echo json_encode([]);
            }
        }
    }

    /**
     * Maneja las solicitudes POST para añadir nuevas cotizaciones.
     *
     * Espera datos JSON en el cuerpo de la solicitud (fecha, bcv, apertura, cierre).
     * Valida los datos y los inserta en la base de datos.
     * Responde con un mensaje JSON de éxito o error.
     *
     * @return void
    */
    private function handlePostRequest() {
        $data = json_decode(file_get_contents("php://input"), true);

        $fecha = $data['fecha'] ?? null;
        $apertura = $data['apertura'] ?? 0.00;
        $cierre = $data['cierre'] ?? 0.00;
        $bcv = $data['bcv'] ?? null;

        if (!$fecha || !$bcv) {
            http_response_code(400);
            echo json_encode(["message" => "Fecha y BCV son campos requeridos."]);
            return;
        }

        $stmt_check = $this->mysqli->prepare("SELECT id FROM dolar WHERE fecha = ?");
        $stmt_check->bind_param("s", $fecha);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            http_response_code(409);
            echo json_encode(["message" => "Ya existe una cotización para la fecha " . $fecha]);
            $stmt_check->close();
            return;
        }
        $stmt_check->close();

        $stmt = $this->mysqli->prepare("INSERT INTO dolar (fecha, apertura, cierre, bcv) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sddd", $fecha, $apertura, $cierre, $bcv);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["message" => "Cotización añadida exitosamente.", "id" => $this->mysqli->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al añadir cotización: " . $stmt->error]);
        }
        $stmt->close();
    }

    /**
     * Maneja las solicitudes PUT para actualizar cotizaciones existentes.
     *
     * Espera datos JSON en el cuerpo de la solicitud (fecha, y opcionalmente apertura, cierre, bcv).
     * Actualiza los campos proporcionados para la fecha especificada.
     * Responde con un mensaje JSON de éxito o error.
     *
     * @return void
    */
    private function handlePutRequest() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            parse_str(file_get_contents("php://input"), $data); // Fallback for x-www-form-urlencoded
        }

        $fecha = $data['fecha'] ?? null;
        $apertura = $data['apertura'] ?? null;
        $cierre = $data['cierre'] ?? null;
        $bcv = $data['bcv'] ?? null;

        if (!$fecha) {
            http_response_code(400);
            echo json_encode(["message" => "La fecha es requerida para actualizar."]);
            return;
        }

        $set_clauses = [];
        $params = [];
        $types = "";

        if ($apertura !== null) {
            $set_clauses[] = "apertura = ?";
            $params[] = $apertura;
            $types .= "d";
        }
        if ($cierre !== null) {
            $set_clauses[] = "cierre = ?";
            $params[] = $cierre;
            $types .= "d";
        }
        if ($bcv !== null) {
            $set_clauses[] = "bcv = ?";
            $params[] = $bcv;
            $types .= "d";
        }

        if (empty($set_clauses)) {
            http_response_code(400);
            echo json_encode(["message" => "No se proporcionaron campos para actualizar."]);
            return;
        }

        $query = "UPDATE dolar SET " . implode(", ", $set_clauses) . " WHERE fecha = ?";
        $params[] = $fecha;
        $types .= "s";

        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["message" => "Error al preparar la consulta: " . $this->mysqli->error]);
            return;
        }

        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(["message" => "Cotización actualizada exitosamente para la fecha " . $fecha]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Cotización no encontrada o sin cambios para la fecha " . $fecha]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar cotización: " . $stmt->error]);
        }
        $stmt->close();
    }
}