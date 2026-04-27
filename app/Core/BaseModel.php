<?php
// app/Core/BaseModel.php
namespace App\Core;

use App\Core\Database;
use PDO;

class BaseModel {
    /** @var PDO */
    protected $db;

    public function __construct($db = null) {
        if ($db instanceof PDO) {
            $this->db = $db;
        } else {
            $this->db = Database::getConnection();
        }
    }
}
