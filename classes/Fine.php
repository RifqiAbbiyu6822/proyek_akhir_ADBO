<?php

class Fine {
    private $conn;
    private $table_name = "fines";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserFines($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE user_id = ? 
                 ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function create($user_id, $rental_id, $amount, $reason) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (user_id, rental_id, amount, reason, status, date) 
                 VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $rental_id, $amount, $reason]);
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = ? 
                 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $id]);
    }
}

?>