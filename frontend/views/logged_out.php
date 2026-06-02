<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesión Cerrada - Sistema de Presupuesto Personal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/assets/css/app.css">
    <style>
        .glass-box {
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02), 0 8px 32px 0 rgba(0, 0, 0, 0.4);
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .float { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen relative flex items-center justify-center p-6 text-white overflow-hidden">
    
    <!-- Animated Gradients -->
    <div class="fixed top-[-20%] left-[-10%] w-[60%] h-[60%] bg-purple-600/10 blur-[150px] rounded-full pointer-events-none z-0"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-purple-600/10 blur-[150px] rounded-full pointer-events-none z-0"></div>

    <div class="glass-box rounded-3xl w-full max-w-md p-10 relative z-10 flex flex-col items-center text-center">
        <!-- Ícono flotante de Check -->
        <div class="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center shadow-[0_0_30px_rgba(16,185,129,0.4)] mb-6 float">
            <i class="fas fa-check text-4xl text-white"></i>
        </div>

        <h1 class="text-3xl font-black mb-2">¡Hasta Pronto!</h1>
        <p class="text-gray-400 text-sm mb-8">
            Tu sesión ha sido cerrada exitosamente y de forma segura. 
            Gracias por utilizar Sistema de Presupuesto Personal.
        </p>

        <a href="<?php echo URL_ROOT; ?>/auth/login" class="w-full py-4 bg-white text-black font-black uppercase tracking-widest text-xs rounded-xl shadow-lg hover:bg-gray-200 transition-all flex items-center justify-center gap-2 active:scale-95">
            <i class="fas fa-arrow-right"></i> Volver a Iniciar Sesión
        </a>
    </div>

</body>
</html>
