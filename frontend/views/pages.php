<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="bg-[#0f172a] text-white min-h-screen flex items-center justify-center overflow-hidden relative">
    <!-- Background Decor -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-purple-500/20 blur-[120px] rounded-full"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-500/20 blur-[120px] rounded-full"></div>

    <div class="glass p-12 rounded-xl max-w-lg w-full text-center relative z-10 shadow-2xl">
        <h1 class="text-4xl font-extrabold tracking-tighter mb-4">
            Bienvenido a <span class="text-purple-400">Presupey</span>
        </h1>
        <p class="text-gray-400 text-sm mb-8 leading-relaxed">
            La nueva era de la gestión financiera personal. Desacoplado, rápido y diseñado para cualquier dispositivo.
        </p>

        <div class="space-y-4">
            <a href="<?php echo URL_ROOT; ?>/auth/login"
                class="block w-full py-4 bg-purple-600 hover:bg-purple-500 transition-all rounded-lg font-bold text-sm tracking-widest uppercase shadow-lg shadow-purple-500/20">
                Comience ahora
            </a>
            <p class="text-[10px] text-gray-500 uppercase tracking-widest">Fase 1: Arquitectura MVC Completada</p>
        </div>
    </div>
</body>

</html>