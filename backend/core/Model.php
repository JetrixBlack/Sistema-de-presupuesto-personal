<?php
namespace Core;

class Model {
    protected $db;

    public function __construct() {
        $db_obj = new Database();
        $this->db = $db_obj->getDbh();
    }
}
