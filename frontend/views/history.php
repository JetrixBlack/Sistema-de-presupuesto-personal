<?php
$transactions = $transactions ?? [];
$isAdmin      = $isAdmin      ?? false;
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-black text-white">Historial <?php echo $isAdmin ? 'General' : 'de Movimientos'; ?></h2>
        <p class="text-xs text-gray-500">
            <?php echo $isAdmin ? 'Todos los movimientos financieros del sistema' : 'Tus movimientos financieros (sin acciones administrativas)'; ?>
        </p>
    </div>
    
    <div class="flex items-center gap-2 relative group">
        <button class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg transition-all flex items-center gap-2">
            <i class="fas fa-file-pdf"></i> Exportar Reporte PDF
        </button>
        <!-- Menú de Opciones (Hover) -->
        <div class="absolute top-full right-0 mt-2 w-48 glass p-4 rounded-xl border border-white/10 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
            <form action="<?php echo URL_ROOT; ?>/pdf/export" method="GET" class="space-y-3">
                <div>
                    <label class="text-[9px] font-black text-gray-600 uppercase block mb-1 tracking-tighter">Año del Reporte</label>
                    <select name="year" class="w-full bg-black/40 text-gray-300 text-[10px] font-bold px-2 py-1.5 rounded border border-white/10 outline-none">
                        <?php for($y=2020; $y<=2035; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[9px] font-black text-gray-600 uppercase block mb-1 tracking-tighter">Mes del Reporte</label>
                    <select name="month" class="w-full bg-black/40 text-gray-300 text-[10px] font-bold px-2 py-1.5 rounded border border-white/10 outline-none" required>
                        <?php 
                        $mNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        $currentMonth = date('n');
                        foreach ($mNames as $idx => $m): 
                        ?>
                            <option value="<?php echo $idx + 1; ?>" <?php echo ($idx + 1) == $currentMonth ? 'selected' : ''; ?>><?php echo $m; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white text-[9px] font-black uppercase tracking-widest rounded-lg transition-all shadow-md">
                    Descargar PDF
                </button>
            </form>
        </div>
    </div>
</div>

<div class="glass rounded-2xl overflow-hidden flex flex-col" style="max-height:640px">
    <!-- Filtros -->
    <div class="p-4 border-b border-white/5 bg-black/10 flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[200px]">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
            <input type="text" id="filterSearch" placeholder="Buscar por nombre, categoría..." 
                   class="w-full pl-8 pr-3 py-2 rounded-lg text-xs bg-black/20 border border-white/10 text-white focus:border-purple-500 focus:outline-none transition-colors">
        </div>
        <select id="filterType" class="py-2 px-3 rounded-lg text-xs bg-[#0b1120] border border-white/10 text-white focus:outline-none focus:border-purple-500">
            <option class="bg-[#080b14] text-white" value="">Todos los Tipos</option>
            <option class="bg-[#080b14] text-white" value="income">Ingresos (+)</option>
            <option class="bg-[#080b14] text-white" value="expense">Egresos (-)</option>
        </select>
        <select id="filterMode" class="py-2 px-3 rounded-lg text-xs bg-[#0b1120] border border-white/10 text-white focus:outline-none focus:border-purple-500">
            <option class="bg-[#080b14] text-white" value="">Cualquier Modalidad</option>
            <option class="bg-[#080b14] text-white" value="mov">Movimiento Regular</option>
            <option class="bg-[#080b14] text-white" value="debt">Deuda</option>
        </select>
    </div>

    <!-- Tabla -->
    <div class="flex-1 overflow-auto">
        <table class="w-full text-left border-collapse" id="dataTable">
            <thead class="sticky top-0 bg-[#080b14]/90 backdrop-blur-md z-10 border-b border-white/10">
                <tr>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Fecha</th>
                    <?php if ($isAdmin): ?>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Usuario</th>
                    <?php endif; ?>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Detalles</th>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Categoría</th>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5" id="tableBody">
                <?php if (empty($transactions)): ?>
                <tr id="emptyRow"><td colspan="<?php echo $isAdmin ? 6 : 5; ?>" class="py-8 text-center text-xs text-gray-500">No hay movimientos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($transactions as $t): ?>
                <tr class="hover:bg-white/5 transition-colors group data-row"
                    data-type="<?php echo $t->type; ?>"
                    data-mode="<?php echo $t->mode; ?>">
                    <td class="py-3 px-4 text-xs text-gray-400 whitespace-nowrap">
                        <?php echo date('d M, Y', strtotime($t->transaction_date)); ?>
                    </td>
                    <?php if ($isAdmin): ?>
                    <td class="py-3 px-4">
                        <p class="text-xs font-bold text-purple-300 searchable"><?php echo htmlspecialchars($t->full_name ?? $t->username ?? 'N/A'); ?></p>
                        <p class="text-[9px] text-gray-600 uppercase"><?php echo $t->user_role ?? ''; ?></p>
                    </td>
                    <?php endif; ?>
                    <td class="py-3 px-4">
                        <p class="text-[10px] text-gray-500 truncate max-w-[200px] searchable"><?php echo htmlspecialchars($t->details ?: '—'); ?></p>
                        <?php if ($t->mode === 'debt' && $t->acreedor): ?>
                        <p class="text-[9px] text-amber-500/80 font-bold uppercase mt-0.5"><i class="fas fa-handshake mr-1"></i>Acreedor: <?php echo htmlspecialchars($t->acreedor); ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4">
                        <span class="px-2.5 py-1 rounded-md text-[9px] font-bold uppercase bg-white/10 text-gray-300 searchable">
                            <?php echo htmlspecialchars($t->category); ?>
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <p class="text-[10px] uppercase font-bold <?php echo $t->type === 'income' ? 'text-emerald-500' : 'text-red-500'; ?>">
                            <?php echo $t->type === 'income' ? 'Ingreso' : 'Egreso'; ?>
                        </p>
                        <p class="text-[9px] text-gray-500 uppercase"><?php echo $t->mode === 'debt' ? 'Deuda' : 'Regular'; ?></p>
                    </td>
                    <td class="py-3 px-4 text-right">
                        <p class="text-xs font-black <?php echo $t->type === 'income' ? 'text-emerald-400' : 'text-red-400'; ?>">
                            <?php echo $t->type === 'income' ? '+' : '-'; ?> Bs. <?php echo number_format($t->amount, 2, ',', '.'); ?>
                        </p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="p-3 border-t border-white/5 flex items-center justify-between bg-black/10">
        <p class="text-[10px] text-gray-500">Mostrando <span id="pageInfo">0-0 de 0</span></p>
        <div class="flex gap-1">
            <button onclick="changePage(-1)" id="btnPrev" class="px-3 py-1 rounded border border-white/10 text-xs text-gray-400 hover:bg-white/5 disabled:opacity-30 disabled:cursor-not-allowed">Anterior</button>
            <button onclick="changePage(1)"  id="btnNext" class="px-3 py-1 rounded border border-white/10 text-xs text-gray-400 hover:bg-white/5 disabled:opacity-30 disabled:cursor-not-allowed">Siguiente</button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const rowsPerPage = 8;
let currentPage = 1, filteredRows = [];
function initTable() {
    const allRows = Array.from(document.querySelectorAll('.data-row'));
    const sInput = document.getElementById('filterSearch');
    const sType  = document.getElementById('filterType');
    const sMode  = document.getElementById('filterMode');
    function renderTable() {
        allRows.forEach(r => r.style.display='none');
        const s=(currentPage-1)*rowsPerPage, e=s+rowsPerPage;
        filteredRows.slice(s,e).forEach(r=>r.style.display='');
        const t=filteredRows.length;
        document.getElementById('pageInfo').innerText=t===0?'0 de 0':`${s+1}-${Math.min(e,t)} de ${t}`;
        document.getElementById('btnPrev').disabled=currentPage===1;
        document.getElementById('btnNext').disabled=e>=t;
        const empty=document.getElementById('emptyRow');
        if(empty) empty.style.display=t===0?'':'none';
    }
    function doFilter() {
        const q=sInput.value.toLowerCase(), ty=sType.value, mo=sMode.value;
        filteredRows=allRows.filter(r=>{
            const text=Array.from(r.querySelectorAll('.searchable')).map(el=>el.innerText.toLowerCase()).join(' ');
            return text.includes(q)&&(ty?r.dataset.type===ty:true)&&(mo?r.dataset.mode===mo:true);
        });
        currentPage=1; renderTable();
    }
    sInput.addEventListener('input',doFilter);
    sType.addEventListener('change',doFilter);
    sMode.addEventListener('change',doFilter);
    filteredRows=[...allRows]; renderTable();
    
    <?php if (isset($_GET['audit'])): ?>
    sInput.value = "<?php echo htmlspecialchars($_GET['audit']); ?>";
    doFilter();
    <?php endif; ?>
    
    window.changePage=d=>{currentPage+=d;renderTable();};
}
document.addEventListener('DOMContentLoaded',initTable);
</script>
<?php $extraScripts = ob_get_clean(); ?>
