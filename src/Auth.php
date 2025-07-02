<?php
declare(strict_types=1);

namespace App;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * Class Auth
 *
 * Servicio de autenticación JWT y control de permisos por rol,
 * leyendo usuarios desde un JSON con contraseñas bcrypt.
 *
 * @package App
 */
class Auth
{
    /** @var string Clave secreta para firmar los tokens JWT */
    private string $secret;

    /** @var int Tiempo de vida del token en segundos */
    private int $ttl;

    /** @var string Ruta absoluta al fichero users.json */
    private string $usersFile;

    /**
     * Auth constructor.
     *
     * @param string $secret     Clave secreta para firmar JWT.
     * @param int    $ttl        Tiempo de vida del token en segundos.
     * @param string $usersFile  Ruta absoluta al users.json.
     */
    public function __construct(string $secret, int $ttl, string $usersFile)
    {
        $this->secret    = $secret;
        $this->ttl       = $ttl;
        $this->usersFile = $usersFile;
    }

    /**
     * Autentica un usuario con username y password contra users.json.
     *
     * @param string $username Nombre de usuario.
     * @param string $password Contraseña en texto plano.
     * @return array Datos de usuario ['username'=>..., 'roles'=>...] o lanza Exception.
     * @throws Exception Si credenciales inválidas o error interno de lectura/JSON.
     */
    public function authenticate(string $username, string $password): array
    {
        if (!is_readable($this->usersFile)) {
            throw new Exception('Error interno: no se puede leer users.json');
        }

        $raw = file_get_contents($this->usersFile);
        $users = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error interno: JSON de usuarios inválido');
        }

        foreach ($users as $user) {
            if (
                isset($user['username'], $user['password_hash'], $user['roles'])
                && $user['username'] === $username
                && password_verify($password, $user['password_hash'])
            ) {
                return [
                    'username' => $username,
                    'roles'    => $user['roles']
                ];
            }
        }

        throw new Exception('Credenciales inválidas');
    }

    /**
     * Genera un JWT firmado con HS256.
     *
     * @param array $userData Array con 'username' y 'roles'.
     * @return string JWT codificado.
     */
    public function generateToken(array $userData): string
    {
        $now = time();
        $payload = [
            'iat'   => $now,
            'exp'   => $now + $this->ttl,
            'sub'   => $userData['username'],
            'roles' => $userData['roles'],
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Verifica y decodifica un JWT.
     *
     * @param string $jwt Token JWT.
     * @return object Payload decodificado.
     * @throws Exception Si el token es inválido o expirado.
     */
    public function verifyToken(string $jwt): object
    {
        try {
            return JWT::decode($jwt, new Key($this->secret, 'HS256'));
        } catch (\Throwable $e) {
            throw new Exception('Token inválido: ' . $e->getMessage(), 401);
        }
    }

    /**
     * Calcula los métodos HTTP permitidos para un array de roles.
     *
     * @param string[] $roles Lista de roles del usuario.
     * @return string[] Métodos HTTP únicos permitidos.
     */
    public function getAllowedMethods(array $roles): array
    {
        $map = [
            'admin'  => ['GET', 'POST', 'PUT', 'DELETE'],
            'editor' => ['GET', 'POST', 'PUT', 'DELETE'],
            'viewer' => ['GET'],
        ];

        $allowed = [];
        foreach ($roles as $role) {
            if (isset($map[$role])) {
                $allowed = array_merge($allowed, $map[$role]);
            }
        }

        return array_unique($allowed);
    }
}