<?php
use Core\Controller;
use Core\Auth;
use Core\Database;

class BackupController extends Controller {
    private $backupDir;

    public function __construct() {
        // Bloqueamos el acceso si el usuario no es admin.
        // ¿Para qué? Para evitar que usuarios normales descarguen o borren respaldos de la base de datos.
        if (!Auth::check() || ($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect(URL_ROOT . '/dashboard');
        }
        
        // Configuramos la carpeta donde se guardarán los archivos SQL físicamente.
        $this->backupDir = dirname(dirname(__DIR__)) . '/storage/backups';
        
        // Si la carpeta no existe, la creamos automáticamente para prevenir errores.
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function index() {
        // Capturamos la IP del servidor donde corre PHP y el puerto
        $ip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
        $port = $_SERVER['SERVER_PORT'] ?? '80';
        
        // Verificamos si la base de datos está respondiendo.
        try {
            Database::getInstance()->getDbh();
            $dbStatus = 'En Línea';
        } catch (\Exception $e) {
            $dbStatus = 'Desconectada';
        }

        // Leemos todos los archivos .sql en la carpeta de respaldos.
        // ¿Por qué con glob()? Porque es la forma más rápida de obtener un arreglo de archivos filtrando por extensión.
        $files = glob($this->backupDir . '/*.sql');
        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)), // Fecha de creación del archivo
                'size' => round(filesize($file) / 1024, 2) . ' KB' // Tamaño legible en Kilobytes
            ];
        }

        // Ordenamos el arreglo de respaldos de más reciente a más antiguo, para que el último salga primero en la tabla.
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Lógica de 6 meses: Revisamos si ya pasó medio año desde el último respaldo.
        // Si no hay respaldos, o el último tiene más de 180 días, activaremos una alerta visual.
        $needsBackup = true;
        if (count($backups) > 0) {
            $lastBackup = strtotime($backups[0]['date']);
            $sixMonthsAgo = strtotime('-6 months');
            
            // Si el último respaldo es MÁS RECIENTE que hace 6 meses, entonces no necesitamos respaldar obligatoriamente.
            if ($lastBackup > $sixMonthsAgo) {
                $needsBackup = false;
            }
        }

        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);

        $this->render('backup', [
            'activeMenu' => 'backup',
            'ip' => $ip,
            'port' => $port,
            'dbStatus' => $dbStatus,
            'backups' => $backups,
            'needsBackup' => $needsBackup,
            'success' => $success
        ]);
    }

    public function generate() {
        // Generar respaldo manual desde el botón del frontend
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf(); // Validamos seguridad anti-falsificación
            $this->createBackup(); // Creamos el archivo
            
            $_SESSION['success'] = "¡Copia de seguridad generada correctamente!";
            $this->redirect(URL_ROOT . '/backup');
        }
    }

    // Método que hace la magia de extraer los datos de MySQL y convertirlos a texto plano (.sql)
    public function createBackup() {
        $pdo = Database::getInstance()->getDbh();
        $tables = [];
        
        // Obtenemos una lista con los nombres de todas las tablas de la BD.
        $query = $pdo->query('SHOW TABLES');
        while ($row = $query->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- Respaldo de Base de Datos Sistema de Presupuesto Personal\n";
        $sql .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n\n";

        // Iteramos tabla por tabla para sacar su estructura y sus filas de datos.
        foreach ($tables as $table) {
            // Añadimos DROP TABLE para que al restaurar no haya conflictos si la tabla ya existe
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $row2 = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_NUM);
            $sql .= "\n\n" . $row2[1] . ";\n\n";

            // Ahora extraemos las filas (los registros reales de la tabla)
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    // Escapamos los valores con quote() para que si un texto tiene comillas simples, no rompa la consulta SQL.
                    $vals = array_map(function($v) use ($pdo) {
                        return ($v === null) ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    
                    $sql .= "INSERT INTO `$table` VALUES(" . implode(', ', $vals) . ");\n";
                }
            }
            $sql .= "\n\n";
        }

        // Creamos el nombre de archivo con fecha y hora para que no se sobreescriban
        $filename = 'backup_' . date('Y_m_d_His') . '.sql';
        $filepath = $this->backupDir . '/' . $filename;
        
        // Guardamos todo el texto generado en el archivo físico
        file_put_contents($filepath, $sql);

        // Control de almacenamiento: Si hay más de 10 respaldos, borramos el más viejo para no saturar el disco del servidor.
        $files = glob($this->backupDir . '/*.sql');
        if (count($files) > 10) {
            array_multisort(array_map('filemtime', $files), SORT_ASC, $files);
            unlink($files[0]); // unlink() borra el archivo
        }

        return $filename;
    }

    public function download($filename) {
        // Bloqueamos directorios de subida para evitar que intenten descargar archivos de sistema con rutas como "../"
        $filepath = $this->backupDir . '/' . basename($filename);
        
        // Forzamos al navegador a descargar el archivo en vez de intentar abrirlo
        if (file_exists($filepath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
        $this->redirect(URL_ROOT . '/backup');
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            $filename = $_POST['filename'] ?? '';
            $filepath = $this->backupDir . '/' . basename($filename);
            
            if (file_exists($filepath)) {
                unlink($filepath);
                $_SESSION['success'] = "El archivo de respaldo fue eliminado del servidor.";
            }
            $this->redirect(URL_ROOT . '/backup');
        }
    }
}
