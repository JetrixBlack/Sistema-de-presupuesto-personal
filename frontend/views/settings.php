<?php
$user = $user ?? (object)['username'=>'','full_name'=>'','role'=>'user'];
$canEdit = $canEdit ?? ['name'=>true, 'username'=>true, 'days_name'=>0, 'days_user'=>0];
$bcvRate = $bcvRate ?? 0;

$securityQuestions = [
    '¿Cuál es el nombre de tu primera mascota?',
    '¿En qué ciudad naciste?',
    '¿Cuál es el nombre de soltera de tu madre?',
    '¿Cuál fue el nombre de tu primera escuela?',
    '¿Cuál es tu película favorita de infancia?',
];
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-white">Configuración</h2>
        <p class="text-xs text-gray-500">Personaliza tu perfil y seguridad</p>
    </div>
</div>

<?php if (!empty($success)): ?>
<div class="glass border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 text-xs px-4 py-3 rounded-lg mb-4 animate-[fadeIn_0.3s]">
    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="glass border border-red-500/20 bg-red-500/10 text-red-400 text-xs px-4 py-3 rounded-lg mb-4 animate-[fadeIn_0.3s]">
    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="w-full max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- CONTENIDO APILADO -->

    <!-- CONTENIDO PERFIL -->
    <div class="glass p-8 rounded-2xl border border-white/10 flex flex-col justify-between">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-black shadow-xl shadow-purple-500/20 uppercase">
                <?php echo strtoupper(substr($user->username, 0, 1)); ?>
            </div>
            <div>
                <h3 class="text-white font-bold"><?php echo htmlspecialchars($user->full_name); ?></h3>
                <p class="text-xs text-gray-500 uppercase tracking-widest font-black">@<?php echo htmlspecialchars($user->username); ?></p>
            </div>
        </div>

        <?php if (!$canEdit['name'] || !$canEdit['username']): ?>
        <div class="mb-6 p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-start gap-3">
            <i class="fas fa-clock text-amber-500 mt-0.5"></i>
            <div>
                <p class="text-[10px] font-black text-amber-400 uppercase tracking-widest mb-1">Restricción de Edición</p>
                <p class="text-[11px] text-amber-200/80 leading-relaxed">
                    <?php if (!$canEdit['name']): ?>
                    • Nombre: Bloqueado por <b><?php echo $canEdit['days_name']; ?> días</b> más.<br>
                    <?php endif; ?>
                    <?php if (!$canEdit['username']): ?>
                    • Usuario: Bloqueado por <b><?php echo $canEdit['days_user']; ?> días</b> más.
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo URL_ROOT; ?>/settings/updateProfile" class="space-y-6">
            <?php echo csrf_field(); ?>
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Nombre Completo (Máx 80)</label>
                <div class="relative group">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-purple-400 transition-colors"></i>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user->full_name); ?>" 
                           maxlength="80" placeholder="Tu nombre..."
                           <?php echo !$canEdit['name'] ? 'readonly' : ''; ?>
                           class="w-full pl-11 pr-4 py-3.5 rounded-xl text-sm bg-black/40 border border-white/10 text-white focus:border-purple-500 outline-none transition-all <?php echo !$canEdit['name'] ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                </div>
            </div>

            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Nombre de Usuario (Máx 30)</label>
                <div class="relative group">
                    <i class="fas fa-at absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-purple-400 transition-colors"></i>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user->username); ?>" 
                           maxlength="30" placeholder="usuario..."
                           <?php echo !$canEdit['username'] ? 'readonly' : ''; ?>
                           class="w-full pl-11 pr-4 py-3.5 rounded-xl text-sm bg-black/40 border border-white/10 text-white focus:border-purple-500 outline-none transition-all <?php echo !$canEdit['username'] ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                </div>
            </div>

            <button type="submit" <?php echo (!$canEdit['name'] && !$canEdit['username']) ? 'disabled' : ''; ?>
                    class="w-full py-4 bg-purple-600 hover:bg-purple-500 disabled:opacity-30 disabled:cursor-not-allowed text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-purple-900/20 active:scale-95">
                <i class="fas fa-save mr-2"></i>Guardar Cambios
            </button>
        </form>
    </div>

    <!-- CONTENIDO SEGURIDAD -->
    <div class="glass p-8 rounded-2xl border border-white/10 flex flex-col justify-between">
        <h3 class="text-sm font-bold text-white mb-8 flex items-center gap-2">
            <i class="fas fa-shield-halved text-purple-400 text-xs"></i>
            Seguridad de la Cuenta
        </h3>

        <form method="POST" action="<?php echo URL_ROOT; ?>/settings/updateSecurity" class="space-y-6">
            <?php echo csrf_field(); ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Nueva Contraseña</label>
                    <div class="relative">
                        <input type="password" id="newPass" name="new_password" placeholder="••••••••" class="w-full px-4 py-3 rounded-xl text-sm bg-black/40 border border-white/10 text-white outline-none focus:border-purple-500 transition-all pr-10">
                        <button type="button" onclick="togglePw('newPass', 'eye1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition-colors">
                            <i id="eye1" class="fas fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Confirmar Contraseña</label>
                    <div class="relative">
                        <input type="password" id="confPass" name="confirm_password" placeholder="••••••••" class="w-full px-4 py-3 rounded-xl text-sm bg-black/40 border border-white/10 text-white outline-none focus:border-purple-500 transition-all pr-10">
                        <button type="button" onclick="togglePw('confPass', 'eye2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition-colors">
                            <i id="eye2" class="fas fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-white/5">
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Pregunta de Seguridad</label>
                <select name="security_question" class="w-full px-4 py-3 rounded-xl text-sm bg-black/40 border border-white/10 text-white outline-none focus:border-purple-500 transition-all mb-4 appearance-none">
                    <?php foreach ($securityQuestions as $q): ?>
                    <option class="bg-[#080b14] text-white" value="<?php echo $q; ?>" <?php echo ($user->security_question ?? '') === $q ? 'selected' : ''; ?>><?php echo $q; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="password" name="security_answer" placeholder="Respuesta de seguridad..." class="w-full px-4 py-3 rounded-xl text-sm bg-black/40 border border-white/10 text-white outline-none focus:border-purple-500 transition-all">
            </div>

            <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg active:scale-95">
                <i class="fas fa-lock mr-2"></i>Actualizar Seguridad
            </button>
        </form>
    </div>

<!-- TASA BCV MANUAL (USD) -->
    <div class="glass p-8 rounded-2xl border border-white/10 flex flex-col justify-between">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
            <i class="fas fa-dollar-sign text-emerald-400"></i>
        </div>
        <div>
            <h3 class="text-sm font-bold text-white">Tasa BCV (Dólar)</h3>
            <p class="text-[10px] text-gray-500">Se actualiza automáticamente con internet. Usa este formulario si no hay conexión.</p>
        </div>
    </div>

    <div class="mb-5 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center justify-between">
        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Tasa Actual Guardada</span>
        <span class="text-lg font-black text-emerald-400" id="currentBcvDisplay">
            <?php echo $bcvRate > 0 ? 'Bs. ' . number_format($bcvRate, 2, ',', '.') : 'Cargando...'; ?>
        </span>
    </div>

    <form method="POST" action="<?php echo URL_ROOT; ?>/dashboard/updateBcvRate" class="flex gap-3 items-end">
        <?php echo csrf_field(); ?>
        <div class="flex-1">
            <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Nueva Tasa Manual (Bs.)</label>
            <div class="relative group">
                <i class="fas fa-coins absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-emerald-400 transition-colors"></i>
                <input type="text" name="bcv_rate" placeholder="Ej: 96,50" required
                       class="w-full pl-11 pr-4 py-3.5 rounded-xl text-sm bg-black/40 border border-white/10 text-white focus:border-emerald-500 outline-none transition-all">
            </div>
        </div>
        <button type="submit" class="px-6 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg whitespace-nowrap">
            <i class="fas fa-save mr-2"></i>Guardar
        </button>
    </form>
    </div>

    <!-- TASA BCV MANUAL (EUR) -->
    <div class="glass p-8 rounded-2xl border border-white/10 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                    <i class="fas fa-euro-sign text-blue-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Tasa Banco Central (Euro)</h3>
                    <p class="text-[10px] text-gray-500">Actualiza manualmente la tasa oficial del BCV para euros.</p>
                </div>
            </div>

            <div class="mb-5 p-3 bg-blue-500/10 border border-blue-500/20 rounded-xl flex items-center justify-between">
                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Tasa Actual Guardada</span>
                <span class="text-lg font-black text-blue-400">
                    <?php echo isset($euroRate) && $euroRate > 0 ? 'Bs. ' . number_format($euroRate, 2, ',', '.') : 'No registrada'; ?>
                </span>
            </div>
        </div>

        <form method="POST" action="<?php echo URL_ROOT; ?>/dashboard/updateEuroRate" class="flex gap-3 items-end">
            <?php echo csrf_field(); ?>
            <div class="flex-1">
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 block">Nueva Tasa Manual (Bs.)</label>
                <div class="relative group">
                    <i class="fas fa-coins absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-blue-400 transition-colors"></i>
                    <input type="text" name="euro_rate" placeholder="Ej: 41,50" required
                           class="w-full pl-11 pr-4 py-3.5 rounded-xl text-sm bg-black/40 border border-white/10 text-white focus:border-blue-500 outline-none transition-all">
                </div>
            </div>
            <button type="submit" class="px-6 py-3.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg whitespace-nowrap">
                <i class="fas fa-save mr-2"></i>Guardar
            </button>
        </form>
    </div>


</div>

<?php ob_start(); ?>
<script>
function togglePw(id, eyeId) {
    const input = document.getElementById(id);
    const eye = document.getElementById(eyeId);
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        eye.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
<?php $extraScripts = ob_get_clean(); ?>
