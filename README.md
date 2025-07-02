# 📈 API REST de Cotizaciones del Dólar (PHP + MySQL)

## Descripción del Proyecto
Esta es una API REST sencilla construida con PHP y MySQL para gestionar las cotizaciones diarias del dólar. Permite consultar, añadir y actualizar los valores de apertura, cierre y la tasa oficial del BCV.

El proyecto está diseñado siguiendo las buenas prácticas de organización de código, utilizando Composer para la gestión de dependencias (autoloading y phpdotenv para las variables de entorno) y una interfaz frontend básica que interactúa vía AJAX.

## Características

**API RESTful:** Soporte para métodos GET, POST y PUT.

### Gestión de Cotizaciones

- **GET /api.php:**  
  Obtiene todas las cotizaciones disponibles (ordenadas por fecha descendente).
- **GET /api.php?fecha=YYYY-MM-DD:**  
  Obtiene la cotización para una fecha específica.
- **POST /api.php:**  
  Añade una nueva cotización.
- **PUT /api.php:**  
  Actualiza una cotización existente.

### Frontend Básico

- **Interfaz en `public/index.html` y `public/assets/js/script.js`.**  
- Formularios para:
  - **Consultar** cotización por fecha.
  - **Añadir** una nueva cotización.
  - **Actualizar** una cotización existente.
- **Autofill en el formulario de Actualización:**  
  Al seleccionar una fecha en el formulario de actualización, los campos de apertura, cierre y BCV se completan automáticamente con los valores existentes que hay en la base de datos.  
  Esto se consigue en `public/assets/js/script.js` mediante un handler `"change"` sobre el input de fecha, que realiza una petición GET a `api.php?fecha=YYYY-MM-DD` y vuelca los valores en los inputs con formato de dos decimales.

### Base de Datos MySQL
Persistencia de datos de cotizaciones.

### PHP
Backend robusto y eficiente.

### Composer
Gestión de dependencias y autoloading (PSR-4).

### .env
Manejo seguro de variables de entorno con vlucas/phpdotenv.

### Estructura de Proyecto
```
tu_proyecto/
├── public/                 
│   ├── index.html          
│   └── assets/             
│       ├── css/
│       │   └── style.css   # Estilos
│       └── js/
│           └── script.js   # Lógica JavaScript del frontend
├── src/                    
│   ├── Database.php
│   ├── Auth.php        
│   └── ApiRouter.php       
├── vendor/                 
├── api.php                 
├── composer.json           
├── composer.lock           
├── .env
├── .htaccess
├── users.json                    
└── .gitignore              
```

## Requisitos
Para ejecutar este proyecto, necesitas:

- **Servidor Web:** Apache o Nginx (con soporte para PHP).  
- **PHP:** Versión 7.4 o superior (recomendado PHP 8.x).  
- **MySQL:** Servidor de base de datos.  
- **Composer:** Gestor de paquetes de PHP.  

## Configuración y Ejecución

1. **Clonar el Repositorio**  
   ```bash
   git clone https://github.com/jeancgarciaq/api-rest-php-test
   cd api-rest-php-test
   ```

2. **Instalar Dependencias**  
   ```bash
   composer install
   ```

3. **Configurar la Base de Datos**  
   - Crea la base de datos (por ejemplo `cotizaciones_db`).  
   - Ejecuta el script SQL:
     ```sql
     CREATE TABLE cotizaciones (
         id INT AUTO_INCREMENT PRIMARY KEY,
         fecha DATE NOT NULL UNIQUE,
         apertura DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
         cierre DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
         bcv DECIMAL(10, 2) NOT NULL
     );
     ```

4. **Variables de Entorno**  
   Copia `.env.example` a `.env` y ajusta tus credenciales:
   ```dotenv
   DB_SERVER=localhost
   DB_USERNAME=tu_usuario_mysql
   DB_PASSWORD=tu_contraseña_mysql
   DB_NAME=cotizaciones_db

   JWT_SECRET=tu_clave_secreta
   JWT_TTL=3600
   ```

5. **Configurar el Servidor Web**  
   - Señala el `DocumentRoot` a la carpeta `public/`.  
   - Ejemplo (Apache VirtualHost):
     ```apache
     <VirtualHost *:80>
         DocumentRoot "/ruta/a/tu_proyecto/public"
         ServerName cotizaciones.test
         <Directory "/ruta/a/tu_proyecto/public">
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```
   - Añade `127.0.0.1 cotizaciones.test` a tu `/etc/hosts`.

6. **Acceder a la Aplicación**  
   - Con Virtual Host: http://cotizaciones.test/  
   - Sin Virtual Host: http://localhost/tu_proyecto/public/index.html  

## Endpoints de la API

| Método HTTP | Ruta                | Descripción                                        | Parámetros                           |
|-------------|---------------------|----------------------------------------------------|--------------------------------------|
| GET         | `/api.php`          | Lista todas las cotizaciones                       | —                                    |
| GET         | `/api.php?fecha=`   | Cotización de una fecha específica                 | `fecha=YYYY-MM-DD` (query string)    |
| POST        | `/api.php`          | Crea nueva cotización                              | JSON body: `{ "fecha", "bcv", ... }` |
| PUT         | `/api.php`          | Actualiza cotización existente (por fecha)         | JSON body: `{ "fecha", "cierre", ...}`|

## Contribución
¡Las contribuciones son bienvenidas!  
1. Haz un fork del repositorio.  
2. Crea una rama: `git checkout -b feature/nueva-funcionalidad`.  
3. Realiza tus cambios y commitea:  
   ```bash
   git commit -am "feat: descripción de la funcionalidad"
   ```  
4. Sube tu rama: `git push origin feature/nueva-funcionalidad`.  
5. Abre un Pull Request.

## Licencia
Este proyecto está bajo la Licencia MIT. Consulta `LICENSE` para más detalles.

## Contacto
_Jean Carlo Garcia_  
- **GitHub:** @jeancgarciaq  
- **LinkedIn:** https://linkedin.com/in/jean-carlo-garcia-quinones  
