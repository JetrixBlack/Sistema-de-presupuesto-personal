// CONFIGURACIÓN DE CATEGORÍAS
const categories = {
    mov: {
        income: ["Sueldo", "Bono", "Venta", "Remesa", "Inversión", "Transferencia", "Préstamo (me pagan)", "Otros"],
        expense: ["Mercado", "Alquiler", "Servicios", "CANTV", "Condominio", "Salud", "Transporte", "Préstamo (debo)", "Gustos", "Varios"]
    }
};

let myChart = null;
let annualChart = null;
let pieChart = null;
let deleteId = null;
let isDarkMode = localStorage.getItem('theme_dig') === 'dark';
let currentPage = 1;
const itemsPerPage = 8;





function switchModule(module) {
    const modules = ['dashboard', 'control', 'history', 'settings'];
    modules.forEach(m => {
        const mod = document.getElementById(`module-${m}`);
        const nav = document.getElementById(`nav-${m}`);
        if(mod) mod.classList.add('hidden');
        if(nav) nav.classList.remove('active-nav');
    });
    
    const targetMod = document.getElementById(`module-${module}`);
    const targetNav = document.getElementById(`nav-${module}`);
    if(targetMod) targetMod.classList.remove('hidden');
    if(targetNav) targetNav.classList.add('active-nav');

    if (module === 'dashboard') updateChart('monthly');
    if (module === 'history') {
        currentPage = 1;
        renderHistory();
        renderSummaryTable();
        updatePieChart();
    }
}

function renderSummaryTable() {
    const container = document.querySelector('#module-history .space-y-3');
    if (!container || !window.chartData?.summary) return;

    const { positive, negative } = window.chartData.summary;
    
    container.innerHTML = `
        <div class="flex justify-between items-center p-4 bg-emerald-50 dark:bg-emerald-500/10 rounded-2xl border border-emerald-100 dark:border-emerald-500/20">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Total Ingresos</span>
            </div>
            <span class="text-sm font-black text-emerald-600">Bs. ${positive.toLocaleString('es-VE', {minimumFractionDigits: 2})}</span>
        </div>
        <div class="flex justify-between items-center p-4 bg-rose-50 dark:bg-rose-500/10 rounded-2xl border border-rose-100 dark:border-rose-500/20">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Total Egresos</span>
            </div>
            <span class="text-sm font-black text-rose-600">Bs. ${negative.toLocaleString('es-VE', {minimumFractionDigits: 2})}</span>
        </div>
        <div class="flex justify-between items-center p-4 bg-brand/5 dark:bg-brand/10 rounded-2xl border border-brand/10 dark:border-brand/20">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand text-white flex items-center justify-center text-xs">
                    <i class="fas fa-equals"></i>
                </div>
                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Saldo Neto</span>
            </div>
            <span class="text-sm font-black text-brand">Bs. ${(positive - negative).toLocaleString('es-VE', {minimumFractionDigits: 2})}</span>
        </div>
    `;
}

function renderHistory() {
    const container = document.getElementById('history-list');
    const paginationContainer = document.getElementById('pagination-controls');
    if (!container || !window.chartData?.transactions) return;

    const transactions = window.chartData.transactions;
    const totalPages = Math.ceil(transactions.length / itemsPerPage);
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const paginatedItems = transactions.slice(start, end);

    if (transactions.length === 0) {
        container.innerHTML = `<div class="p-20 text-center opacity-30"><i class="fas fa-folder-open text-5xl mb-4"></i><p class="font-black uppercase tracking-widest text-[10px]">Sin movimientos registrados</p></div>`;
        paginationContainer.innerHTML = '';
        return;
    }

    container.innerHTML = paginatedItems.map(t => {
        const isInc = t.type === 'income';
        const date = new Date(t.transaction_date);
        const day = date.getDate();
        const month = date.toLocaleString('es-VE', { month: 'short' }).replace('.', '');
        const statusClass = t.mode === 'debt' && t.status === 'pendiente' ? 'bg-amber-50/20 dark:bg-amber-500/5' : '';
        
        return `
            <div class="p-6 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-all ${statusClass}">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex flex-col items-center justify-center ${isInc ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10'}">
                        <span class="text-[8px] font-black uppercase">${month}</span>
                        <span class="text-sm font-black leading-none">${day}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-bold text-gray-800 dark:text-white">${t.category}</span>
                            <span class="bg-brand/10 dark:bg-brand/30 text-brand px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter">${t.familiar}</span>
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">
                            ${t.mode === 'debt' ? `<b class="text-amber-600">${t.acreedor}:</b> ` : ''}
                            ${t.details}
                        </p>
                    </div>
                </div>
                <div class="text-right flex flex-col items-end gap-2">
                    <p class="text-sm font-black ${isInc ? 'text-emerald-500' : 'text-rose-500'}">${isInc ? '+' : '-'} Bs. ${parseFloat(t.amount).toLocaleString('es-VE', {minimumFractionDigits: 2})}</p>
                    <div class="flex gap-4">
                        <button onclick="askDelete(${t.id})" class="text-gray-300 hover:text-rose-500 transition-colors"><i class="fas fa-trash-alt text-[10px]"></i></button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Pagination
    let paginationHtml = '';
    if (totalPages > 1) {
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `
                <button onclick="changePage(${i})" class="w-8 h-8 rounded-lg text-[10px] font-black transition-all ${i === currentPage ? 'bg-brand text-white shadow-lg shadow-brand/20' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'}">
                    ${i}
                </button>
            `;
        }
    }
    paginationContainer.innerHTML = paginationHtml;
}

function changePage(page) {
    currentPage = page;
    renderHistory();
    document.getElementById('history-container').scrollIntoView({ behavior: 'smooth' });
}

// --- FORMULARIO ---
function loadCategories() {
    const typeInput = document.getElementById('type');
    const select = document.getElementById('category');
    if(!typeInput || !select) return;

    const type = typeInput.value;
    const list = categories.mov[type] || [];
    select.innerHTML = list.map(c => `<option value="${c}">${c}</option>`).join('');
}

// --- GRÁFICO ANUAL (Simulado o con data PHP) ---
function updateChart(period = 'monthly') {
    const canvas = document.getElementById('yearlyChart');
    if(!canvas) return;
    const ctx = canvas.getContext('2d');
    
    // Update active tab buttons
    document.querySelectorAll('.chart-tab').forEach(btn => {
        if(btn.dataset.period === period) {
            btn.classList.add('active-tab');
            btn.classList.remove('text-gray-400');
        } else {
            btn.classList.remove('active-tab');
            btn.classList.add('text-gray-400');
        }
    });

    // Show/Hide selectors
    const monthSelector = document.getElementById('month-selector');
    const yearSelector = document.getElementById('year-selector');
    
    if(period === 'monthly') {
        if(monthSelector) monthSelector.classList.remove('hidden');
        if(yearSelector) yearSelector.classList.remove('hidden');
    } else {
        if(monthSelector) monthSelector.classList.add('hidden');
        if(yearSelector) yearSelector.classList.add('hidden');
    }

    const isDark = document.documentElement.classList.contains('dark');
    const chartConfig = window.chartData || {};
    const transactions = chartConfig.transactions || [];
    
    let labels = [];
    let incomeData = [];
    let expenseData = [];
    let periodTransactions = [];
    let barWidth = period === 'daily' ? 0.4 : 0.8;

    const selectedMonth = parseInt(monthSelector?.value || new Date().getMonth() + 1);
    const selectedYear = parseInt(yearSelector?.value || new Date().getFullYear());

    if (period === 'monthly') {
        const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
        labels = Array.from({length: daysInMonth}, (_, i) => i + 1);
        incomeData = Array(daysInMonth).fill(0);
        expenseData = Array(daysInMonth).fill(0);

        periodTransactions = transactions.filter(t => {
            const d = new Date(t.transaction_date);
            return d.getMonth() + 1 === selectedMonth && d.getFullYear() === selectedYear;
        });

        periodTransactions.forEach(t => {
            const d = new Date(t.transaction_date).getDate();
            const amount = parseFloat(t.amount);
            if(t.type === 'income') incomeData[d-1] += amount;
            else expenseData[d-1] += amount;
        });
    } else if (period === 'weekly') {
        labels = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
        incomeData = Array(7).fill(0);
        expenseData = Array(7).fill(0);
        
        // Get current week transactions
        const now = new Date();
        const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
        startOfWeek.setHours(0,0,0,0);

        periodTransactions = transactions.filter(t => {
            const d = new Date(t.transaction_date);
            return d >= startOfWeek;
        });

        periodTransactions.forEach(t => {
            const d = new Date(t.transaction_date).getDay();
            const amount = parseFloat(t.amount);
            if(t.type === 'income') incomeData[d] += amount;
            else expenseData[d] += amount;
        });

        const maxExpense = Math.max(...expenseData);
        if (maxExpense > 0) {
            const maxIndex = expenseData.indexOf(maxExpense);
            labels = labels.map((l, i) => i === maxIndex ? `${l} 🔥` : l);
        }
    } else if (period === 'daily') {
        labels = ["Mañana", "Tarde", "Noche"];
        incomeData = [0, 0, 0];
        expenseData = [0, 0, 0];

        const todayStr = new Date().toISOString().split('T')[0];
        periodTransactions = transactions.filter(t => t.transaction_date.startsWith(todayStr));

        periodTransactions.forEach(t => {
            const h = new Date(t.transaction_date).getHours();
            const amount = parseFloat(t.amount);
            let idx = (h >= 6 && h < 12) ? 0 : (h >= 12 && h < 18) ? 1 : 2;
            if(t.type === 'income') incomeData[idx] += amount;
            else expenseData[idx] += amount;
        });
    }

    // Trend lines calculation (Linear Regression)
    function getTrendLine(data) {
        const n = data.length;
        let sumX = 0, sumY = 0, sumXY = 0, sumXX = 0;
        for (let i = 0; i < n; i++) {
            sumX += i;
            sumY += data[i];
            sumXY += i * data[i];
            sumXX += i * i;
        }
        const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
        const intercept = (sumY - slope * sumX) / n;
        return data.map((_, i) => slope * i + intercept);
    }

    const incomeTrend = getTrendLine(incomeData);
    const expenseTrend = getTrendLine(expenseData);

    if (myChart) myChart.destroy();

    myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ingresos',
                    data: incomeData,
                    backgroundColor: '#10b981',
                    borderRadius: 5,
                    barPercentage: barWidth,
                    categoryPercentage: barWidth,
                },
                {
                    label: 'Egresos',
                    data: expenseData,
                    backgroundColor: '#f43f5e',
                    borderRadius: 5,
                    barPercentage: barWidth,
                    categoryPercentage: barWidth,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { color: isDark ? '#64748b' : '#94a3b8', font: { size: 10, weight: 'bold' } }
                }
            },
            scales: {
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: isDark ? '#1e293b' : '#f1f5f9' },
                    ticks: { color: isDark ? '#64748b' : '#94a3b8', font: { size: 9, weight: 'bold' } }
                },
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { color: isDark ? '#64748b' : '#94a3b8', font: { size: 9, weight: 'bold' } }
                }
            }
        }
    });

    renderDetailedLegend(periodTransactions);
}

function renderDetailedLegend(transactions) {
    const container = document.getElementById('chart-detailed-legend');
    if(!container) return;

    if(!transactions || transactions.length === 0) {
        container.innerHTML = `<div class="col-span-full py-10 text-center opacity-30"><i class="fas fa-info-circle mb-2"></i><p class="text-[9px] font-black uppercase">Sin datos para este periodo</p></div>`;
        return;
    }

    const countIncome = transactions.filter(t => t.type === 'income').length;
    const countExpense = transactions.filter(t => t.type === 'expense').length;

    container.innerHTML = `
        <div class="flex items-center justify-between p-4 bg-emerald-50/50 dark:bg-emerald-500/5 rounded-2xl border border-emerald-100 dark:border-emerald-500/20">
            <div class="flex flex-col">
                <span class="text-[8px] font-black text-emerald-600 uppercase tracking-widest">Cantidad Ingresos</span>
                <span class="text-lg font-black text-emerald-700 dark:text-emerald-400">${countIncome} <span class="text-xs font-bold opacity-50">Movimientos</span></span>
            </div>
            <i class="fas fa-plus-circle text-emerald-500 opacity-50"></i>
        </div>
        <div class="flex items-center justify-between p-4 bg-rose-50/50 dark:bg-rose-500/5 rounded-2xl border border-rose-100 dark:border-rose-500/20">
            <div class="flex flex-col">
                <span class="text-[8px] font-black text-rose-600 uppercase tracking-widest">Cantidad Egresos</span>
                <span class="text-lg font-black text-rose-700 dark:text-rose-400">${countExpense} <span class="text-xs font-bold opacity-50">Movimientos</span></span>
            </div>
            <i class="fas fa-minus-circle text-rose-500 opacity-50"></i>
        </div>
    `;
}

function updatePieChart() {
    const canvas = document.getElementById('categoryPieChart');
    if(!canvas || !window.chartData?.categories) return;
    const ctx = canvas.getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');

    if (pieChart) pieChart.destroy();

    const data = Object.values(window.chartData.categories);
    const labels = Object.keys(window.chartData.categories);

    pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#a78bfa', '#f472b6', '#fb923c', '#4ade80', '#60a5fa', 
                    '#facc15', '#2dd4bf', '#818cf8', '#fb7185', '#94a3b8'
                ],
                borderWidth: 2,
                borderColor: isDark ? '#1e293b' : '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: isDark ? '#64748b' : '#94a3b8', font: { size: 8, weight: 'bold' } }
                }
            }
        }
    });
}

// --- UTILITIES ---
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = document.getElementById('eye-' + id);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function updateCounter(id) {
    const input = document.getElementById(id);
    const counterId = id === 'new-pass' ? 'char-count-new-pass' : 'char-count-sec-ans';
    const counter = document.getElementById(counterId);
    if (input && counter) {
        counter.innerText = `${input.value.length}/${input.maxLength}`;
    }
}

// Detective de Montos (Entero/Decimal)
document.addEventListener('input', (e) => {
    if (e.target.id === 'amount-input') {
        const val = e.target.value;
        const status = document.getElementById('amount-status');
        if (!status) return;

        if (!val) {
            status.classList.add('hidden');
            return;
        }

        status.classList.remove('hidden');
        if (val.includes('.') && val.split('.')[1].length > 0) {
            status.innerText = 'Decimal';
            status.className = 'text-[7px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 animate-in';
        } else {
            status.innerText = 'Entero';
            status.className = 'text-[7px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 animate-in';
        }
    }
});

// --- MODALES ---

function askDelete(id) {
    const modal = document.getElementById('delete-modal');
    const content = document.getElementById('modal-content');
    const input = document.getElementById('delete-id-input');
    
    if(!modal || !input) return;
    
    input.value = id;
    modal.classList.replace('hidden', 'flex');
    setTimeout(() => {
        if(content) {
            content.classList.replace('scale-90', 'scale-100');
            content.classList.replace('opacity-0', 'opacity-100');
        }
    }, 10);
}

function closeDeleteModal() {
    const modal = document.getElementById('delete-modal');
    const content = document.getElementById('modal-content');
    if(!modal) return;
    if(content) {
        content.classList.replace('scale-100', 'scale-90');
        content.classList.replace('opacity-100', 'opacity-0');
    }
    setTimeout(() => modal.classList.replace('flex', 'hidden'), 300);
}

// --- INICIALIZACIÓN ---
document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.add('dark');
    const dateEl = document.getElementById('current-date');
    if(dateEl) dateEl.innerText = new Date().toLocaleDateString('es-VE', { weekday: 'long', day: 'numeric', month: 'long' });
    loadCategories();
    updateChart('monthly');
    renderHistory();
    renderSummaryTable();
    updatePieChart();
});
