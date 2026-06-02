<!DOCTYPE html>
<?php
// Helper CSRF para vistas standalone (sin layout)
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        $token = \Core\Auth::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
?>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Presupuesto Personal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0b1121 0%, #170f30 100%);
            color: white;
            min-height: 100vh;
        }

        .glass {
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02), 0 8px 32px 0 rgba(0, 0, 0, 0.4);
        }

        .input-glass {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .input-glass:focus {
            border-color: #a855f7;
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.3);
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .float {
            animation: float 6s ease-in-out infinite;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
        }
    </style>
    <link rel="icon" type="image/png" href="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png">
</head>

<body class="min-h-screen relative flex items-center justify-center p-6 text-white overflow-hidden">
    
    <!-- Particles Background -->
    <canvas id="particleCanvas" class="absolute inset-0 z-0 pointer-events-none"></canvas>

    <!-- Animated Gradients -->
    <div class="fixed top-[-20%] left-[-10%] w-[60%] h-[60%] bg-purple-600/10 blur-[150px] rounded-full pointer-events-none z-0"></div>
    <div class="fixed bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-purple-600/10 blur-[150px] rounded-full pointer-events-none z-0"></div>

    <!-- Contenedor Principal Vertical -->
    <div class="glass max-w-md w-full rounded-3xl shadow-2xl relative z-10 flex flex-col overflow-hidden p-8 mx-auto border border-white/5">
        
        <!-- Header: Bienvenida -->
        <div class="flex flex-col items-center justify-center text-center mb-8 border-b border-white/5 pb-6">
            <div class="w-16 h-16 mb-4 float">
                <img src="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png" alt="Sistema de Presupuesto Personal"
                    class="w-full h-full object-contain drop-shadow-[0_0_15px_rgba(168,85,247,0.4)]">
            </div>
            <h1 class="text-3xl font-black mb-1 leading-tight drop-shadow-lg tracking-tighter uppercase">Bienvenido</h1>
            <p class="text-gray-400 text-[10px] uppercase tracking-[0.2em]">Accede a tu panel financiero</p>
        </div>

        <!-- Formulario -->
        <div class="flex flex-col justify-center relative">
            <?php if (isset($data['error'])): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] uppercase font-bold tracking-widest p-3 rounded-lg mb-6 text-center animate-pulse">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $data['error']; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo URL_ROOT; ?>/auth/login" method="POST" class="space-y-6">
        <?php echo csrf_field(); ?>
                <div>
                    <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-2 mb-2 block">Usuario</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 text-sm"></i>
                        <input type="text" name="username" required 
                            class="w-full pl-12 pr-6 py-4 input-glass rounded-xl outline-none text-sm font-bold placeholder:text-gray-700" 
                            placeholder="ej: administrador">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-2 mb-2 block">Contraseña</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 text-sm"></i>
                        <input type="password" name="password" id="password" required 
                            class="w-full pl-12 pr-12 py-4 input-glass rounded-xl outline-none text-sm font-bold placeholder:text-gray-700" 
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-purple-400 focus:outline-none transition-colors">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 transition-all rounded-xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-purple-900/20 transform active:scale-[0.98] mt-2">
                    Ingresar
                </button>
                
                <div class="text-center mt-6">
                    <a href="<?php echo URL_ROOT; ?>/auth/recover" class="text-[10px] font-bold text-gray-500 hover:text-purple-400 uppercase tracking-widest transition-colors">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Particle System
        const canvas = document.getElementById('particleCanvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        window.addEventListener('resize', resize);
        resize();

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2;
                this.speedX = Math.random() * 0.5 - 0.25;
                this.speedY = Math.random() * 0.5 - 0.25;
                this.opacity = Math.random() * 0.5;
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                if (this.x > canvas.width) this.x = 0;
                if (this.x < 0) this.x = canvas.width;
                if (this.y > canvas.height) this.y = 0;
                if (this.y < 0) this.y = canvas.height;
            }

            draw() {
                ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function init() {
            for (let i = 0; i < 100; i++) {
                particles.push(new Particle());
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animate);
        }

        init();
        animate();
    </script>
</body>

</html>