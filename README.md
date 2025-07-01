# 📈 API REST de Cotizaciones del Dólar (PHP + MySQL)
## Descripción del Proyecto
Esta es una API REST sencilla construida con PHP y MySQL para gestionar las cotizaciones diarias del dólar. Permite consultar, añadir y actualizar los valores de apertura, cierre y la tasa oficial del Banco Central de Venezuela (BCV) para fechas específicas. El frontend básico, desarrollado con HTML, CSS y JavaScript (AJAX), interactúa con la API para demostrar sus funcionalidades.

El proyecto está diseñado siguiendo las buenas prácticas de organización de código, utilizando Composer para la gestión de dependencias (autoloading y phpdotenv para las variables de entorno) y una estructura de frontend separada con HTML, CSS y JavaScript.

## Características
**API RESTful:** Soporte para métodos GET, POST y PUT.

### Gestión de Cotizaciones:

**GET /api.php:** Obtiene todas las cotizaciones disponibles (ordenadas por fecha descendente).

**GET /api.php?fecha=YYYY-MM-DD:** Obtiene la cotización para una fecha específica.

**POST /api.php:** Añade una nueva cotización.

**PUT /api.php:** Actualiza una cotización existente.

**Base de Datos MySQL:** Persistencia de datos de cotizaciones.

**PHP:** Backend robusto y eficiente.

**Frontend Básico:** Interfaz de usuario para interactuar con la API mediante AJAX.

**Composer:** Gestión de dependencias y autoloading (PSR-4).

**.env:** Manejo seguro de variables de entorno con vlucas/phpdotenv.

**Estructura de Proyecto Limpia:** Separación de backend y frontend (src/, public/, assets/).

**Documentación de Código (PHPDoc/JSDoc-like):** Comentarios detallados en PHP, JavaScript y CSS para facilitar la comprensión y mantenimiento.

## Requisitos
Para ejecutar este proyecto, necesitas:

- **Servidor Web:** Apache o Nginx (con soporte para PHP).
- **PHP:** Versión 7.4 o superior (recomendado PHP 8.x).
- **MySQL:** Servidor de base de datos.
- **Composer:** Gestor de paquetes de PHP.

## Configuración y Ejecución
Sigue estos pasos para poner en marcha el proyecto en tu máquina local.

1. Clonar el Repositorio
Bash
---
`git clone https://github.com/jeancgarciaq/api-rest-php-test
 cd api-rest-php-test`

3. Configuración de Composer
  Instala las dependencias de PHP y genera los archivos de autoloading:

Bash
---
`composer install`
  
3. Configuración de la Base de Datos
Crea una base de datos MySQL (por ejemplo, cotizaciones_db).

Ejecuta el siguiente script SQL para crear la tabla cotizaciones:

SQL
---
`CREATE TABLE cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    apertura DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    cierre DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    bcv DECIMAL(10, 2) NOT NULL
);`

4. Configuración de Variables de Entorno
Crea un archivo llamado .env en la raíz de tu proyecto (al mismo nivel que composer.json).

Añade tus credenciales de la base de datos:

Fragmento de código
---
`DB_SERVER=localhost
DB_USERNAME=tu_usuario_mysql
DB_PASSWORD=tu_contraseña_mysql
DB_NAME=cotizaciones_db`
¡Importante: Nunca subas tu archivo .env a un repositorio público. Ya está incluido en el .gitignore de este proyecto para evitarlo.

5. Configuración del Servidor Web (Apache/Nginx)
Configura tu servidor web para que el DocumentRoot de tu aplicación apunte a la carpeta public/. Esto asegura que solo los archivos públicos sean accesibles directamente y mantiene tus archivos .env, src/ y api.php fuera del acceso directo del navegador.

Ejemplo con Apache (Virtual Host):
Añade un bloque similar en tu archivo de configuración de virtual hosts (httpd-vhosts.conf en XAMPP):

Apache
---
`<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/tu_proyecto/public" # Ajusta a tu ruta real
    ServerName cotizaciones.test # Puedes usar un nombre diferente
    <Directory "C:/xampp/htdocs/tu_proyecto/public"> # Ajusta a tu ruta real
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>`
Asegúrate de añadir 127.0.0.1 cotizaciones.test a tu archivo hosts del sistema operativo y reiniciar Apache.

Si no usas Virtual Host (solo para desarrollo local rápido):
Puedes acceder al frontend en `http://localhost/tu_nombre_del_proyecto/public/index.html`. En este caso, la API estará en `http://localhost/tu_nombre_del_proyecto/api.php`. El script.js ya está configurado para `../api.php`, lo que funcionará si api.php está en la raíz del proyecto.

6. Acceder a la Aplicación
Una vez configurado el servidor web, abre tu navegador y ve a la URL de tu frontend:

Con Virtual Host: http://cotizaciones.test/ (o http://cotizaciones.test/index.html)

Sin Virtual Host: http://localhost/tu_nombre_del_proyecto/public/index.html

Estructura del Proyecto
tu_proyecto/
├── public/                 # Archivos públicos (HTML, CSS, JS)
│   ├── index.html          # Interfaz de usuario del frontend
│   └── assets/             # Recursos estáticos
│       ├── css/
│       │   └── style.css   # Estilos CSS de la aplicación
│       └── js/
│           └── script.js   # Lógica JavaScript del frontend (AJAX)
├── src/                    # Código fuente PHP (clases y lógica de negocio)
│   ├── Database.php        # Clase para la conexión y operaciones de DB
│   └── ApiRouter.php       # Clase para manejar las rutas y la lógica de la API
├── vendor/                 # Dependencias de Composer
├── api.php                 # Punto de entrada principal de la API REST (backend)
├── composer.json           # Definiciones de dependencias de Composer
├── composer.lock           # Bloqueo de versiones de Composer
├── .env                    # Variables de entorno (credenciales DB, etc.)
└── .gitignore              # Archivos y directorios a ignorar por Git
Endpoints de la API
Método HTTP
Ruta
Descripción
Parámetros (Body/Query)
Ejemplo de Cuerpo (JSON)

GET
/api.php
Obtiene todas las cotizaciones.
(Ninguno)

GET
/api.php?fecha
Obtiene una cotización por fecha.
fecha=YYYY-MM-DD (en la URL)

POST
/api.php
Crea una nueva cotización.
fecha, bcv (requeridos); apertura, cierre (opcionales)
{ "fecha": "2024-07-01", "apertura": 36.50, "cierre": 36.60, "bcv": 36.55 }

PUT
/api.php
Actualiza una cotización existente por fecha.
fecha (requerido); apertura, cierre, bcv (al menos uno opcional)
{ "fecha": "2024-07-01", "cierre": 36.65 }

Exportar a Hojas de cálculo

## Contribución
¡Las contribuciones son bienvenidas! Si deseas mejorar este proyecto, por favor:

- Haz un "fork" del repositorio.
- Crea una nueva rama (git checkout -b feature/nueva-funcionalidad).
- Realiza tus cambios y commitea (git commit -am 'feat: Añade nueva funcionalidad X').
- Sube tus cambios (git push origin feature/nueva-funcionalidad).
- Abre un Pull Request.

Licencia
Este proyecto está bajo la Licencia MIT. Consulta el archivo LICENSE para más detalles.

Contacto
Si tienes alguna pregunta o sugerencia, no dudes en contactarme:

_Jean Carlo Garcia_
**GitHub:** @jeancgarciaq
**Linkedin:** [Jean Carlo Garcia](https://linkedin.com/in/jean-carlo-garcia-quinones)
