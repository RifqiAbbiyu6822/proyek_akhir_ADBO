<?php

class Rental {
    private $conn;
    private $table_name = "rentals";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $lens_id, $rental_date, $return_date) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=?, lens_id=?, rental_date=?, return_date=?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $lens_id, $rental_date, $return_date]);
    }

    public function getUserRentals($user_id) {
        $query = "SELECT r.*, l.name as lens_name, l.price_per_day 
                  FROM " . $this->table_name . " r 
                  JOIN lenses l ON r.lens_id = l.id 
                  WHERE r.user_id = ? 
                  ORDER BY r.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt;
    }

    public function returnLens($rental_id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'returned' WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$rental_id]);
    }

    public function getOverdueRentals() {
        $query = "SELECT r.*, l.name as lens_name, u.name as user_name, u.email 
                  FROM " . $this->table_name . " r 
                  JOIN lenses l ON r.lens_id = l.id 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.status = 'active' AND r.return_date < CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

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