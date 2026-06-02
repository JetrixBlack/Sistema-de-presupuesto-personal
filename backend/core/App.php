<?php
namespace Core;

class App {
    protected $currentController = 'PagesController';
    protected $currentMethod = 'index';
    protected $params = [];

    // Route aliases: url segment => ControllerName (without suffix)
    private $aliases = [
        'users'       => 'User',
        'history'     => 'History',
        'finance'     => 'Finance',
        'inventory'   => 'Inventory',
        'support'     => 'Support',
        'settings'    => 'Settings',
        'transaction' => 'Transaction',
        'dashboard'   => 'Dashboard',
        'auth'        => 'Auth',
        'pdf'         => 'Pdf',
        'backup'      => 'Backup',
    ];

    public function __construct() {
        $url = $this->getUrl();

        // Resolve controller
        if (isset($url[0])) {
            $segment = strtolower($url[0]);
            $base    = $this->aliases[$segment] ?? ucwords($segment);
            $controllerName = $base . 'Controller';
            if (file_exists('../backend/controllers/' . $controllerName . '.php')) {
                $this->currentController = $controllerName;
                unset($url[0]);
            }
        }

        require_once '../backend/controllers/' . $this->currentController . '.php';
        $this->currentController = new $this->currentController;

        // Resolve method
        if (isset($url[1])) {
            if (method_exists($this->currentController, $url[1])) {
                $this->currentMethod = $url[1];
                unset($url[1]);
            }
        }

        // Params
        $this->params = $url ? array_values($url) : [];

        // Track real-time activity for authenticated users
        if (\Core\Auth::check()) {
            $pdo = \Core\Database::getInstance()->getDbh();
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([\Core\Auth::id()]);
        }

        // Dispatch
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }

    public function getUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }
}
