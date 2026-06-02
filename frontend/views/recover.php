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
    <title>Recuperar Contraseña - Sistema de Presupuesto Personal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0b1121 0%, #170f30 100%); color: white; min-height: 100vh; }
        .glass { background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.04); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02), 0 8px 32px 0 rgba(0, 0, 0, 0.4); }
        .input-glass { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease; }
        .input-glass:focus { border-color: #a855f7; box-shadow: 0 0 15px rgba(168, 85, 247, 0.3); }
        .step-dot { transition: all 0.5s ease; }
        .step-active { background: #a855f7; box-shadow: 0 0 15px #a855f7; }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
        .float { animation: float 6s ease-in-out infinite; }
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
        
        <!-- Header: Recuperación -->
        <div class="flex flex-col items-center justify-center text-center mb-8 border-b border-white/5 pb-6">
            <div class="w-16 h-16 mb-4 float">
                <img src="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png" alt="Sistema de Presupuesto Personal"
                    class="w-full h-full object-contain drop-shadow-[0_0_15px_rgba(168,85,247,0.4)]">
            </div>
            <h1 class="text-3xl font-black mb-1 uppercase tracking-tighter">Recuperación</h1>
            <p class="text-gray-400 text-[10px] uppercase tracking-[0.2em]">Restablece tu acceso</p>
        </div>

        <!-- Formulario -->
        <div class="flex flex-col justify-center relative">
            
            <!-- STEPS INDICATOR -->
            <div class="flex items-center justify-between mb-8 px-2">
                <?php for($i=1; $i<=4; $i++): ?>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-black border border-white/10 step-dot <?php echo $step >= $i ? 'step-active border-purple-500' : 'bg-white/5 text-gray-500'; ?>">
                            <?php if($step > $i): ?><i class="fas fa-check"></i><?php else: echo $i; endif; ?>
                        </div>
                    </div>
                    <?php if($i < 4): ?>
                        <div class="flex-1 h-[1px] bg-white/5 mb-4 mx-2">
                            <div class="h-full bg-purple-500 transition-all duration-500" style="width: <?php echo $step > $i ? '100%' : '0%'; ?>"></div>
                        </div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

            <p class="text-gray-500 text-[10px] uppercase tracking-[0.2em] mb-6 text-center">Paso <?php echo $step; ?> de 4</p>
            
            <?php if(isset($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] uppercase font-bold tracking-widest p-3 rounded-lg mb-6 text-center animate-pulse">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- STEP 1: USERNAME -->
            <?php if($step == 1): ?>
                <form action="<?php echo URL_ROOT; ?>/auth/recover?step=1" method="POST" class="space-y-6">
        <?php echo csrf_field(); ?>
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-2 mb-2 block">Nombre de Usuario</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 text-sm"></i>
                            <input type="text" name="username" required 
                                class="w-full pl-12 pr-6 py-4 input-glass rounded-xl outline-none text-sm font-bold placeholder:text-gray-700" 
                                placeholder="ej: administrador">
                        </div>
                    </div>
                    <button type="submit" 
                        class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 transition-all rounded-xl font-black text-xs uppercase tracking-widest shadow-xl shadow-purple-900/20 transform active:scale-[0.98]">
                        Continuar <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>

            <!-- STEP 2: SECURITY QUESTION -->
            <?php elseif($step == 2): ?>
                <form action="<?php echo URL_ROOT; ?>/auth/recover?step=2" method="POST" class="space-y-6">
        <?php echo csrf_field(); ?>
                    <div class="p-4 bg-purple-500/5 border border-purple-500/20 rounded-xl mb-4 text-left">
                        <p class="text-[10px] text-purple-400 font-bold uppercase tracking-widest mb-2">Pregunta de Seguridad:</p>
                        <p class="text-sm font-semibold text-white"><?php echo $question; ?></p>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-2 mb-2 block">Tu Respuesta</label>
                        <input type="text" name="answer" required 
                            class="w-full px-6 py-4 input-glass rounded-xl outline-none text-sm font-bold placeholder:text-gray-700" 
                            placeholder="Escribe la respuesta aquí">
                    </div>
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 transition-all rounded-xl font-black text-xs uppercase tracking-widest shadow-xl shadow-purple-900/20 transform active:scale-[0.98]">
                        Verificar <i class="fas fa-shield-check ml-2"></i>
                    </button>
                </form>

            <!-- STEP 3: NEW PASSWORD -->
            <?php elseif($step == 3): ?>
                <form action="<?php echo URL_ROOT; ?>/auth/recover?step=3" method="POST" class="space-y-6" id="recoverForm">
        <?php echo csrf_field(); ?>
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-2 mb-2 block">Nueva Contraseña</label>
                        <input type="password" name="password" id="new_pwd" required 
                            class="w-full px-6 py-4 input-glass rounded-xl outline-none text-sm font-bold placeholder:text-gray-700" 
                            placeholder="••••••••">
                        <p id="pwd_error" class="text-red-400 text-[10px] mt-2 hidden ml-2">Mín. 8 chars, 1 mayúscula, 1 minúscula, 1 número.</p>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest ml-2 mb-2 block">Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" id="conf_pwd" required 
                            class="w-full px-6 py-4 input-glass rounded-xl outline-none text-sm font-bold placeholder:text-gray-700" 
                            placeholder="••••••••">
                        <p id="match_error" class="text-red-400 text-[10px] mt-2 hidden ml-2">Las contraseñas no coinciden.</p>
                    </div>
                    <button type="submit" id="btn_submit" class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 transition-all rounded-xl font-black text-xs uppercase tracking-widest shadow-xl shadow-purple-900/20 transform active:scale-[0.98]">
                        Cambiar Contraseña <i class="fas fa-key ml-2"></i>
                    </button>
                </form>
                <script>
                    const pwd = document.getElementById('new_pwd');
                    const conf = document.getElementById('conf_pwd');
                    const btn = document.getElementById('btn_submit');
                    const pwdError = document.getElementById('pwd_error');
                    const matchError = document.getElementById('match_error');

                    function validate() {
                        let valid = true;
                        const pVal = pwd.value;
                        const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

                        if (pVal.length > 0 && !regex.test(pVal)) {
                            pwdError.classList.remove('hidden');
                            valid = false;
                        } else {
                            pwdError.classList.add('hidden');
                        }

                        if (conf.value.length > 0 && pVal !== conf.value) {
                            matchError.classList.remove('hidden');
                            valid = false;
                        } else {
                            matchError.classList.add('hidden');
                        }

                        if (!pVal || !conf.value) valid = false;
                        btn.disabled = !valid;
                        btn.style.opacity = valid ? '1' : '0.5';
                    }

                    pwd.addEventListener('input', validate);
                    conf.addEventListener('input', validate);
                    validate();
                </script>

            <!-- STEP 4: SUCCESS -->
            <?php elseif($step == 4): ?>
                <div class="text-center space-y-6">
                    <div class="w-16 h-16 bg-emerald-500/20 text-emerald-500 rounded-full flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-500/10">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white uppercase tracking-tighter">¡Actualizada!</h3>
                    <p class="text-gray-400 text-sm">Tu contraseña ha sido restablecida con éxito. Ya puedes iniciar sesión de forma segura.</p>
                    <a href="<?php echo URL_ROOT; ?>/auth/login" class="block w-full py-4 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 transition-all rounded-xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-900/20 transform active:scale-[0.98]">
                        Ir al Login
                    </a>
                </div>
            <?php endif; ?>

            <?php if($step != 4): ?>
            <div class="mt-6 text-center">
                <a href="<?php echo URL_ROOT; ?>/auth/login" class="text-[9px] font-black text-gray-600 hover:text-purple-400 uppercase tracking-widest transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al Inicio
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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
