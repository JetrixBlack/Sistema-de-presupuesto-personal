<?php
$tickets     = $tickets     ?? [];
$isAdmin     = $isAdmin     ?? false;
$ticketTypes = $ticketTypes ?? [];
$stats       = $stats       ?? [];
$range       = $range       ?? 'all';
$statusLabels = ['pendiente' => 'Pendiente', 'en_proceso' => 'En Proceso', 'resuelto' => 'Resuelto'];
$statusColors = ['pendiente' => 'text-red-400 bg-red-500/10 border-red-500/20', 'en_proceso' => 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20', 'resuelto' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20'];
?>
<?php if (!empty($success)): ?>
<div class="glass border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 text-xs px-4 py-3 rounded-lg mb-4">
    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="glass border border-red-500/20 bg-red-500/10 text-red-400 text-xs px-4 py-3 rounded-lg mb-4">
    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-white"><?php echo $isAdmin ? 'Gestión de Soporte' : 'Centro de Soporte'; ?></h2>
        <p class="text-xs text-gray-500"><?php echo $isAdmin ? 'Administra los tickets de los usuarios' : 'Reporta problemas o envía sugerencias'; ?></p>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- ═══════════════ VISTA ADMIN ═══════════════ -->

<!-- Tarjetas métricas -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <?php
    $pending   = $stats['pendiente']  ?? 0;
    $inProcess = $stats['en_proceso'] ?? 0;
    $resolved  = $stats['resuelto']   ?? 0;
    $total     = $pending + $inProcess + $resolved;
    $cards = [
        ['Pendientes',  $pending,   'fa-clock',        'text-red-400',     'bg-red-500/10'],
        ['En Proceso',  $inProcess, 'fa-spinner',      'text-yellow-400',  'bg-yellow-500/10'],
        ['Resueltos',   $resolved,  'fa-check-circle', 'text-emerald-400', 'bg-emerald-500/10'],
    ];
    foreach ($cards as [$label, $val, $icon, $color, $bg]): ?>
    <div class="glass p-5 rounded-2xl flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl <?php echo $bg; ?> flex items-center justify-center flex-shrink-0">
            <i class="fas <?php echo $icon; ?> <?php echo $color; ?>"></i>
        </div>
        <div>
            <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest"><?php echo $label; ?></p>
            <p class="text-2xl font-black <?php echo $color; ?>"><?php echo $val; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>



<!-- Lista de todos los tickets -->
<div class="glass rounded-2xl overflow-hidden">
    <div class="p-4 border-b border-white/5 bg-black/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h3 class="text-sm font-bold text-white"><i class="fas fa-ticket text-purple-400 mr-2"></i>Todos los Tickets</h3>
        <div class="relative w-full md:w-64">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-[10px]"></i>
            <input type="text" id="adminTicketSearch" placeholder="Buscar por usuario o descripción..."
                   class="w-full pl-8 pr-3 py-1.5 rounded-xl text-[10px] bg-black/20 border border-white/10 focus:border-purple-500 outline-none transition-all">
        </div>
    </div>
    <div id="adminTicketList" class="divide-y divide-white/5">
        <?php if (empty($tickets)): ?>
        <div class="p-8 text-center text-gray-500 text-xs">No hay tickets registrados.</div>
        <?php endif; ?>
        <?php foreach ($tickets as $t): ?>
        <div class="p-4 hover:bg-white/5 transition-colors ticket-item">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-purple-600/20 flex items-center justify-center flex-shrink-0 font-bold text-purple-400 text-sm overflow-hidden">
                    <?php echo strtoupper(mb_substr($t->full_name ?: $t->username, 0, 1)); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-1 flex-wrap">
                        <p class="text-xs font-bold text-white searchable"><?php echo htmlspecialchars($t->full_name ?: $t->username); ?></p>
                        <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border <?php echo $statusColors[$t->status] ?? ''; ?>">
                            <?php echo $statusLabels[$t->status] ?? $t->status; ?>
                        </span>
                        <span class="text-[9px] text-gray-500 bg-white/5 px-2 py-0.5 rounded-full">
                            <?php echo $ticketTypes[$t->type] ?? $t->type; ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mb-2 searchable"><?php echo nl2br(htmlspecialchars($t->description)); ?></p>
                    <?php if ($t->admin_note): ?>
                    <div class="mt-2 p-2.5 bg-purple-500/10 border border-purple-500/20 rounded-lg inline-block">
                        <p class="text-[10px] text-purple-300"><i class="fas fa-reply mr-1"></i><strong>Nota Admin:</strong> <?php echo htmlspecialchars($t->admin_note); ?></p>
                    </div>
                    <?php endif; ?>
                    <p class="text-[9px] text-gray-600 mt-2"><?php echo date('d M Y, H:i', strtotime($t->created_at)); ?></p>
                </div>
                <!-- Acciones admin -->
                <?php if ($t->status !== 'resuelto'): ?>
                <form action="<?php echo URL_ROOT; ?>/support/status" method="POST" class="flex-shrink-0 flex flex-col gap-2 min-w-[160px]">
        <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $t->id; ?>">
                    <select name="status" class="px-3 py-1.5 rounded-lg text-[10px] bg-[#0b1120] border border-white/10 text-white focus:border-purple-500 outline-none">
                        <option class="bg-[#080b14] text-white" value="pendiente"  <?php echo $t->status === 'pendiente'  ? 'selected' : ''; ?>>Pendiente</option>
                        <option class="bg-[#080b14] text-white" value="en_proceso" <?php echo $t->status === 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                        <option class="bg-[#080b14] text-white" value="resuelto"   <?php echo $t->status === 'resuelto'   ? 'selected' : ''; ?>>Resuelto</option>
                    </select>
                    <input type="text" name="admin_note" placeholder="Responder al usuario..." value="<?php echo htmlspecialchars($t->admin_note ?? ''); ?>"
                           class="px-3 py-1.5 rounded-lg text-[10px] bg-black/20 border border-white/10 text-white placeholder:text-gray-600 focus:border-purple-500 outline-none">
                    <button type="submit" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all">
                        <i class="fas fa-save mr-1"></i>Actualizar
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Paginación Admin -->
    <div id="adminPagination" class="p-4 border-t border-white/5 bg-black/10 flex items-center justify-between">
        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Página <span id="adminCurrentPage">1</span> de <span id="adminTotalPages">1</span></p>
        <div class="flex gap-2">
            <button id="adminPrevBtn" class="px-4 py-1.5 rounded-lg bg-white/10 border border-white/20 text-white text-[10px] font-bold hover:bg-purple-600 hover:border-purple-500 transition-all disabled:opacity-30 disabled:pointer-events-none">← Anterior</button>
            <button id="adminNextBtn" class="px-4 py-1.5 rounded-lg bg-white/10 border border-white/20 text-white text-[10px] font-bold hover:bg-purple-600 hover:border-purple-500 transition-all disabled:opacity-30 disabled:pointer-events-none">Siguiente →</button>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════ VISTA USUARIO ═══════════════ -->

<!-- Formulario crear ticket -->
<details class="w-full glass p-6 rounded-2xl mb-6 group bg-white/5 border border-white/10" id="ticketForm" data-persist>
    <summary class="cursor-pointer list-none flex items-center justify-between text-sm font-bold text-white outline-none">
        <div class="flex items-center gap-2">
            <i class="fas fa-plus-circle text-purple-400"></i> Nuevo Reporte / Ticket
        </div>
        <i class="fas fa-chevron-down text-gray-500 group-open:rotate-180 transition-transform"></i>
    </summary>
    <div class="pt-6 mt-4 border-t border-white/5">
        <form action="<?php echo URL_ROOT; ?>/support/store" method="POST" enctype="multipart/form-data" class="space-y-4">
        <?php echo csrf_field(); ?>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Tipo de Problema</label>
                    <select name="type" class="w-full px-4 py-3 rounded-xl text-sm bg-black/20 border border-white/10 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition-all" required>
                        <?php foreach ($ticketTypes as $val => $lbl): ?>
                        <option class="bg-[#080b14] text-white" value="<?php echo $val; ?>"><?php echo $lbl; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Descripción del Problema</label>
                <textarea name="description" rows="4" required placeholder="Describe el problema con el mayor detalle posible..."
                          class="w-full px-4 py-3 rounded-xl text-sm bg-black/20 border border-white/10 text-white placeholder:text-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition-all resize-none"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-lg active:scale-95">
                    <i class="fas fa-paper-plane mr-2"></i>Enviar Ticket
                </button>
            </div>
        </form>
    </div>
</details>

<!-- Lista de mis tickets -->
<div class="glass rounded-2xl overflow-hidden">
    <div class="p-4 border-b border-white/5 bg-black/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h3 class="text-sm font-bold text-white"><i class="fas fa-ticket text-purple-400 mr-2"></i>Mis Tickets</h3>
        <div class="relative w-full md:w-64">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-[10px]"></i>
            <input type="text" id="userTicketSearch" placeholder="Buscar en mis tickets..."
                   class="w-full pl-8 pr-3 py-1.5 rounded-xl text-[10px] bg-black/20 border border-white/10 focus:border-purple-500 outline-none transition-all">
        </div>
    </div>
    <div id="userTicketList" class="divide-y divide-white/5">
        <?php if (empty($tickets)): ?>
        <div class="p-10 text-center">
            <i class="fas fa-inbox text-3xl text-gray-700 mb-3 block"></i>
            <p class="text-sm text-gray-500">No tienes tickets enviados aún.</p>
        </div>
        <?php endif; ?>
        <?php foreach ($tickets as $t): ?>
        <div class="p-4 hover:bg-white/5 transition-colors ticket-item">
            <div class="flex items-start gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border <?php echo $statusColors[$t->status] ?? ''; ?>">
                            <?php echo $statusLabels[$t->status] ?? $t->status; ?>
                        </span>
                        <span class="text-[9px] text-gray-500 bg-white/5 px-2 py-0.5 rounded-full">
                            <?php echo $ticketTypes[$t->type] ?? $t->type; ?>
                        </span>
                        <span class="text-[9px] text-gray-600"><?php echo date('d M Y, H:i', strtotime($t->created_at)); ?></span>
                    </div>
                    <p class="text-xs text-gray-300 searchable"><?php echo nl2br(htmlspecialchars($t->description)); ?></p>
                    <?php if ($t->admin_note): ?>
                    <div class="mt-2 p-2.5 bg-yellow-500/10 border border-yellow-500/20 rounded-lg inline-block">
                        <p class="text-[10px] text-yellow-400"><i class="fas fa-reply mr-1"></i><strong>Respuesta del Soporte:</strong> <?php echo htmlspecialchars($t->admin_note); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($t->status === 'pendiente'): ?>
                <div class="flex gap-2 flex-shrink-0">
                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($t)); ?>)"
                            class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 hover:bg-blue-500 hover:text-white transition-colors" title="Editar">
                        <i class="fas fa-pen text-xs"></i>
                    </button>
                    <button type="button" onclick="openDeleteModal(<?php echo $t->id; ?>)" 
                            class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white transition-colors" title="Eliminar">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Paginación Usuario -->
    <div id="userPagination" class="p-4 border-t border-white/5 bg-black/10 flex items-center justify-between">
        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Página <span id="userCurrentPage">1</span> de <span id="userTotalPages">1</span></p>
        <div class="flex gap-2">
            <button id="userPrevBtn" class="px-4 py-1.5 rounded-lg bg-white/10 border border-white/20 text-white text-[10px] font-bold hover:bg-purple-600 hover:border-purple-500 transition-all disabled:opacity-30 disabled:pointer-events-none">← Anterior</button>
            <button id="userNextBtn" class="px-4 py-1.5 rounded-lg bg-white/10 border border-white/20 text-white text-[10px] font-bold hover:bg-purple-600 hover:border-purple-500 transition-all disabled:opacity-30 disabled:pointer-events-none">Siguiente →</button>
        </div>
    </div>
</div>
<?php endif; ?>



<!-- Modal eliminar ticket -->
<div id="deleteTicketModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(0,0,0,0.85)">
    <div class="glass p-8 rounded-3xl w-full max-w-sm text-center">
        <div class="w-16 h-16 bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
        </div>
        <h3 class="text-lg font-black text-white mb-2 uppercase">¿Eliminar Ticket?</h3>
        <p class="text-gray-400 text-xs mb-8">Esta acción no se puede deshacer. El ticket será removido permanentemente del sistema.</p>
        
        <form action="<?php echo URL_ROOT; ?>/support/delete" method="POST" class="flex gap-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="deleteId">
            <button type="button" onclick="closeDeleteTicketModal()" class="flex-1 py-3 rounded-xl text-xs font-bold text-gray-400 border border-white/10 hover:bg-white/5 transition-all uppercase">Cancelar</button>
            <button type="submit" class="flex-1 py-3 bg-red-600 hover:bg-red-500 rounded-xl text-xs font-bold text-white uppercase tracking-widest shadow-lg shadow-red-900/20 transition-all">Eliminar</button>
        </form>
    </div>
</div>

<!-- Modal editar ticket (usuario) -->
<div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" style="background:rgba(0,0,0,0.85)">
    <div class="glass p-6 rounded-2xl w-full max-w-lg">
        <h3 class="text-sm font-bold text-white mb-4"><i class="fas fa-pen text-purple-400 mr-2"></i>Editar Ticket</h3>
        <form action="<?php echo URL_ROOT; ?>/support/edit" method="POST" enctype="multipart/form-data" class="space-y-4">
        <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="editId">
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Tipo</label>
                <select name="type" id="editType" class="w-full px-4 py-3 rounded-xl text-sm bg-[#0b1120] border border-white/10 text-white focus:border-purple-500 outline-none">
                    <?php foreach ($ticketTypes as $val => $lbl): ?>
                    <option class="bg-[#080b14] text-white" value="<?php echo $val; ?>"><?php echo $lbl; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Descripción</label>
                <textarea name="description" id="editDesc" rows="4" class="w-full px-4 py-3 rounded-xl text-sm bg-black/20 border border-white/10 text-white focus:border-purple-500 outline-none resize-none"></textarea>
            </div>
            <div class="flex gap-3 justify-end pt-2 border-t border-white/5">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-400 border border-white/10 hover:bg-white/5 transition-all">Cerrar</button>
                <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-500 rounded-xl text-xs font-bold text-white uppercase tracking-wider transition-all">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
// Modal editar ticket (usuario)
function openEditModal(t) {
    document.getElementById('editId').value   = t.id;
    document.getElementById('editType').value = t.type;
    document.getElementById('editDesc').value = t.description;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }

// Funciones Modal Eliminar
function openDeleteModal(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteTicketModal').classList.remove('hidden');
}
function closeDeleteTicketModal() {
    document.getElementById('deleteTicketModal').classList.add('hidden');
}

// Lógica de Búsqueda y Paginación
function initPagination(listId, searchId, prevBtnId, nextBtnId, currentPageId, totalPagesId) {
    const list = document.getElementById(listId);
    if (!list) return;

    const searchInput = document.getElementById(searchId);
    const prevBtn = document.getElementById(prevBtnId);
    const nextBtn = document.getElementById(nextBtnId);
    const currentPageSpan = document.getElementById(currentPageId);
    const totalPagesSpan = document.getElementById(totalPagesId);
    
    const itemsPerPage = 5;
    let currentPage = 1;
    let filteredItems = [];

    function updateList() {
        const items = Array.from(list.querySelectorAll('.ticket-item'));
        const query = searchInput.value.toLowerCase().trim();

        // Filtrar
        filteredItems = items.filter(item => {
            const text = Array.from(item.querySelectorAll('.searchable'))
                              .map(el => el.textContent.toLowerCase())
                              .join(' ');
            return text.includes(query);
        });

        // Paginación
        const totalItems = filteredItems.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
        
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;

        // Ocultar todos y mostrar solo el rango
        items.forEach(item => item.classList.add('hidden'));
        filteredItems.slice(start, end).forEach(item => item.classList.remove('hidden'));

        // Actualizar UI
        currentPageSpan.textContent = currentPage;
        totalPagesSpan.textContent = totalPages;
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages;

        // Mostrar mensaje si no hay resultados
        const noResults = list.querySelector('.no-results') || (() => {
            const div = document.createElement('div');
            div.className = 'no-results p-8 text-center text-gray-500 text-xs hidden';
            div.textContent = 'No se encontraron resultados.';
            list.appendChild(div);
            return div;
        })();
        
        if (totalItems === 0 && query !== '') {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }

    searchInput.addEventListener('input', () => {
        currentPage = 1;
        updateList();
    });

    prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            updateList();
            list.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    nextBtn.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updateList();
            list.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // Inicializar
    updateList();
}

// Ejecutar al cargar
document.addEventListener('DOMContentLoaded', () => {
    // Admin
    initPagination('adminTicketList', 'adminTicketSearch', 'adminPrevBtn', 'adminNextBtn', 'adminCurrentPage', 'adminTotalPages');
    // Usuario
    initPagination('userTicketList', 'userTicketSearch', 'userPrevBtn', 'userNextBtn', 'userCurrentPage', 'userTotalPages');
});
</script>
<?php $extraScripts = ob_get_clean(); ?>
