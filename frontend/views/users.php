<?php
$users   = $users   ?? [];
$metrics = $metrics ?? ['total'=>0,'activos'=>0,'inactivos'=>0];
$success = $success ?? null;
$error   = $error   ?? null;
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-white">Gestión de Usuarios</h2>
        <p class="text-xs text-gray-500">Control total de acceso al sistema</p>
    </div>
</div>

<?php if ($success): ?>
<div class="glass border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 text-xs px-4 py-3 rounded-lg mb-4">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="glass border border-red-500/20 bg-red-500/10 text-red-400 text-xs px-4 py-3 rounded-lg mb-4">
    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<!-- FORMULARIO CREAR USUARIO -->
<details id="createUserForm" data-persist class="glass p-6 rounded-2xl mb-6 group bg-white/5 border border-white/10">
    <summary class="cursor-pointer list-none flex items-center justify-between text-sm font-bold text-white outline-none">
        <div class="flex items-center gap-2">
            <i class="fas fa-user-plus text-purple-400"></i> Crear Nuevo Usuario
        </div>
        <i class="fas fa-chevron-down text-gray-500 group-open:rotate-180 transition-transform"></i>
    </summary>
    <div class="pt-6 mt-4 border-t border-white/5">
        <form action="<?php echo URL_ROOT; ?>/users/store" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <?php echo csrf_field(); ?>
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Nombre Completo</label>
                <input type="text" name="full_name" placeholder="Ej: Juan Pérez" required
                       class="w-full px-4 py-3 rounded-xl text-sm bg-black/20 border border-white/10 text-white focus:border-purple-500 transition-all outline-none">
            </div>
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Usuario</label>
                <input type="text" placeholder="Asignado automáticamente" readonly
                       class="w-full px-4 py-3 rounded-xl text-sm bg-white/5 border border-white/5 text-gray-500 outline-none cursor-not-allowed">
            </div>
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Contraseña Predeterminada</label>
                <input type="text" value="Usuario123$" readonly
                       class="w-full px-4 py-3 rounded-xl text-sm bg-white/5 border border-white/5 text-purple-500 font-bold outline-none cursor-not-allowed">
            </div>

            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="px-6 py-3 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-lg active:scale-95">
                    <i class="fas fa-user-check mr-2"></i>Registrar Usuario
                </button>
            </div>
        </form>
    </div>
</details>

<!-- TABLA DE USUARIOS -->
<div class="glass rounded-2xl overflow-hidden">
    <div class="p-4 border-b border-white/5 flex items-center justify-between bg-black/10">
        <h3 class="text-sm font-bold text-white"><i class="fas fa-list text-purple-400 mr-2"></i>Lista de Usuarios</h3>
        <div class="relative w-44">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
            <input type="text" id="userSearch" placeholder="Buscar..."
                   class="w-full pl-8 pr-3 py-1.5 rounded-lg text-xs bg-black/20 border border-white/10 focus:border-purple-500 outline-none transition-colors">
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-[#080b14] border-b border-white/10">
                <tr>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Fecha Reg.</th>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Hora Reg.</th>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Nombre Completo</th>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Usuario</th>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider text-center">Estado</th>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-white/5 transition-colors group user-row">
                    <td class="py-3 px-4 text-xs text-gray-400 whitespace-nowrap"><?php echo date('d/m/Y', strtotime($u->created_at)); ?></td>
                    <td class="py-3 px-4 text-xs text-gray-500 whitespace-nowrap"><?php echo date('H:i:s', strtotime($u->created_at)); ?></td>
                    <td class="py-3 px-4 text-xs font-bold text-white searchable"><?php echo htmlspecialchars($u->full_name); ?></td>
                    <td class="py-3 px-4 text-xs text-purple-400 font-bold searchable"><?php echo htmlspecialchars($u->username); ?></td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($u->is_active): ?>
                        <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase bg-emerald-500/10 text-emerald-500">Activo</span>
                        <?php else: ?>
                        <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase bg-red-500/10 text-red-500">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex justify-center gap-2 transition-opacity">
                            <?php if ($u->username !== 'admin'): ?>
                            <a href="<?php echo URL_ROOT; ?>/history?audit=<?php echo urlencode($u->username); ?>" class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-500 hover:bg-emerald-500 hover:text-white transition-colors flex items-center justify-center" title="Auditar Movimientos">
                                <i class="fas fa-search-dollar text-xs"></i>
                            </a>
                            <button onclick='openEditUser(<?php echo json_encode($u); ?>)' class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-500 hover:bg-blue-500 hover:text-white transition-colors" title="Editar">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <button onclick="openToggleUser(<?php echo $u->id; ?>, <?php echo $u->is_active; ?>)" 
                                    class="w-7 h-7 rounded-lg bg-yellow-500/20 text-yellow-500 hover:bg-yellow-500 hover:text-white transition-colors" 
                                    title="<?php echo $u->is_active ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas <?php echo $u->is_active ? 'fa-user-slash' : 'fa-user-check'; ?> text-xs"></i>
                            </button>
                            <button onclick="openDeleteUser(<?php echo $u->id; ?>)" class="w-7 h-7 rounded-lg bg-red-500/20 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Eliminar">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODALES (Cierre solo por botón) -->

<!-- MODAL EDITAR -->
<div id="editUserModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm hidden">
    <div class="glass w-full max-w-md p-6 rounded-3xl">
        <h3 class="text-lg font-black text-white mb-6">Editar Usuario</h3>
        <form action="<?php echo URL_ROOT; ?>/users/update" method="POST" class="space-y-4">
        <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="edit_user_id">
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase mb-1 block">Nombre Completo</label>
                <input type="text" name="full_name" id="edit_user_name" required class="w-full px-4 py-2.5 rounded-xl text-sm bg-black/40 border border-white/10 text-white outline-none">
            </div>
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase mb-1 block">Usuario</label>
                <input type="text" name="username" id="edit_user_username" required class="w-full px-4 py-2.5 rounded-xl text-sm bg-black/40 border border-white/10 text-white outline-none">
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('editUserModal')" class="px-6 py-2 text-xs font-bold text-gray-400 hover:text-white transition-colors">Cerrar</button>
                <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TOGGLE ESTADO -->
<div id="toggleUserModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm hidden">
    <div class="glass w-full max-w-sm p-8 rounded-3xl text-center">
        <div id="toggleIcon" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"></div>
        <h3 id="toggleTitle" class="text-lg font-black text-white mb-2"></h3>
        <p class="text-xs text-gray-500 mb-8">El usuario perderá o recuperará el acceso al sistema inmediatamente.</p>
        <form action="<?php echo URL_ROOT; ?>/users/toggle" method="POST" class="flex flex-col gap-2">
        <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="toggle_user_id">
            <input type="hidden" name="status" id="toggle_user_status">
            <button type="submit" id="toggleBtn" class="w-full py-3 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all"></button>
            <button type="button" onclick="closeModal('toggleUserModal')" class="w-full py-3 text-gray-400 hover:text-white text-xs font-bold uppercase tracking-widest">Cerrar</button>
        </form>
    </div>
</div>

<!-- MODAL ELIMINAR -->
<div id="deleteUserModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm hidden">
    <div class="glass w-full max-w-sm p-8 rounded-3xl text-center">
        <div class="w-16 h-16 bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-xmark text-2xl"></i>
        </div>
        <h3 class="text-lg font-black text-white mb-2">¿Eliminar permanentemente?</h3>
        <p class="text-xs text-gray-500 mb-8">Esta acción eliminará al usuario y todos sus registros asociados. No se puede deshacer.</p>
        <form action="<?php echo URL_ROOT; ?>/users/delete" method="POST" class="flex flex-col gap-2">
        <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="delete_user_id">
            <button type="submit" class="w-full py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-bold uppercase tracking-widest">Confirmar Eliminación</button>
            <button type="button" onclick="closeModal('deleteUserModal')" class="w-full py-3 text-gray-400 hover:text-white text-xs font-bold uppercase tracking-widest">Cerrar</button>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
function openEditUser(u) {
    document.getElementById('edit_user_id').value = u.id;
    document.getElementById('edit_user_name').value = u.full_name;
    document.getElementById('edit_user_username').value = u.username;
    document.getElementById('editUserModal').classList.remove('hidden');
}

function openToggleUser(id, currentActive) {
    const newStatus = currentActive ? 0 : 1;
    document.getElementById('toggle_user_id').value = id;
    document.getElementById('toggle_user_status').value = newStatus;
    
    const icon = document.getElementById('toggleIcon');
    const title = document.getElementById('toggleTitle');
    const btn = document.getElementById('toggleBtn');
    
    if(newStatus) {
        icon.className = 'w-16 h-16 bg-emerald-500/20 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4';
        icon.innerHTML = '<i class="fas fa-user-check text-2xl"></i>';
        title.innerText = '¿Activar usuario?';
        btn.innerText = 'Activar Usuario';
        btn.className = 'w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold uppercase tracking-widest';
    } else {
        icon.className = 'w-16 h-16 bg-amber-500/20 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4';
        icon.innerHTML = '<i class="fas fa-user-slash text-2xl"></i>';
        title.innerText = '¿Desactivar usuario?';
        btn.innerText = 'Desactivar Usuario';
        btn.className = 'w-full py-3 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold uppercase tracking-widest';
    }
    document.getElementById('toggleUserModal').classList.remove('hidden');
}

function openDeleteUser(id) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('deleteUserModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

// Búsqueda simple
document.getElementById('userSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(row => {
        const text = Array.from(row.querySelectorAll('.searchable')).map(el => el.innerText.toLowerCase()).join(' ');
        row.style.display = text.includes(q) ? '' : 'none';
    });
});
</script>
<?php $extraScripts = ob_get_clean(); ?>
