<?php
if (!isset($data)) { $data = []; }
$title = 'Eliminar mis datos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> · Sistema de Presupuesto Personal</title>
    <meta name="description" content="Solicitud de eliminación de datos personales (hábeas data).">
    <link rel="icon" type="image/png" href="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0b1121 0%, #170f30 100%); color: white; min-height: 100vh; }
        .glass { background: rgba(0,0,0,0.45); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.12); border-radius: 1rem; }
        .wrap { max-width: 520px; margin: 0 auto; padding: 2rem 1.25rem 3rem; }
        .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        .topbar a.back { color: #9ca3af; text-decoration: none; font-size: 0.85rem; }
        .topbar a.back:hover { color: #34d399; }
        .card { padding: 1.5rem; }
        h1 { font-size: 1.4rem; margin-bottom: 0.5rem; }
        .hint { color: #9ca3af; font-size: 0.8rem; margin-bottom: 1.25rem; line-height: 1.6; }
        label { display: block; font-size: 0.85rem; margin: 0.9rem 0 0.35rem; color: #d1d5db; }
        input { width: 100%; padding: 0.7rem 0.85rem; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.06); color: white; font-size: 0.9rem; }
        button { width: 100%; margin-top: 1.25rem; padding: 0.8rem; border: none; border-radius: 0.6rem; background: #dc2626; color: white; font-weight: 700; cursor: pointer; }
        button:hover { background: #b91c1c; }
        .err { background: rgba(220,38,38,0.15); border: 1px solid #dc2626; color: #fca5a5; padding: 0.7rem 1rem; border-radius: 0.6rem; margin-bottom: 1rem; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="topbar">
            <span style="font-weight:800;color:#34d399">Sistema de Presupuesto Personal</span>
            <a class="back" href="<?php echo URL_ROOT; ?>/dashboard">← Volver</a>
        </div>
        <div class="card glass">
            <h1>Eliminar mis datos (Hábeas Data)</h1>
            <p class="hint">Para confirmar que eres tú necesitamos una <strong>doble verificación</strong>: tu contraseña actual y la respuesta a tu pregunta de seguridad. Tus datos básicos se eliminarán de inmediato; los registros financieros quedarán anonimizados durante <strong>10 años</strong> (Código de Comercio, art. 44 y 132) hasta la purga tras auditoría.</p>
            <?php if (!empty($data['error'])): ?>
                <div class="err"><?php echo htmlspecialchars($data['error']); ?></div>
            <?php endif; ?>
            <form method="POST" action="<?php echo URL_ROOT; ?>/auth/eliminarDatos" onsubmit="return confirm('Se eliminarán tus datos y se anonimizarán los registros financieros 10 años. ¿Continuar?')">
                <label for="current_password">Contraseña actual</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="current-password">
                <label for="answer">Respuesta de seguridad</label>
                <input type="password" name="answer" id="answer" required autocomplete="off" placeholder="Tu respuesta a la pregunta de seguridad">
                <button type="submit">Eliminar mis datos</button>
            </form>
        </div>
    </div>
</body>
</html>