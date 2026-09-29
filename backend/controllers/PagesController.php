<?php
use Core\Controller;

class PagesController extends Controller {
    public function __construct() {
        // Cargar modelos si es necesario
    }

    public function index() {
        $data = [
            'title' => 'Bienvenido a Sistema de Presupuesto Personal'
        ];

        $this->view('pages', $data);
    }

    public function privacidad() {
        $data = [
            'title' => 'Política de Privacidad'
        ];

        $this->view('privacidad', $data);
    }
}
