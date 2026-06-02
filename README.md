# Sistema de Presupuesto Personal - Documentación del Sistema 🚀

Bienvenido a la documentación técnica oficial del **Sistema de Presupuesto Personal** (anteriormente conocido como Sistema de Presupuesto Personal), una plataforma inteligente y robusta de gestión financiera personal y administrativa desarrollada bajo el patrón de diseño MVC en PHP nativo, con interfaces modernas y alta seguridad.

## 🏗️ Arquitectura del Sistema
El sistema emplea una arquitectura limpia **MVC (Modelo-Vista-Controlador)** que divide el proyecto de la siguiente forma:
- **Modelos (`backend/models/`)**: Manejan la lógica de acceso a la base de datos (PDO) y procesan las operaciones matemáticas e informes financieros.
- **Vistas (`frontend/views/`)**: Interfaces de usuario responsivas con diseño de Glassmorphic (diseño premium y moderno) implementadas con Tailwind CSS e iconos de FontAwesome.
- **Controladores (`backend/controllers/`)**: Gestionan el flujo de datos, autorización de usuarios, reportes mensuales y la exportación de PDFs.
- **Core (`backend/core/`)**: Motor central que implementa el Router dinámico, manejo de sesiones seguras, singleton de base de datos y utilidades de caché.

## 🔒 Seguridad Implementada
- **Autenticación Segura**: Sesiones seguras con directivas HTTPOnly, SameSite y encriptación nativa `bcrypt` para las contraseñas.
- **Mitigación CSRF**: Protección contra falsificación de peticiones en todos los formularios mediante tokens criptográficos aleatorios.
- **Prevención de Inyección SQL**: Acceso seguro a la base de datos mediante sentencias preparadas de PDO (Prepared Statements).
- **Protección de Fuerza Bruta (Rate Limiting)**: Bloqueo de inicios de sesión tras múltiples intentos fallidos para mitigar ataques.

## ⚙️ Configuración y Despliegue
### 1. Requisitos Previos
- Servidor web con PHP 8.1 o superior.
- Base de datos MySQL / MariaDB.
- Extensión PDO activa en `php.ini`.

### 2. Variables de Entorno y Configuración
Edita el archivo `backend/config/config.php` o configura un archivo `.env` en la raíz con las siguientes variables:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'presupuesto_bd');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_SECRET', 'tu_secreto_seguro_aqui');
```

### 3. Estructura de la Base de Datos
El esquema (`presupuesto_bd`) consta de:
- `users`: Cuentas de usuario y administradores.
- `transactions`: Transacciones financieras (ingresos y egresos) con estado (pagado/pendiente) y etiquetas.
- `activity_log`: Auditoría completa de seguridad de las actividades del usuario.
- `support_tickets`: Soporte integrado y comunicación.
- `monthly_budgets`: Presupuestos mensuales personalizados.

## 💡 Rendimiento y Optimización
Para garantizar un rendimiento de alta velocidad en el Dashboard, el sistema utiliza un sistema de caché de archivos temporales (`backend/cache/`) para almacenar cálculos financieros pesados. Esta caché se invalida automáticamente cuando se realiza una nueva transacción (`Core\Cache::clearAll()`), manteniendo la integridad de la información en tiempo real.

