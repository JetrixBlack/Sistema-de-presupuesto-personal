<?php
if (!isset($data)) { $data = []; }
$comp = $data['comprobante'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud procesada · Sistema de Presupuesto Personal</title>
    <link rel="icon" type="image/png" href="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0b1121 0%, #170f30 100%); color: white; min-height: 100vh; }
        .glass { background: rgba(0,0,0,0.45); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.12); border-radius: 1rem; }
        .wrap { max-width: 560px; margin: 0 auto; padding: 2rem 1.25rem 3rem; }
        .card { padding: 1.75rem; }
        h1 { font-size: 1.3rem; margin-bottom: 1rem; color: #34d399; }
        p, li { line-height: 1.7; color: #d1d5db; margin-bottom: 0.6rem; }
        ul { padding-left: 1.25rem; }
        a.back { display: inline-block; margin-top: 1.25rem; color: #34d399; text-decoration: none; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card glass">
            <h1>✓ Solicitud de eliminación procesada</h1>
            <ul>
                <li><?php echo htmlspecialchars($comp['datos_eliminados'] ?? 'Datos básicos eliminados de forma inmediata.'); ?></li>
                <li><?php echo htmlspecialchars($comp['retencion_legal'] ?? 'Retención legal de 10 años (Código de Comercio art. 44/132) hasta la purga tras auditoría.'); ?></li>
            </ul>
            <p style="margin-top:1rem;font-size:0.8rem;color:#9ca3af">Tu sesión fue cerrada. Para volver a usar el sistema se necesita un nuevo acceso.</p>
            <a class="back" href="<?php echo URL_ROOT; ?>/auth/login">← Volver al inicio de sesión</a>
        </div>
    </div>
</body>
</html>