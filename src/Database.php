<?php
// src/Database.php
namespace App; // Define el namespace para esta clase

use mysqli; // Importa la clase mysqli para usarla directamente

/**
 * Clase Database
 *
 * Maneja la conexión a la base de datos MySQL utilizando la extensión mysqli.
 * Las credenciales de conexión se obtienen de las variables de entorno.
 */
class Database {
    /**
     * @var string La dirección del servidor de la base de datos.
     */
    private $host;

    /**
     * @var string El nombre de usuario para la conexión a la base de datos.
     */
    private $username;

    /**
     * @var string La contraseña para la conexión a la base de datos.
     */
    private $password;

    /**
     * @var string El nombre de la base de datos a la que conectarse.
     */
    private $dbname;

    /**
     * @var mysqli La instancia de conexión a la base de datos.
     */
    public $connection;

    /**
     * Constructor de la clase Database.
     *
     * Inicializa las propiedades de conexión a partir de las variables de entorno
     * y establece la conexión con la base de datos MySQL.
     *
     * @throws \Exception Si la conexión a la base de datos falla.
     */
    public function __construct() {
        // Obtener las variables de entorno
        // Es buena práctica usar getenv() o $_ENV directamente para las variables de entorno
        // phpdotenv carga estas variables en $_ENV y las hace accesibles con getenv()
        $this->host = $_ENV['DB_SERVER'] ?? 'localhost'; // Fallback por si no se carga
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->dbname = $_ENV['DB_NAME'] ?? 'condominio';


        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->dbname);

        if ($this->connection->connect_error) {
            // Es mejor lanzar una excepción aquí para que el script api.php la capture
            throw new \Exception("Error de conexión a la base de datos: " . $this->connection->connect_error);
        }
    }

    /**
     * Obtiene la instancia de conexión a la base de datos.
     *
     * @return mysqli La instancia de la conexión a la base de datos.
     */
    public function getConnection() {
        return $this->connection;
    }
}