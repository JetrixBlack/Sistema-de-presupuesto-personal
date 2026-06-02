<?php
namespace Core;

class Cache {
    private $cacheDir;
    private $enabled;

    public function __construct() {
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        $this->enabled = ($_ENV['CACHE_ENABLED'] ?? 'true') === 'true';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get($key) {
        if (!$this->enabled) return null;
        $file = $this->cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] > time()) {
                return $data['content'];
            }
            unlink($file);
        }
        return null;
    }

    public function set($key, $content, $duration = 3600) {
        if (!$this->enabled) return false;
        $file = $this->cacheDir . '/' . md5($key) . '.cache';
        $data = [
            'expires' => time() + $duration,
            'content' => $content
        ];
        return file_put_contents($file, serialize($data));
    }

    public function delete($key) {
        $file = $this->cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    /**
     * Alias for delete() to support calls in controllers
     */
    public function clear($key) {
        return $this->delete($key);
    }

    /**
     * Borra todos los archivos de cache guardados en el directorio
     */
    public function clearAll() {
        $files = glob($this->cacheDir . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        return true;
    }
}
