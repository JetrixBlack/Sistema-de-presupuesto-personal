<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Panel'; ?> - <?php echo APP_NAME; ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: '#0f172a',
                        darker: '#0b1120',
                    },
                    borderRadius: {
                        'xl': '0.5rem', /* Reducido para que no sea tan redondo */
                        '2xl': '0.75rem' /* Reducido */
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/assets/css/app.css">
</head>

<body class="antialiased overflow-hidden selection:bg-purple-500 selection:text-white">


    <!-- Background Decoration -->
    <div
        class="fixed top-[-20%] left-[-10%] w-[60%] h-[60%] bg-purple-600/10 rounded-full blur-[150px] pointer-events-none">
    </div>
    <div
        class="fixed bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-purple-600/10 rounded-full blur-[150px] pointer-events-none">
    </div>

    <!-- Mobile Overlay -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/80 z-[90] hidden md:hidden transition-opacity duration-300"></div>

    <div class="flex h-screen w-full relative z-10">

        <!-- SIDEBAR -->
        <aside id="sidebar"
            class="fixed inset-y-0 left-0 md:relative w-64 flex-shrink-0 border-r border-white/5 flex flex-col sidebar-transition z-[100] transform -translate-x-full md:translate-x-0 transition-transform duration-300">
            <!-- Logo -->
            <div
                class="py-6 flex flex-col items-center justify-center border-b border-white/5 center-collapsed relative">
                <div
                    class="w-12 h-12 flex items-center justify-center flex-shrink-0 p-1 mb-2">
                    <img src="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png" alt="Logo"
                        class="w-full h-full object-contain">
                </div>
                <span class="text-xl font-black text-white tracking-wide hide-collapsed">Sistema de Presupuesto Personal</span>
            </div>

            <!-- Menu -->
            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1" id="tour-menu">
                <?php $menu = $activeMenu ?? ''; ?>
                <p class="px-3 text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 mt-4 hide-collapsed">
                    Principal</p>

                <a href="<?php echo URL_ROOT; ?>/dashboard"
                    class="flex items-center px-3 py-2.5 rounded-xl transition-all group <?php echo $menu == 'dashboard' ? 'bg-purple-600/10 text-purple-500' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> center-collapsed">
                    <i class="fas fa-chart-pie w-5 text-center"></i>
                    <span class="ml-3 text-sm font-semibold hide-collapsed">Dashboard</span>
                </a>

                <p class="px-3 text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 mt-6 hide-collapsed">
                    Gestión</p>

                <?php if (($_SESSION['role'] ?? '') !== 'admin'): ?>
                    <!-- Finanzas -->
                    <a href="<?php echo URL_ROOT; ?>/finance"
                        class="flex items-center px-3 py-2.5 rounded-xl transition-all group <?php echo $menu == 'finance' ? 'bg-purple-600/10 text-purple-500' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> center-collapsed"
                        id="tour-finance">
                        <i class="fas fa-money-bill-transfer w-5 text-center"></i>
                        <span class="ml-3 text-sm font-semibold hide-collapsed">Finanzas</span>
                    </a>

                <?php endif; ?>

                <a href="<?php echo URL_ROOT; ?>/history"
                    class="flex items-center px-3 py-2.5 rounded-xl transition-all group <?php echo $menu == 'history' ? 'bg-purple-600/10 text-purple-500' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> center-collapsed">
                    <i class="fas fa-clock-rotate-left w-5 text-center"></i>
                    <span class="ml-3 text-sm font-semibold hide-collapsed">Historial</span>
                </a>

                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="<?php echo URL_ROOT; ?>/users"
                        class="flex items-center px-3 py-2.5 rounded-xl transition-all group <?php echo $menu == 'users' ? 'bg-purple-600/10 text-purple-500' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> center-collapsed">
                        <i class="fas fa-users-gear w-5 text-center"></i>
                        <span class="ml-3 text-sm font-semibold hide-collapsed">Usuarios</span>
                    </a>
                <?php endif; ?>

                <p class="px-3 text-[9px] font-black text-gray-500 uppercase tracking-widest mb-2 mt-6 hide-collapsed">
                    Cuenta</p>

                <a href="<?php echo URL_ROOT; ?>/support"
                    class="flex items-center px-3 py-2.5 rounded-xl transition-all group <?php echo $menu == 'support' ? 'bg-purple-600/10 text-purple-500' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> center-collapsed">
                    <div class="relative">
                        <i class="fas fa-headset w-5 text-center"></i>
                        <?php if (($unreadMessages ?? 0) > 0): ?>
                            <span
                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full border border-dark"></span>
                        <?php endif; ?>
                    </div>
                    <span class="ml-3 text-sm font-semibold hide-collapsed">Soporte</span>
                </a>

                <a href="<?php echo URL_ROOT; ?>/settings"
                    class="flex items-center px-3 py-2.5 rounded-xl transition-all group <?php echo $menu == 'settings' ? 'bg-purple-600/10 text-purple-500' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> center-collapsed">
                    <i class="fas fa-gear w-5 text-center"></i>
                    <span class="ml-3 text-sm font-semibold hide-collapsed">Configuración</span>
                </a>

                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <!-- Enlace exclusivo de Administradores para Respaldos -->
                    <a href="<?php echo URL_ROOT; ?>/backup"
                        class="flex items-center px-3 py-2.5 rounded-xl transition-all group <?php echo $menu == 'backup' ? 'bg-purple-600/10 text-purple-500' : 'text-gray-400 hover:bg-white/5 hover:text-white'; ?> center-collapsed">
                        <i class="fas fa-database w-5 text-center"></i>
                        <span class="ml-3 text-sm font-semibold hide-collapsed">Respaldo</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Profile Footer -->
            <div class="p-4 border-t border-white/5 center-collapsed">
                <a href="<?php echo URL_ROOT; ?>/auth/logout"
                    class="flex items-center w-full px-4 py-2.5 rounded-xl bg-red-600 text-white hover:bg-red-700 transition-all center-collapsed group shadow-lg shadow-red-900/20">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                    <span class="ml-3 text-sm font-black hide-collapsed uppercase tracking-widest">Cerrar Sesión</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Navbar -->
            <header class="h-16 flex items-center justify-between px-6 border-b border-white/5 relative z-40">
                <div class="flex items-center gap-4">
                    <!-- Hamburguesa solo en desktop -->
                    <button onclick="toggleSidebar()" class="hidden md:block text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>

                <div class="flex items-center gap-3 ml-2">
                    <!-- Tasa BCV Compacta (USD y EUR) -->
                    <div class="hidden md:flex gap-2" id="bcvWidget">
                        <div
                            class="flex flex-col items-end px-3 py-1.5 rounded-xl bg-emerald-500/5 border border-emerald-500/10">
                            <span
                                class="text-[8px] font-black text-gray-500 uppercase tracking-widest leading-none mb-1">Dólar
                                BCV</span>
                            <span class="text-xs font-black text-emerald-400 leading-none" id="bcvRateLabel">Bs.
                                0,00</span>
                        </div>
                        <div
                            class="flex flex-col items-end px-3 py-1.5 rounded-xl bg-blue-500/5 border border-blue-500/10">
                            <span
                                class="text-[8px] font-black text-gray-500 uppercase tracking-widest leading-none mb-1">Euro
                                BCV</span>
                            <span class="text-xs font-black text-blue-400 leading-none" id="euroRateLabel">Bs.
                                0,00</span>
                        </div>
                    </div>

                    <!-- Notificaciones -->
                    <div class="relative" id="notificationDropdown">
                        <button onclick="document.getElementById('notifMenu').classList.toggle('hidden')" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-colors relative">
                            <i class="fas fa-bell text-sm"></i>
                            <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-purple-500 rounded-full animate-ping"></span>
                            <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                        </button>
                        
                        <div id="notifMenu" class="hidden fixed top-20 left-4 right-4 w-auto sm:absolute sm:top-auto sm:left-auto sm:right-0 sm:mt-2 sm:w-80 glass border border-white/10 rounded-2xl shadow-2xl z-50 overflow-hidden origin-top">
                            <div class="p-4 border-b border-white/5 flex justify-between items-center bg-black/20">
                                <h3 class="text-sm font-bold text-white"><i class="fas fa-history text-purple-400 mr-2"></i><?php echo ($_SESSION['role'] ?? '') === 'admin' ? 'Actividad Reciente' : 'Movimientos Recientes'; ?></h3>
                                <a href="<?php echo URL_ROOT; ?>/history" class="text-[9px] font-bold text-purple-400 hover:text-purple-300 uppercase tracking-wider">Ver todos &rarr;</a>
                            </div>
                            <div class="max-h-[300px] overflow-y-auto custom-scrollbar p-2">
                                <?php
                                $pdo = \Core\Database::getInstance()->getDbh();
                                if (($_SESSION['role'] ?? '') === 'admin') {
                                    $stmt = $pdo->query("SELECT al.*, u.full_name FROM activity_log al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 10");
                                    $acts = $stmt->fetchAll(\PDO::FETCH_OBJ);
                                    if(empty($acts)) echo '<p class="text-xs text-gray-500 text-center py-4">Sin actividad reciente.</p>';
                                    foreach ($acts as $a) {
                                        echo '
                                        <div class="flex items-start gap-3 p-3 hover:bg-white/5 rounded-xl border border-transparent transition-colors">
                                            <div class="w-8 h-8 rounded-lg bg-purple-600/20 flex items-center justify-center flex-shrink-0 text-purple-400 font-bold text-xs uppercase">
                                                '.substr($a->full_name ?? 'S', 0, 1).'
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-bold text-white">'.htmlspecialchars($a->full_name ?? 'Sistema').'</p>
                                                <p class="text-[10px] text-gray-400 line-clamp-2">'.htmlspecialchars($a->action).'</p>
                                                <p class="text-[9px] text-gray-600 mt-1">'.date('d M, H:i', strtotime($a->created_at)).'</p>
                                            </div>
                                        </div>';
                                    }
                                } else {
                                    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 10");
                                    $stmt->execute([\Core\Auth::id()]);
                                    $txs = $stmt->fetchAll(\PDO::FETCH_OBJ);
                                    if(empty($txs)) echo '<p class="text-xs text-gray-500 text-center py-4">Sin movimientos recientes.</p>';
                                    foreach ($txs as $t) {
                                        $isDebt = ($t->mode ?? '') === 'debt';
                                        $sign = ''; $color = '';
                                        if ($t->type === 'income' && !$isDebt) { $sign = '+'; $color = 'text-emerald-400'; }
                                        elseif ($t->type === 'expense' && !$isDebt) { $sign = '-'; $color = 'text-red-400'; }
                                        elseif ($isDebt) { $sign = '-'; $color = 'text-amber-400'; }
                                        
                                        echo '
                                        <div class="flex items-center justify-between p-3 hover:bg-white/5 rounded-xl border border-transparent transition-colors">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-bold text-white truncate">'.htmlspecialchars($t->category).'</p>
                                                <p class="text-[10px] text-gray-400 truncate">'.htmlspecialchars($t->details ?: '—').'</p>
                                                <p class="text-[9px] text-gray-600">'.date('d M', strtotime($t->transaction_date)).'</p>
                                            </div>
                                            <div class="text-right ml-2">
                                                <p class="text-xs font-black '.$color.'">'.$sign.'Bs. '.number_format(abs($t->amount), 2, ',', '.').'</p>
                                            </div>
                                        </div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="hidden xl:block text-right">
                        <span
                            class="block text-xs font-black text-white"><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Usuario'; ?></span>
                        <span
                            class="block text-[9px] font-bold text-purple-400 uppercase tracking-widest"><?php echo $_SESSION['role'] ?? 'user'; ?></span>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-lg border-2 border-white/10">
                        <?php echo strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)); ?>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto p-6" id="mainScrollArea">
                <div class="max-w-7xl mx-auto pb-20 md:pb-6 w-full">
                    <?php echo $content ?? ''; ?>
                </div>
            </div>

            <!-- Footer visible en móvil también -->
            <footer class="flex-shrink-0 py-3 px-6 border-t border-white/10 w-full flex flex-col sm:flex-row items-center justify-between gap-2 bg-black/50">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 opacity-30 grayscale">
                        <img src="<?php echo URL_ROOT; ?>/assets/img/logo_prncipal.png" alt="Sistema de Presupuesto Personal" class="w-full h-full object-contain">
                    </div>
                    <p class="text-[9px] font-bold text-gray-600">
                        &copy; <?php echo date('Y'); ?> Sistema de Presupuesto Personal
                    </p>
                </div>
                <div class="flex items-center gap-3 text-[9px] font-semibold text-gray-600">
                    <span class="flex items-center gap-1"><i class="fas fa-shield-alt text-emerald-700"></i> SSL Activo</span>
                    <span>v1.5.0</span>
                </div>
        </main>
    </div>


    <!-- Bottom Navigation Bar — solo móvil (plegable) -->
    <div id="bottomNavWrapper" class="md:hidden fixed bottom-0 left-0 right-0 z-[200] transition-transform duration-300 ease-in-out" style="transform:translateY(0);">

        <!-- Botón de colapso / expansión -->
        <button id="bottomNavToggle" onclick="toggleBottomNav()"
            class="absolute -top-7 left-1/2 -translate-x-1/2 w-12 h-7 rounded-t-2xl flex items-center justify-center border border-b-0 border-white/10 transition-colors"
            style="background:rgba(5,7,15,0.97);">
            <i id="bottomNavArrow" class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-300"></i>
        </button>

        <!-- Barra de navegación -->
        <nav id="bottomNavBar" class="flex items-center justify-around border-t border-white/10"
             style="background:rgba(5,7,15,0.97);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);padding-bottom:env(safe-area-inset-bottom,8px);">
            <?php
            $activeMenu = $activeMenu ?? '';
            $role = $_SESSION['role'] ?? 'user';
            $bottomNavItems = [['dashboard', URL_ROOT.'/dashboard', 'fa-chart-pie', 'Inicio']];
            if ($role !== 'admin') {
                $bottomNavItems[] = ['finance', URL_ROOT.'/finance', 'fa-money-bill-transfer', 'Finanzas'];
            }
            $bottomNavItems[] = ['history', URL_ROOT.'/history', 'fa-clock-rotate-left', 'Historial'];
            if ($role === 'admin') {
                $bottomNavItems[] = ['users', URL_ROOT.'/users', 'fa-users-gear', 'Usuarios'];
            }
            $bottomNavItems[] = ['settings', URL_ROOT.'/settings', 'fa-gear', 'Ajustes'];
            foreach ($bottomNavItems as [$key, $url, $icon, $label]):
                $isActive = $activeMenu === $key;
            ?>
            <a href="<?php echo $url; ?>"
               class="flex flex-col items-center gap-1 py-3 px-3 flex-1 transition-all <?php echo $isActive ? 'text-purple-400' : 'text-gray-600 hover:text-gray-400'; ?>">
                <i class="fas <?php echo $icon; ?> text-xl"></i>
                <span class="text-[8px] font-bold uppercase tracking-wider"><?php echo $label; ?></span>
                <?php if ($isActive): ?>
                    <span class="w-4 h-0.5 rounded-full bg-purple-400"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <!-- Salir -->
            <a href="<?php echo URL_ROOT; ?>/auth/logout"
               class="flex flex-col items-center gap-1 py-3 px-3 flex-1 text-red-600 hover:text-red-400 transition-all">
                <i class="fas fa-sign-out-alt text-xl"></i>
                <span class="text-[8px] font-bold uppercase tracking-wider">Salir</span>
            </a>
        </nav>
    </div>

    <script>
    (function() {
        const wrapper = document.getElementById('bottomNavWrapper');
        const arrow   = document.getElementById('bottomNavArrow');
        const bar     = document.getElementById('bottomNavBar');
        let collapsed = localStorage.getItem('bottomNavCollapsed') === 'true';

        function applyState() {
            if (collapsed) {
                const barH = bar ? bar.offsetHeight : 56;
                wrapper.style.transform = 'translateY(' + barH + 'px)';
                arrow.style.transform   = 'rotate(180deg)';
            } else {
                wrapper.style.transform = 'translateY(0)';
                arrow.style.transform   = 'rotate(0deg)';
            }
        }

        window.toggleBottomNav = function() {
            collapsed = !collapsed;
            localStorage.setItem('bottomNavCollapsed', collapsed);
            applyState();
        };

        // Aplicar estado guardado al cargar
        applyState();
    })();
    </script>

    <!-- Toast Notification Container (notificaciones flotantes) -->
    <div id="toast-container"></div>

    <!-- Onboarding Tour Elements -->
    <?php if (isset($showOnboarding) && $showOnboarding): ?>
        <div id="tourOverlay" class="tour-overlay hidden"></div>
        <div id="tourTooltip" class="tour-step hidden glass p-5 rounded-xl w-72 shadow-2xl transition-all duration-300">
            <h4 id="tourTitle" class="text-sm font-black text-white mb-2 flex items-center gap-2">
                <i class="fas fa-info-circle text-purple-400"></i> <span id="tourTitleText">Título</span>
            </h4>
            <p id="tourText" class="text-xs text-gray-400 mb-4 leading-relaxed">Texto</p>
            <div class="flex items-center justify-between mt-2">
                <span class="text-[10px] text-gray-500 font-bold"><span id="tourStepNum">1</span>/5</span>
                <div class="flex gap-2">
                    <button onclick="tourNext()" id="tourNextBtn"
                        class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 rounded-lg text-[10px] font-bold text-white uppercase tracking-wider transition-colors">Siguiente</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Base Scripts -->
    <script>
        // Persistir estado de elementos <details> con atributo data-persist
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('details[data-persist]').forEach(details => {
                const id = details.getAttribute('id');
                if (!id) return;

                // Restaurar estado
                const state = localStorage.getItem('details_state_' + id);
                if (state === 'open') {
                    details.setAttribute('open', '');
                } else if (state === 'closed') {
                    details.removeAttribute('open');
                }

                // Guardar estado al cambiar
                details.addEventListener('toggle', () => {
                    localStorage.setItem('details_state_' + id, details.hasAttribute('open') ? 'open' : 'closed');
                });
            });
        });

        // Toggle Sidebar
        const sidebar = document.getElementById('sidebar');
        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('collapsed'); // Asegurar que no esté colapsado en móvil
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('translate-x-0');
                const overlay = document.getElementById('mobile-overlay');
                if(overlay) overlay.classList.toggle('hidden');
            } else {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
            }
        }
        if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth > 768) {
            sidebar.classList.add('collapsed');
        }

        function toggleSubmenu(menuId, iconId) {
            const menu = document.getElementById(menuId);
            const icon = document.getElementById(iconId);
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        // Restore sidebar state
        if (localStorage.getItem('sidebarState') === 'closed') {
            document.getElementById('sidebar').classList.add('collapsed');
        }

        // Fetch BCV Rate
        async function fetchBcv() {
            const lblUsd = document.getElementById('bcvRateLabel');
            const lblEur = document.getElementById('euroRateLabel');
            if (!lblUsd && !lblEur) return;
            try {
                const res = await fetch('<?php echo URL_ROOT; ?>/dashboard/bcvRate');
                const data = await res.json();
                if (data.usd) {
                    if (lblUsd) lblUsd.innerHTML = `Bs. ${data.usd.toFixed(2).replace('.', ',')}`;
                } else if (lblUsd) {
                    lblUsd.innerHTML = '—';
                }
                if (data.eur) {
                    if (lblEur) lblEur.innerHTML = `Bs. ${data.eur.toFixed(2).replace('.', ',')}`;
                } else if (lblEur) {
                    lblEur.innerHTML = '—';
                }
            } catch (e) {
                if (lblUsd) lblUsd.innerHTML = 'Error';
                if (lblEur) lblEur.innerHTML = 'Error';
            }
        }
        fetchBcv();

        // Persistence for Collapsible Forms (Details)
        document.addEventListener('DOMContentLoaded', () => {
            const details = document.querySelectorAll('details');
            details.forEach((detail, index) => {
                const id = `details_state_${window.location.pathname}_${index}`;
                const state = localStorage.getItem(id);
                if (state === 'open') detail.open = true;
                if (state === 'closed') detail.open = false;

                detail.addEventListener('toggle', () => {
                    localStorage.setItem(id, detail.open ? 'open' : 'closed');
                });
            });
        });


        <?php if (isset($showOnboarding) && $showOnboarding): ?>
            // ONBOARDING LOGIC
            const steps = [
                { el: 'tour-menu', title: 'Navegación', text: 'Aquí encontrarás todos los módulos del sistema. Dashboard, Finanzas, Historial y Configuración.' },
                { el: 'tour-finance', title: 'Finanzas', text: 'Registra tus ingresos, egresos, deudas y préstamos desde aquí.' },
                { el: 'bcvWidget', title: 'Tasa BCV', text: 'La tasa oficial del Banco Central se actualiza automáticamente todos los días.' },
                { el: 'tour-search', title: 'Búsqueda Global', text: 'Encuentra cualquier movimiento o transacción rápidamente desde cualquier pantalla.' },
                { el: 'mainScrollArea', title: '¡Todo listo!', text: 'Ya puedes empezar a gestionar tu presupuesto. Si necesitas ayuda, usa el Chat de Soporte.' }
            ];
            let currentStep = 0;

            function positionTooltip(targetId) {
                const target = document.getElementById(targetId);
                const tooltip = document.getElementById('tourTooltip');
                if (!target) return;

                document.querySelectorAll('.tour-highlight').forEach(e => e.classList.remove('tour-highlight'));
                target.classList.add('tour-highlight');

                const rect = target.getBoundingClientRect();
                let top = rect.top + (rect.height / 2) - 100;
                let left = rect.right + 20;

                if (left + 300 > window.innerWidth) { left = rect.left - 300; }
                if (top < 0) top = 20;

                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';
                tooltip.classList.remove('hidden');
            }

            function tourNext() {
                if (currentStep >= steps.length) return endTour();
                const step = steps[currentStep];
                document.getElementById('tourTitleText').innerText = step.title;
                document.getElementById('tourText').innerText = step.text;
                document.getElementById('tourStepNum').innerText = currentStep + 1;

                if (currentStep === steps.length - 1) {
                    document.getElementById('tourNextBtn').innerText = 'Comenzar';
                    document.getElementById('tourNextBtn').classList.replace('bg-purple-600', 'bg-emerald-500');
                }
                positionTooltip(step.el);
                currentStep++;
            }

            function endTour() {
                document.getElementById('tourOverlay').classList.add('hidden');
                document.getElementById('tourTooltip').classList.add('hidden');
                document.querySelectorAll('.tour-highlight').forEach(e => e.classList.remove('tour-highlight'));
                fetch('<?php echo URL_ROOT; ?>/settings/completeOnboarding');
            }

            window.onload = () => {
                if (document.getElementById('sidebar').classList.contains('collapsed')) { toggleSidebar(); }
                document.getElementById('tourOverlay').classList.remove('hidden');
                tourNext();
            };
        <?php endif; ?>
    </script>
    <?php echo $extraScripts ?? ''; ?>

    <script>
    // ══════════════════════════════════════════════════════════
    //  Toast Notification Engine — Notificaciones flotantes
    //  Uso desde cualquier página:
    //    showToast('Mensaje aquí', 'success') // success | error | warning | info
    // ══════════════════════════════════════════════════════════
    window.showToast = function(message, type = 'info', duration = 4000) {
        const icons = {
            success: 'fa-circle-check',
            error:   'fa-circle-xmark',
            warning: 'fa-triangle-exclamation',
            info:    'fa-circle-info'
        };
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fas ${icons[type] || icons.info} text-base"></i><span>${message}</span>`;

        // Clic para cerrar manualmente
        toast.addEventListener('click', () => dismissToast(toast));
        container.appendChild(toast);

        // Auto-dismiss después de 'duration' ms
        const timer = setTimeout(() => dismissToast(toast), duration);
        toast._timer = timer;
    };

    function dismissToast(toast) {
        clearTimeout(toast._timer);
        toast.classList.add('toast-out');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }

    // Auto-mostrar mensajes flash que vengan desde PHP session
    <?php
    $flashTypes = ['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'];
    foreach ($flashTypes as $sessionKey => $toastType):
        if (!empty($_SESSION['flash_' . $sessionKey])):
            $msg = addslashes(htmlspecialchars($_SESSION['flash_' . $sessionKey], ENT_QUOTES));
            echo "document.addEventListener('DOMContentLoaded', function() { showToast('{$msg}', '{$toastType}'); });";
            unset($_SESSION['flash_' . $sessionKey]);
        endif;
    endforeach;
    ?>
    </script>
</body>

</html>