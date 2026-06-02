<?php
$isAdmin       = $isAdmin       ?? false;
$userMetrics   = $userMetrics   ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0];
$ticketMetrics = $ticketMetrics ?? (object)['pendientes' => 0, 'en_proceso' => 0, 'resueltos' => 0, 'total' => 0];
$recentActivity= $recentActivity?? [];
$recentUsers   = $recentUsers   ?? [];
$onlineUsers   = $onlineUsers   ?? [];
$stats         = $stats         ?? ['income' => 0, 'expense' => 0, 'balance' => 0, 'deudas_pendientes' => 0, 'total' => 0];
$transactions  = $transactions  ?? [];
$chart         = $chart         ?? ['annual' => []];
$adminChart    = $adminChart    ?? ['annual' => []];
$adminPie      = $adminPie      ?? ['income' => 0, 'expense' => 0];
$trendChart    = $trendChart    ?? ['income' => array_fill(0,12,0), 'expense' => array_fill(0,12,0)];
$bcvRate       = $bcvRate       ?? 0;
$euroRate      = $euroRate      ?? 0;

function formatUsd($bs, $rate) {
    if (!$rate || $rate <= 0) return '$ —';
    return '$ ' . number_format($bs / $rate, 2, ',', '.');
}
function formatEur($bs, $rate) {
    if (!$rate || $rate <= 0) return '€ —';
    return '€ ' . number_format($bs / $rate, 2, ',', '.');
}

// Determinar IDs de usuarios online para comparar
$onlineIds = array_map(fn($u) => $u->id, $onlineUsers);
?>

<?php if ($isAdmin): ?>
<!-- ═══════════ DASHBOARD ADMIN ═══════════ -->

<!-- Header Bar -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-white">Panel de Control</h2>
        <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-0.5">Vista en tiempo real del sistema</p>
    </div>
    <p class="text-[9px] text-gray-600 uppercase tracking-wider hidden sm:block"><?php echo date('d M Y, H:i'); ?></p>
</div>

<!-- KPI Cards Row -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $onlineCount = count($onlineUsers);
    $adminCards = [
        ['Total Usuarios',  $userMetrics['total']     ?? 0, 'fa-users',        'text-purple-400',  'bg-purple-500/10'],
        ['En Línea',        $onlineCount,              'fa-signal',       'text-emerald-400', 'bg-emerald-500/10'],
        ['Inactivos',       $userMetrics['inactivos'] ?? 0, 'fa-user-slash',   'text-red-400',     'bg-red-500/10'],
        ['Tickets Pend.',   $ticketMetrics->pendientes ?? 0,'fa-headset',      'text-amber-400',   'bg-yellow-500/10'],
    ];
    foreach ($adminCards as [$lbl, $val, $ico, $col, $bg]): ?>
    <div class="glass p-5 rounded-2xl flex items-center gap-4 group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-16 h-16 rounded-full opacity-5 bg-white blur-2xl group-hover:opacity-10 transition-opacity"></div>
        <div class="w-10 h-10 rounded-xl <?php echo $bg; ?> flex items-center justify-center flex-shrink-0">
            <i class="fas <?php echo $ico; ?> <?php echo $col; ?> text-sm"></i>
        </div>
        <div>
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest"><?php echo $lbl; ?></p>
            <p class="text-2xl font-black <?php echo $col; ?>"><?php echo number_format($val); ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Gráficos principales -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Flujo Global -->
    <div class="lg:col-span-2 glass p-6 rounded-2xl border border-white/5">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fas fa-chart-bar text-purple-400"></i> Flujo Global <?php echo $selectedYear ?? date('Y'); ?>
            </h3>
            <form method="GET" class="flex gap-2">
                <select name="year" onchange="this.form.submit()" class="bg-black/40 text-gray-300 text-[10px] font-bold px-3 py-1.5 rounded-lg border border-white/10 outline-none cursor-pointer">
                    <?php foreach ($availableYears as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $selectedYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="min-h-[260px] relative"><canvas id="adminAnnualChart"></canvas></div>
    </div>

    <!-- Proporción Pie -->
    <div class="glass p-6 rounded-2xl border border-white/5 flex flex-col">
        <h3 class="text-sm font-bold text-white mb-5 flex items-center gap-2">
            <i class="fas fa-chart-pie text-purple-400"></i> Proporción del Sistema
        </h3>
        <div class="flex-1 min-h-[200px] relative"><canvas id="adminPieChart"></canvas></div>
        <div class="mt-4 grid grid-cols-2 gap-2 text-center text-[10px] font-bold uppercase tracking-wider">
            <div class="bg-emerald-500/10 rounded-lg p-2 text-emerald-400">
                <p>Ingresos</p>
                <p class="text-base text-white">Bs. <?php echo number_format($adminPie['income'] ?? 0, 0, ',', '.'); ?></p>
            </div>
            <div class="bg-red-500/10 rounded-lg p-2 text-red-400">
                <p>Egresos</p>
                <p class="text-base text-white">Bs. <?php echo number_format($adminPie['expense'] ?? 0, 0, ',', '.'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico de Tendencia Proyectada -->
<div class="glass p-6 rounded-2xl border border-white/5 mb-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fas fa-chart-area text-indigo-400"></i> Proyección de Tendencias — <?php echo $selectedYear ?? date('Y'); ?>
            </h3>
            <p class="text-[10px] text-gray-500 mt-1">Dominio mensual: Ingresos vs Egresos del sistema completo</p>
        </div>
    </div>
    <div class="min-h-[200px] relative"><canvas id="adminTrendChart"></canvas></div>
</div>

<!-- Panel Inferior: Actividad + Usuarios Conectados -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Actividad Reciente -->
    <div class="glass p-6 rounded-2xl border border-white/5">
        <h3 class="text-sm font-bold text-white mb-5 flex items-center gap-2">
            <i class="fas fa-bolt text-amber-400"></i> Actividad Reciente
        </h3>
        <div class="space-y-3 max-h-[360px] overflow-y-auto pr-2 custom-scrollbar">
            <?php if (empty($recentActivity)): ?>
                <p class="text-xs text-gray-500 text-center py-4">No hay actividad reciente.</p>
            <?php endif; ?>
            <?php foreach ($recentActivity ?? [] as $act): ?>
                <div class="flex gap-3 items-start p-3 rounded-xl hover:bg-white/5 transition-colors border border-transparent hover:border-white/5">
                    <div class="w-8 h-8 rounded-lg bg-purple-600/20 flex items-center justify-center flex-shrink-0">
                        <?php
                        $icon = 'fa-info-circle';
                        if ($act->entity === 'transaction') $icon = 'fa-money-bill-wave';
                        elseif ($act->entity === 'user') $icon = 'fa-user';
                        elseif ($act->entity === 'settings') $icon = 'fa-cog';
                        ?>
                        <i class="fas <?php echo $icon; ?> text-purple-400 text-[10px]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-white"><span class="font-bold text-purple-400"><?php echo htmlspecialchars($act->full_name ?? 'Sistema'); ?></span> <?php echo htmlspecialchars($act->action); ?></p>
                        <p class="text-[9px] text-gray-500 mt-0.5"><?php echo date('d M, h:i A', strtotime($act->created_at)); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Usuarios Conectados — tiempo real -->
    <div class="glass p-6 rounded-2xl border border-white/5">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fas fa-wifi text-emerald-400"></i> Estado de Usuarios
            </h3>
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Online
                </span>
                <span class="flex items-center gap-1.5 text-[9px] font-bold text-red-400">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Offline
                </span>
            </div>
        </div>
        <div class="space-y-2 max-h-[360px] overflow-y-auto custom-scrollbar">
            <?php
            $pdo = \Core\Database::getInstance()->getDbh();
            $allUsersStmt = $pdo->query("SELECT id, username, full_name, role, last_activity, is_active FROM users ORDER BY (last_activity IS NULL), last_activity DESC");
            $allSystemUsers = $allUsersStmt ? $allUsersStmt->fetchAll(\PDO::FETCH_OBJ) : [];
            if (empty($allSystemUsers)): ?>
                <p class="text-xs text-gray-500 text-center py-4">Sin usuarios registrados.</p>
            <?php endif; ?>
            <?php foreach ($allSystemUsers as $u):
                $isOnline  = !empty($u->last_activity) && (time() - strtotime($u->last_activity)) < 300;
                $initials  = strtoupper(substr($u->full_name ?: $u->username, 0, 1));
                $lastSeen  = !empty($u->last_activity) ? date('d/m H:i', strtotime($u->last_activity)) : 'Nunca';
            ?>
            <div class="flex items-center gap-3 p-3 rounded-xl border <?php echo $isOnline ? 'border-emerald-500/20 bg-emerald-500/5' : 'border-red-500/10 bg-red-500/5'; ?> transition-all">
                <!-- Avatar -->
                <div class="relative flex-shrink-0">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-black text-sm uppercase <?php echo $isOnline ? 'bg-emerald-600/40' : 'bg-gray-700/60'; ?>">
                        <?php echo $initials; ?>
                    </div>
                    <!-- Indicador de estado -->
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-[#0b1121] <?php echo $isOnline ? 'bg-emerald-400 animate-pulse' : 'bg-red-500'; ?>"
                          title="<?php echo $isOnline ? 'Conectado' : 'Desconectado'; ?>"></span>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($u->full_name ?: $u->username); ?></p>
                    <p class="text-[9px] text-gray-500 uppercase tracking-wider"><?php echo $u->role; ?></p>
                </div>
                <!-- Estado pill -->
                <?php if ($isOnline): ?>
                    <span class="flex-shrink-0 flex items-center gap-1 text-[9px] font-black text-emerald-400 bg-emerald-500/15 border border-emerald-500/30 px-2.5 py-1 rounded-full uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> En l&iacute;nea
                    </span>
                <?php else: ?>
                    <span class="flex-shrink-0 flex items-center gap-1 text-[9px] font-black text-red-400 bg-red-500/10 border border-red-500/20 px-2.5 py-1 rounded-full uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> <?php echo $lastSeen; ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="<?php echo URL_ROOT; ?>/users" class="mt-4 block text-center text-[10px] font-bold text-purple-400 hover:text-purple-300 uppercase tracking-wider transition-colors">
            Administrar usuarios &rarr;
        </a>
    </div>
</div>

<?php else: ?>
<!-- ═══════════ DASHBOARD USUARIO ═══════════ -->
<?php
$balance  = $stats['balance']  ?? 0;
$ingresos = $stats['ingresos'] ?? 0;
$egresos  = $stats['egresos']  ?? 0;
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-white">Mi Dashboard</h2>
        <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-0.5">Resumen financiero personal</p>
    </div>
    <div class="text-right hidden sm:block">
        <p class="text-[9px] text-gray-500 uppercase tracking-wider"><?php echo date('l, d M Y'); ?></p>
    </div>
</div>

<!-- Tarjetas financieras -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <?php
    $userCards = [
        ['Balance Actual', $balance,  'fa-wallet',           $balance >= 0 ? 'text-emerald-400' : 'text-red-400', 'bg-purple-500/10'],
        ['Total Ingresos', $ingresos, 'fa-arrow-trend-up',   'text-emerald-400', 'bg-emerald-500/10'],
        ['Total Egresos',  $egresos,  'fa-arrow-trend-down', 'text-red-400',     'bg-red-500/10'],
    ];
    foreach ($userCards as [$lbl, $val, $ico, $col, $bg]): ?>
    <div class="glass p-5 rounded-2xl flex items-center gap-4 group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-16 h-16 rounded-full opacity-5 bg-white blur-2xl group-hover:opacity-10 transition-opacity"></div>
        <div class="w-10 h-10 rounded-xl <?php echo $bg; ?> flex items-center justify-center flex-shrink-0">
            <i class="fas <?php echo $ico; ?> <?php echo $col; ?> text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest"><?php echo $lbl; ?></p>
            <p class="text-xl font-black <?php echo $col; ?>">Bs. <?php echo number_format($val, 2, ',', '.'); ?></p>
            <div class="flex gap-3 mt-0.5">
                <p class="text-[10px] font-bold text-gray-600"><?php echo formatUsd($val, $bcvRate); ?></p>
                <p class="text-[10px] font-bold text-gray-600"><?php echo formatEur($val, $euroRate); ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Gráficos Flujo + Tendencia: misma altura en escritorio -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Flujo Financiero -->
    <div class="glass p-6 rounded-2xl flex flex-col">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fas fa-chart-bar text-purple-400"></i> Flujo Financiero <?php echo $selectedYear ?? date('Y'); ?>
            </h3>
            <form method="GET" class="flex gap-2">
                <select name="year" onchange="this.form.submit()" class="bg-black/40 text-gray-300 text-[10px] font-bold px-3 py-1.5 rounded-lg border border-white/10 outline-none cursor-pointer">
                    <?php foreach ($availableYears as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $selectedYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="flex-1 min-h-[280px] relative"><canvas id="annualChart"></canvas></div>
    </div>

    <!-- Tendencia -->
    <div class="glass p-6 rounded-2xl flex flex-col">
        <h3 class="text-sm font-bold text-white mb-1 flex items-center gap-2">
            <i class="fas fa-chart-area text-indigo-400"></i> Tendencia <?php echo $selectedYear ?? date('Y'); ?>
        </h3>
        <p class="text-[9px] text-gray-500 mb-5 uppercase tracking-wider">¿Qué domina este año?</p>
        <div class="flex-1 min-h-[280px] relative"><canvas id="userTrendChart"></canvas></div>
    </div>
</div>

<?php endif; ?>

<?php
$months   = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$incData  = $chart['annual']['income']  ?? array_fill(0, 12, 0);
$expData  = $chart['annual']['expense'] ?? array_fill(0, 12, 0);

$extraScripts = "
<script>
Chart.defaults.color = '#64748b';
Chart.defaults.font.family = 'Inter';
Chart.defaults.font.size   = 11;

const months = " . json_encode($months) . ";

" . (!$isAdmin ? "
// ── Gráfico de Flujo del Usuario ──────────────────────────
const ctxA = document.getElementById('annualChart');
if (ctxA) {
    let mInc = " . json_encode($incData) . ";
    let mExp = " . json_encode($expData) . ";
    let netBalance = mInc.map((v, i) => v - mExp[i]);
    let cumulative = []; let t = 0;
    for(let i=0; i<12; i++) { t += netBalance[i]; cumulative.push(t); }

    new Chart(ctxA, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                { type:'line', label:'Balance Acum.', data:cumulative, borderColor:'#c084fc', backgroundColor:'rgba(192,132,252,0.1)', borderWidth:2, pointRadius:3, tension:0.4, fill:true, order:1 },
                { type:'bar', label:'Ingresos (Bs.)', data:mInc, backgroundColor:'rgba(16,185,129,0.7)', borderRadius:4, order:2 },
                { type:'bar', label:'Egresos (Bs.)',  data:mExp, backgroundColor:'rgba(239,68,68,0.7)', borderRadius:4, order:3 }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{ legend:{ position:'top', labels:{ usePointStyle:true, boxWidth:6, padding:14 } },
                tooltip:{ callbacks:{ label: c => { let v=c.parsed.y; return c.dataset.label+': Bs. '+Math.abs(v).toLocaleString('es-VE',{minimumFractionDigits:2}); } } }
            },
            scales:{ x:{ grid:{ display:false } }, y:{ grid:{ color:'rgba(255,255,255,0.05)' }, ticks:{ callback: v => 'Bs. '+Math.abs(v).toLocaleString() } } }
        }
    });
}

// ── Tendencia del usuario (área) ──────────────────────────
const ctxUT = document.getElementById('userTrendChart');
if (ctxUT) {
    let mInc = " . json_encode($incData) . ";
    let mExp = " . json_encode($expData) . ";
    new Chart(ctxUT, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                { label:'Ingresos', data:mInc, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,0.15)', fill:1, tension:0.4, borderWidth:2, pointRadius:2 },
                { label:'Egresos',  data:mExp, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,0.15)', fill:true, tension:0.4, borderWidth:2, pointRadius:2 }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, boxWidth:6 } } },
            scales:{ x:{ grid:{ display:false } }, y:{ grid:{ color:'rgba(255,255,255,0.05)' }, ticks:{ callback: v => 'Bs. '+v.toLocaleString() } } }
        }
    });
}
" : "
// ── Gráfico Flujo Admin ───────────────────────────────────
const ctxAdminA = document.getElementById('adminAnnualChart');
if (ctxAdminA) {
    let mInc = " . json_encode($adminChart['annual']['income'] ?? array_fill(0,12,0)) . ";
    let mExp = " . json_encode($adminChart['annual']['expense'] ?? array_fill(0,12,0)) . ";
    let cumulative = []; let t = 0;
    mInc.forEach((v,i) => { t += v - mExp[i]; cumulative.push(t); });
    new Chart(ctxAdminA, {
        type:'bar',
        data:{ labels:months, datasets:[
            { type:'line', label:'Balance Acum.', data:cumulative, borderColor:'#c084fc', backgroundColor:'rgba(192,132,252,0.1)', borderWidth:2, pointRadius:3, tension:0.4, fill:true, order:1 },
            { type:'bar', label:'Ingresos (Bs.)', data:mInc, backgroundColor:'rgba(16,185,129,0.7)', borderRadius:4, order:2 },
            { type:'bar', label:'Egresos (Bs.)', data:mExp, backgroundColor:'rgba(239,68,68,0.7)', borderRadius:4, order:3 }
        ]},
        options:{
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{ legend:{ position:'top', labels:{ usePointStyle:true, boxWidth:6 } },
                tooltip:{ callbacks:{ label: c => { let v=c.parsed.y; return c.dataset.label+': Bs. '+Math.abs(v).toLocaleString('es-VE',{minimumFractionDigits:2}); } } }
            },
            scales:{ x:{ grid:{ display:false } }, y:{ grid:{ color:'rgba(255,255,255,0.05)' }, ticks:{ callback: v => 'Bs. '+Math.abs(v).toLocaleString() } } }
        }
    });
}

// ── Gráfico Pie Admin ────────────────────────────────────
const ctxAdminP = document.getElementById('adminPieChart');
if (ctxAdminP) {
    new Chart(ctxAdminP, {
        type:'doughnut',
        data:{ labels:['Ingresos','Egresos'], datasets:[{ data:" . json_encode([$adminPie['income'] ?? 0, $adminPie['expense'] ?? 0]) . ", backgroundColor:['#10b981','#ef4444'], borderWidth:0, hoverOffset:8 }] },
        options:{ responsive:true, maintainAspectRatio:false, cutout:'72%', plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, boxWidth:6 } } } }
    });
}

// ── Gráfico Tendencia Admin ──────────────────────────────
const ctxAdminT = document.getElementById('adminTrendChart');
if (ctxAdminT) {
    let tInc = " . json_encode($trendChart['income'] ?? array_fill(0,12,0)) . ";
    let tExp = " . json_encode($trendChart['expense'] ?? array_fill(0,12,0)) . ";
    new Chart(ctxAdminT, {
        type:'line',
        data:{ labels:months, datasets:[
            { label:'Ingresos', data:tInc, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,0.12)', fill:1, tension:0.4, borderWidth:2.5, pointRadius:4, pointBackgroundColor:'#10b981' },
            { label:'Egresos',  data:tExp, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,0.12)', fill:true, tension:0.4, borderWidth:2.5, pointRadius:4, pointBackgroundColor:'#ef4444' }
        ]},
        options:{
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{ legend:{ position:'top', labels:{ usePointStyle:true, boxWidth:6 } },
                tooltip:{ callbacks:{ label: c => c.dataset.label+': Bs. '+c.parsed.y.toLocaleString('es-VE',{minimumFractionDigits:2}) } }
            },
            scales:{ x:{ grid:{ display:false } }, y:{ grid:{ color:'rgba(255,255,255,0.04)' }, ticks:{ callback: v => 'Bs. '+v.toLocaleString() } } }
        }
    });
}
") . "
</script>
";
?>