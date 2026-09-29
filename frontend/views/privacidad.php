<?php
if (!isset($data)) { $data = []; }
$title = $data['title'] ?? 'Política de Privacidad';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> · Sistema de Presupuesto Personal</title>
    <meta name="description" content="Política de privacidad del Sistema de Presupuesto Personal.">
    <link rel="icon" type="image/png" href="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0b1121 0%, #170f30 100%);
            color: white;
            min-height: 100vh;
        }
        .glass {
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1rem;
        }
        .wrap { max-width: 820px; margin: 0 auto; padding: 2rem 1.25rem 3rem; }
        .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        .topbar .brand { font-weight: 800; color: #34d399; }
        .topbar a.back { color: #9ca3af; text-decoration: none; font-size: 0.85rem; }
        .topbar a.back:hover { color: #34d399; }
        .card { padding: 1.75rem; }
        h1 { font-size: 1.75rem; margin-bottom: 0.25rem; }
        .updated { color: #9ca3af; font-size: 0.85rem; margin-bottom: 1.25rem; }
        h2 { font-size: 1.15rem; margin: 1.5rem 0 0.6rem; color: #34d399; }
        p, li { line-height: 1.7; margin-bottom: 0.6rem; color: #d1d5db; }
        ul, ol { padding-left: 1.25rem; }
        a { color: #34d399; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar">
            <span class="brand">Sistema de Presupuesto Personal</span>
            <a class="back" href="<?php echo URL_ROOT; ?>">← Volver</a>
        </div>

        <div class="card glass">
            <h1>Política de Privacidad</h1>
            <p class="updated">Última actualización: 2026-09-29</p>

            <h2>1. Responsable del tratamiento</h2>
            <p>Los datos personales recopilados por el <strong>Sistema de Presupuesto Personal</strong> son gestionados por:</p>
            <ul>
                <li><strong>Responsable:</strong> el administrador del sistema.</li>
                <li><strong>Sitio:</strong> aplicación web de presupuesto personal.</li>
            </ul>

            <h2>2. Datos que recopilamos</h2>
            <p>El sistema recopila únicamente los datos necesarios para su funcionamiento:</p>
            <ul>
                <li><strong>Datos de cuenta:</strong> nombre completo, usuario, contraseña (cifrada), rol y registro de última actividad / último acceso.</li>
                <li><strong>Datos financieros:</strong> transacciones, presupuestos, categorías y saldos registrados por el usuario.</li>
                <li><strong>Datos de soporte:</strong> tickets de soporte y mensajes enviados.</li>
                <li><strong>Datos de auditoría:</strong> registro de actividad (actividad online, acceso, operaciones) para seguridad.</li>
                <li><strong>Datos técnicos:</strong> dirección IP y agente del navegador, para seguridad y auditoría.</li>
            </ul>
            <p><strong>No se comparten datos financieros con terceros.</strong></p>

            <h2>3. Finalidad del tratamiento</h2>
            <p>Los datos se utilizan exclusivamente para:</p>
            <ol>
                <li>Gestionar el presupuesto y las finanzas personales del usuario.</li>
                <li>Autenticar el acceso y proteger la aplicación.</li>
                <li>Generar reportes, respaldos y PDFs solicitados.</li>
                <li>Atender consultas de soporte.</li>
            </ol>
            <p>No se utilizan datos para publicidad ni se ceden a terceros.</p>

            <h2>4. Base legal</h2>
            <p>El tratamiento se ampara en el <strong>artículo 20 de la Constitución de la República Bolivariana de Venezuela</strong> y la <strong>Ley Orgánica de Protección de Datos Personales (LOPDP)</strong>.</p>

            <h2>5. Conservación de los datos</h2>
            <p>Los datos se conservan mientras la cuenta esté activa y por el tiempo necesario para cumplir obligaciones contables o de auditoría. Al solicitar la eliminación, los datos personales se suprimen o anonimizan.</p>

            <h2>6. Derechos del usuario</h2>
            <p>El usuario puede acceder, rectificar, solicitar la eliminación u oponerse al tratamiento de sus datos personales contactando al administrador del sistema.</p>

            <h2>7. Seguridad de los datos</h2>
            <ul>
                <li>Contraseñas cifradas con hash seguro.</li>
                <li>Sesiones con token seguro y expiración.</li>
                <li>Control de acceso y registro de actividad (auditoría).</li>
                <li>Conexiones cifradas (HTTPS/SSL).</li>
                <li>Respaldo de la base de datos.</li>
            </ul>

            <h2>8. Cookies y almacenamiento local</h2>
            <p>La aplicación utiliza cookies de sesión y almacenamiento local para mantener la sesión activa. No se utilizan cookies de terceros ni de seguimiento publicitario.</p>

            <h2>9. Terceros</h2>
            <p>La aplicación no comparte datos personales con terceros. La infraestructura de alojamiento gestiona los datos bajo sus propios términos de servicio.</p>

            <h2>10. Cambios en esta política</h2>
            <p>Cualquier cambio en esta política se publicará en esta página con la fecha de actualización. El uso continuado de la aplicación tras un cambio implica su aceptación.</p>

            <h2>11. Contacto</h2>
            <p>Ante cualquier duda sobre esta política, contacte al administrador del sistema.</p>
        </div>
    </div>
</body>
</html>