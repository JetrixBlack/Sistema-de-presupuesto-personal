<?php
$isAdmin = $isAdmin ?? false;
$globalStats = $globalStats ?? [];
$moduleTitle = $moduleTitle ?? 'Finanzas';
$moduleType = $moduleType ?? 'income';
$moduleMode = $moduleMode ?? 'mov';
$transactions = $transactions ?? [];
$activeTab = $activeTab ?? 'income';
$usersSelect = $usersSelect ?? [];

if (!function_exists('formatUsd')) {
    function formatUsd($bs, $rate)
    {
        if (!$rate || $rate <= 0)
            return 'USD 0.00';
        return 'USD ' . number_format($bs / $rate, 2, '.', ',');
    }
}

// Categorías según el módulo
$catIncome = $catIncome ?? ['Salario', 'Inversión', 'Bono', 'Otros'];
$catExpense = $catExpense ?? ['Alimentación', 'Transporte', 'Hogar', 'Servicios', 'Salud', 'Educación', 'Entretenimiento', 'Vestimenta', 'Calzado', 'Recargas', 'Otros'];
$categories = $moduleType === 'income' ? $catIncome : $catExpense;

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-black text-white">
            <?php echo $isAdmin ? 'Métricas Financieras Globales' : $moduleTitle; ?>
        </h2>
        <p class="text-xs text-gray-500">
            <?php echo $isAdmin ? 'Visión analítica del flujo financiero por usuario' : 'Gestiona tus movimientos financieros'; ?>
        </p>
    </div>
    <?php if ($isAdmin): ?>
        <div>
            <a href="<?php echo URL_ROOT; ?>/pdf/generalReport" target="_blank"
                class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold uppercase tracking-widest rounded-xl shadow-lg transition-all flex items-center gap-2 w-fit">
                <i class="fas fa-file-pdf"></i> PDF General
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="glass border border-red-500/20 bg-red-500/10 text-red-400 text-xs px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($isAdmin): ?>
    <!-- ═══════════ VISTA ADMINISTRADOR (GRÁFICOS) ═══════════ -->



    <!-- Grid de Usuarios -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($globalStats as $s):
            $ing = (float) $s->ingresos;
            $egr = (float) $s->egresos; // ya viene como valor positivo desde SQL
            $bal = $ing - $egr;
            ?>
            <div class="glass p-6 rounded-2xl border border-white/10 hover:border-purple-500/30 transition-all group">
                <div class="flex items-center gap-4 mb-6">
                    <div
                        class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-black text-lg shadow-lg uppercase">
                        <?php echo substr($s->username, 0, 1); ?>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-white"><?php echo htmlspecialchars($s->full_name); ?></h4>
                        <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">
                            @<?php echo htmlspecialchars($s->username); ?></p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-[9px] font-black text-gray-600 uppercase tracking-widest mb-1">Balance Neto</p>
                            <p class="text-lg font-black <?php echo $bal >= 0 ? 'text-emerald-400' : 'text-red-400'; ?>">
                                Bs. <?php echo number_format($bal, 2, ',', '.'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Mini Barras de Progreso -->
                    <div class="space-y-2 pt-2">
                        <div class="relative h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                            <div class="absolute top-0 left-0 h-full bg-emerald-500 transition-all duration-1000"
                                style="width: <?php echo $ing > 0 ? min(100, ($ing / max(1, $ing + $egr)) * 100) : 0; ?>%">
                            </div>
                            <div class="absolute top-0 right-0 h-full bg-red-500 transition-all duration-1000"
                                style="width: <?php echo $egr > 0 ? min(100, ($egr / max(1, $ing + $egr)) * 100) : 0; ?>%">
                            </div>
                        </div>
                        <div class="flex justify-between text-[8px] font-black uppercase tracking-tighter">
                            <span class="text-emerald-500">Ingresos: Bs. <?php echo number_format($ing, 0); ?></span>
                            <span class="text-red-500">Egresos: Bs. <?php echo number_format($egr, 0); ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-white/5 flex justify-between items-center">
                    <a href="<?php echo URL_ROOT; ?>/pdf/userReport/<?php echo $s->id; ?>" target="_blank"
                        class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-white text-[10px] font-bold uppercase tracking-widest rounded-lg transition-all flex items-center gap-2">
                        <i class="fas fa-file-pdf text-red-400"></i> Reporte PDF
                    </a>
                    <span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest">Resumen Financiero</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>



<?php else: ?>
    <!-- ═══════════ VISTA USUARIO REGULAR (FORMULARIO Y TABLA) ═══════════ -->

    <!-- TABS -->
    <div class="flex items-center gap-1 mb-6 p-1 glass rounded-2xl w-fit overflow-x-auto max-w-full no-scrollbar">
        <a href="<?php echo URL_ROOT; ?>/finance/index/income"
            class="flex-shrink-0 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all <?php echo $activeTab === 'income' ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/20' : 'text-gray-500 hover:text-white'; ?>">
            <i class="fas fa-arrow-trend-up mr-1.5"></i>Ingresos
        </a>
        <a href="<?php echo URL_ROOT; ?>/finance/index/expense"
            class="flex-shrink-0 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all <?php echo $activeTab === 'expense' ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/20' : 'text-gray-500 hover:text-white'; ?>">
            <i class="fas fa-arrow-trend-down mr-1.5"></i>Egresos
        </a>
    </div>

    <!-- FORMULARIO REGISTRO -->
    <details class="glass p-6 rounded-2xl mb-6 group bg-white/5 border border-white/10" id="financeForm" data-persist>
        <summary
            class="cursor-pointer list-none flex items-center justify-between text-sm font-bold text-white outline-none">
            <div class="flex items-center gap-2">
                <i class="fas fa-pen-to-square text-purple-400"></i>
                Registrar <?php echo $moduleTitle; ?>
            </div>
            <i class="fas fa-chevron-down text-gray-500 group-open:rotate-180 transition-transform"></i>
        </summary>
        <div class="pt-6 mt-4 border-t border-white/5">
            <form action="<?php echo URL_ROOT; ?>/finance/store" method="POST"
                class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="redirect" value="<?php echo $activeTab; ?>">
                <input type="hidden" name="type" value="<?php echo $moduleType; ?>">
                <input type="hidden" name="mode" value="<?php echo $moduleMode; ?>">



                <div>
                    <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">
                        Monto (Bs.)
                        <span class="text-gray-600">— Min: 0,00 &nbsp;|&nbsp; Max: 10.000.000,00</span>
                    </label>
                    <div class="relative">
                        <input type="text" id="formatted_amount" required
                            class="w-full px-4 py-3 rounded-xl text-sm font-bold text-white bg-black/40 border border-white/10 placeholder:text-gray-600 focus:border-purple-500 transition-all outline-none"
                            placeholder="0,00">
                        <input type="hidden" name="amount" id="real_amount">
                    </div>
                </div>
                <div>
                    <label
                        class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Categoría</label>
                    <select name="category"
                        class="w-full px-4 py-3 rounded-xl text-sm bg-black/40 border border-white/10 text-white focus:border-purple-500 outline-none transition-all"
                        required>
                        <?php foreach ($categories as $cat): ?>
                            <option class="bg-[#080b14] text-white" value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Detalles (Máx
                        300)</label>
                    <textarea name="details" id="main_details" maxlength="300" rows="2" placeholder="Descripción breve..."
                        class="w-full px-4 py-3 rounded-xl text-sm bg-black/40 border border-white/10 text-white focus:border-purple-500 outline-none resize-none transition-all"></textarea>
                </div>

                <div class="md:col-span-2 flex justify-end">
                    <button type="submit"
                        class="px-6 py-3 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold uppercase tracking-widest rounded-xl shadow-lg active:scale-95 transition-all">
                        <i class="fas fa-save mr-2"></i>Guardar Registro
                    </button>
                </div>
            </form>
        </div>
    </details>

    <!-- TABLA -->
    <div class="glass rounded-2xl overflow-hidden flex flex-col" style="max-height:550px">
        <div class="p-4 border-b border-white/5 flex items-center justify-between bg-black/10">
            <h3 class="text-sm font-bold text-white"><i class="fas fa-table text-purple-400 mr-2"></i>Historial de
                <?php echo $moduleTitle; ?>
            </h3>
            <div class="relative w-44">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                <input type="text" id="tableSearch" placeholder="Buscar..."
                    class="w-full pl-8 pr-3 py-1.5 rounded-lg text-xs bg-black/20 border border-white/10 focus:border-purple-500 outline-none transition-colors">
            </div>
        </div>

        <div class="flex-1 overflow-auto custom-scrollbar">
            <table class="w-full text-left border-collapse" id="dataTable">
                <thead class="sticky top-0 bg-[#080b14] z-10 border-b border-white/10">
                    <tr>
                        <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Hora</th>
                        <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Detalles</th>
                        <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider">Categoría</th>
                        <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider text-right">Monto
                        </th>
                        <th class="py-3 px-4 text-[10px] font-black text-gray-500 uppercase tracking-wider text-center">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5" id="tableBody">
                    <?php if (empty($transactions)): ?>
                        <tr id="emptyRow">
                            <td colspan="7" class="py-10 text-center text-xs text-gray-600">No hay registros financieros
                                registrados.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr class="hover:bg-white/5 transition-colors group data-row">
                            <td class="py-3 px-4 text-xs text-gray-400 whitespace-nowrap">
                                <?php echo date('d/m/Y', strtotime($t->transaction_date)); ?>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500 whitespace-nowrap">
                                <?php echo date('H:i:s', strtotime($t->transaction_date)); ?>
                            </td>

                            <td class="py-3 px-4">
                                <p class="text-[10px] text-gray-500 truncate max-w-[180px] searchable">
                                    <?php echo htmlspecialchars($t->details ?: '—'); ?>
                                </p>
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    class="px-2.5 py-1 rounded-md text-[9px] font-bold uppercase bg-white/10 text-gray-300 searchable"><?php echo htmlspecialchars($t->category); ?></span>
                            </td>

                            <td class="py-3 px-4 text-right">
                                <?php
                                $sign = $moduleType === 'income' ? '+' : '-';
                                $color = $moduleType === 'income' ? 'text-emerald-400' : 'text-red-400';
                                ?>
                                <p class="text-xs font-black <?php echo $color; ?>">
                                    <?php echo $sign; ?>Bs. <?php echo number_format(abs($t->amount), 2, ',', '.'); ?>
                                </p>
                                <p class="text-[9px] text-gray-600"><?php echo formatUsd($t->amount, $bcvRate); ?></p>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex justify-center gap-2 transition-opacity">
                                    <button onclick='openEditModal(<?php echo json_encode($t); ?>)'
                                        class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-500 hover:bg-blue-500 hover:text-white transition-colors"
                                        title="Editar">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>

                                    <button onclick="openDeleteModal(<?php echo $t->id; ?>)"
                                        class="w-7 h-7 rounded-lg bg-red-500/20 text-red-500 hover:bg-red-500 hover:text-white transition-colors">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-3 border-t border-white/5 flex items-center justify-between bg-black/10">
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Página <span
                    id="currentPageDisplay">1</span></p>
            <div class="flex gap-1">
                <button onclick="changePage(-1)" id="btnPrev"
                    class="px-4 py-1.5 rounded-lg bg-white/5 border border-white/10 text-[10px] font-black text-gray-500 hover:text-white uppercase transition-all disabled:opacity-20">Anterior</button>
                <button onclick="changePage(1)" id="btnNext"
                    class="px-4 py-1.5 rounded-lg bg-white/5 border border-white/10 text-[10px] font-black text-gray-500 hover:text-white uppercase transition-all disabled:opacity-20">Siguiente</button>
            </div>
        </div>
    </div>

    <!-- MODALES (EDITAR / ELIMINAR) - IGUAL QUE ANTES -->
    <div id="editModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm hidden">
        <div class="glass w-full max-w-lg p-8 rounded-3xl animate-[zoomIn_0.2s_ease-out]">
            <h3 class="text-lg font-black text-white mb-6">Editar Registro</h3>
            <form action="<?php echo URL_ROOT; ?>/finance/update" method="POST"
                class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="type" value="<?php echo $moduleType; ?>">

                <div>
                    <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Monto
                        (Bs.)</label>
                    <div class="relative">
                        <input type="text" id="edit_formatted_amount" required
                            class="w-full px-4 py-3 rounded-xl text-sm font-bold <?php echo $moduleType === 'income' ? 'text-emerald-400' : 'text-red-400'; ?> bg-black/20 border border-white/10 text-white outline-none focus:border-purple-500">
                        <input type="hidden" name="amount" id="edit_real_amount">
                    </div>
                </div>
                <div>
                    <label
                        class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Categoría</label>
                    <select name="category" id="edit_category"
                        class="w-full px-4 py-3 rounded-xl text-sm bg-black/40 border border-white/10 text-white outline-none focus:border-purple-500 transition-all">
                        <?php foreach ($categories as $cat): ?>
                            <option class="bg-[#080b14] text-white" value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1 block">Detalles</label>
                    <textarea name="details" id="edit_details" maxlength="300" rows="2"
                        class="w-full px-4 py-3 rounded-xl text-sm bg-black/40 border border-white/10 text-white outline-none focus:border-purple-500 resize-none"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-4">
                    <button type="button" onclick="closeModal('editModal')"
                        class="px-6 py-2.5 text-xs font-black uppercase text-gray-500 hover:text-white transition-all">Cerrar</button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg active:scale-95">Guardar
                        Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm hidden">
        <div class="glass w-full max-w-sm p-8 rounded-3xl text-center">
            <div class="w-16 h-16 bg-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trash-can text-2xl"></i>
            </div>
            <h3 class="text-lg font-black text-white mb-2">¿Confirmar eliminación?</h3>
            <p class="text-xs text-gray-500 mb-8 leading-relaxed">Esta acción borrará permanentemente el registro financiero
                del sistema.</p>
            <form action="<?php echo URL_ROOT; ?>/finance/delete" method="POST" class="flex flex-col gap-2">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="delete_id">
                <button type="submit"
                    class="w-full py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all">Eliminar
                    Registro</button>
                <button type="button" onclick="closeModal('deleteModal')"
                    class="w-full py-3 text-gray-500 hover:text-white text-xs font-black uppercase tracking-widest">Cerrar</button>
            </form>
        </div>
    </div>

    <?php ob_start(); ?>
    <script>
        const rowsPerPage = 10; let currentPage = 1, filteredRows = [];
        function initTable() {
            const allRows = Array.from(document.querySelectorAll('.data-row'));
            const si = document.getElementById('tableSearch');
            function renderTable() {
                allRows.forEach(r => r.style.display = 'none');
                const s = (currentPage - 1) * rowsPerPage, e = s + rowsPerPage;
                filteredRows.slice(s, e).forEach(r => r.style.display = '');
                const t = filteredRows.length;
                document.getElementById('btnPrev').disabled = currentPage === 1;
                document.getElementById('btnNext').disabled = e >= t;
                document.getElementById('currentPageDisplay').innerText = currentPage;
                const empty = document.getElementById('emptyRow');
                if (empty) empty.style.display = t === 0 ? '' : 'none';
            }
            si.addEventListener('input', () => {
                const q = si.value.toLowerCase();
                filteredRows = allRows.filter(r => Array.from(r.querySelectorAll('.searchable')).some(el => el.innerText.toLowerCase().includes(q)));
                currentPage = 1; renderTable();
            });
            filteredRows = [...allRows]; renderTable();
            window.changePage = d => { currentPage += d; renderTable(); };
        }

        // ── Máscara de Moneda en Tiempo Real ──────────────────
        const setupCurrencyMask = (displayId, hiddenId) => {
            const display = document.getElementById(displayId);
            const hidden = document.getElementById(hiddenId);
            if (!display || !hidden) return;

            const isNegativeMode = <?php echo $moduleType !== 'income' ? 'true' : 'false'; ?>;

            display.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value === '') {
                    hidden.value = '';
                    e.target.value = '';
                    return;
                }

                let number = parseFloat(value) / 100;
                // Limitar al máximo de 10,000,000
                if (number > 10000000) number = 10000000;
                hidden.value = isNegativeMode ? -number : number;

                let formatted = new Intl.NumberFormat('de-DE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(number);

                e.target.value = (isNegativeMode ? '-' : '') + formatted;
            });
        };

        setupCurrencyMask('formatted_amount', 'real_amount');
        setupCurrencyMask('edit_formatted_amount', 'edit_real_amount');

        // Validación de formularios
        document.addEventListener('DOMContentLoaded', () => {
            const finForm = document.querySelector('#financeForm form');
            if (finForm) {
                finForm.addEventListener('submit', function (e) {
                    const realAmount = Math.abs(parseFloat(document.getElementById('real_amount').value || 0));
                    if (realAmount > 10000000) {
                        e.preventDefault();
                        alert('El monto máximo permitido es de Bs. 10.000.000,00.');
                    }
                });
            }

            const editForm = document.querySelector('#editModal form');
            if (editForm) {
                editForm.addEventListener('submit', function (e) {
                    const editRealAmount = Math.abs(parseFloat(document.getElementById('edit_real_amount').value || 0));
                    if (editRealAmount > 10000000) {
                        e.preventDefault();
                        alert('El monto máximo permitido es de Bs. 10.000.000,00.');
                    }
                });
            }
        });

        function openEditModal(t) {
            document.getElementById('edit_id').value = t.id;
            document.getElementById('edit_category').value = t.category;
            document.getElementById('edit_details').value = t.details;

            // Formatear monto inicial en el modal
            const absAmt = Math.abs(parseFloat(t.amount));
            const formatted = new Intl.NumberFormat('de-DE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(absAmt);

            const isNegativeMode = <?php echo $moduleType !== 'income' ? 'true' : 'false'; ?>;
            document.getElementById('edit_formatted_amount').value = (isNegativeMode ? '-' : '') + formatted;
            document.getElementById('edit_real_amount').value = t.amount;

            document.getElementById('editModal').classList.remove('hidden');
        }
        function openDeleteModal(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
        document.addEventListener('DOMContentLoaded', initTable);

    </script>
    <?php $extraScripts = ob_get_clean(); ?>

<?php endif; ?>