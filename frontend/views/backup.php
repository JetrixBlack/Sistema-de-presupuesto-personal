<?php
// La vista backup.php es exclusiva para el Administrador. 
// Aquí se muestra el estado del servidor y el historial de la base de datos.
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-black text-white">Centro de Respaldo</h2>
        <p class="text-xs text-gray-500">Estado del servidor y copias de seguridad de la base de datos</p>
    </div>
</div>

<?php if (!empty($success)): ?>
<!-- Aquí mostramos el mensaje verde si la copia de seguridad se creó con éxito o se eliminó un archivo. -->
<div class="glass border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 text-xs px-4 py-3 rounded-lg mb-4 animate-[fadeIn_0.3s]">
    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if ($needsBackup): ?>
<!-- Alerta roja importante: Han pasado 6 meses sin un respaldo, ¡se le advierte al administrador! -->
<div class="glass border border-red-500/20 bg-red-500/10 text-red-400 px-4 py-4 rounded-xl mb-6 flex items-start gap-3">
    <i class="fas fa-triangle-exclamation text-xl mt-0.5 animate-pulse"></i>
    <div>
        <h4 class="font-bold text-sm">Respaldo Automático Sugerido</h4>
        <p class="text-xs opacity-80 mt-1">
            Han pasado más de 6 meses desde el último respaldo (o no existe ninguno). Por la seguridad de la información, te recomendamos generar una copia de seguridad manualmente ahora mismo haciendo clic en el botón inferior.
        </p>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Tarjeta IP del Servidor -->
    <div class="glass p-6 rounded-2xl border border-white/10 flex items-center gap-4 relative overflow-hidden">

        <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center">
            <i class="fas fa-network-wired text-purple-400 text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">IP del Servidor</p>
            <p class="text-lg font-black text-white"><?php echo htmlspecialchars($ip); ?></p>
        </div>
    </div>

    <!-- Tarjeta Puerto del Servidor -->
    <div class="glass p-6 rounded-2xl border border-white/10 flex items-center gap-4 relative overflow-hidden">

        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center">
            <i class="fas fa-plug text-indigo-400 text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Puerto (Web)</p>
            <p class="text-lg font-black text-white"><?php echo htmlspecialchars($port); ?></p>
        </div>
    </div>

    <!-- Tarjeta Estado Base de Datos -->
    <div class="glass p-6 rounded-2xl border border-white/10 flex items-center gap-4 relative overflow-hidden">

        <div class="w-12 h-12 rounded-xl <?php echo $dbStatus === 'En Línea' ? 'bg-emerald-500/10' : 'bg-red-500/10'; ?> flex items-center justify-center">
            <i class="fas fa-database <?php echo $dbStatus === 'En Línea' ? 'text-emerald-400' : 'text-red-400'; ?> text-xl"></i>
        </div>
        <div>
            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-1">Estado MySQL</p>
            <p class="text-lg font-black <?php echo $dbStatus === 'En Línea' ? 'text-emerald-400' : 'text-red-400'; ?>">
                <?php echo $dbStatus; ?>
            </p>
        </div>
    </div>
</div>

<div class="glass rounded-2xl border border-white/10 overflow-hidden">
    <!-- Cabecera de la tabla con el botón de generar respaldo -->
    <div class="p-6 border-b border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-white">Historial de Respaldos</h3>
            <p class="text-[10px] text-gray-500">Últimos 10 respaldos almacenados en el servidor.</p>
        </div>
        
        <form method="POST" action="<?php echo URL_ROOT; ?>/backup/generate" onsubmit="return confirm('¿Iniciar generación de respaldo? Esto puede tomar unos segundos.');">
            <?php echo csrf_field(); ?>
            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg active:scale-95 flex items-center gap-2">
                <i class="fas fa-cloud-download-alt"></i> Generar Respaldo
            </button>
        </form>
    </div>

    <!-- Tabla que itera sobre el arreglo de respaldos -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/5 border-b border-white/5">
                    <th class="py-3 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Archivo (.sql)</th>
                    <th class="py-3 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha de Creación</th>
                    <th class="py-3 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Tamaño</th>
                    <th class="py-3 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($backups)): ?>
                <tr>
                    <td colspan="4" class="py-8 text-center text-gray-500 text-xs">
                        <i class="fas fa-folder-open text-2xl mb-2 opacity-50 block"></i>
                        No hay respaldos generados todavía.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($backups as $index => $b): ?>
                    <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                        <td class="py-3 px-6 text-xs text-white flex items-center gap-2">
                            <i class="fas fa-file-code text-purple-400"></i>
                            <?php echo htmlspecialchars($b['name']); ?>
                            <?php if ($index === 0): ?>
                                <!-- Etiqueta verde para el archivo más reciente -->
                                <span class="ml-2 px-1.5 py-0.5 rounded text-[8px] bg-emerald-500/20 text-emerald-400 uppercase font-bold">Nuevo</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-xs text-gray-400">
                            <?php echo date('d/m/Y h:i A', strtotime($b['date'])); ?>
                        </td>
                        <td class="py-3 px-6 text-xs text-gray-400 font-mono">
                            <?php echo $b['size']; ?>
                        </td>
                        <td class="py-3 px-6 text-right flex justify-end gap-2">
                            <!-- Botón para descargar el archivo a la PC local -->
                            <a href="<?php echo URL_ROOT; ?>/backup/download/<?php echo urlencode($b['name']); ?>" class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white flex items-center justify-center transition-all" title="Descargar">
                                <i class="fas fa-download text-xs"></i>
                            </a>
                            
                            <!-- Botón para eliminar el archivo del servidor -->
                            <form method="POST" action="<?php echo URL_ROOT; ?>/backup/delete" class="inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar este respaldo del servidor?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($b['name']); ?>">
                                <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all" title="Eliminar">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
