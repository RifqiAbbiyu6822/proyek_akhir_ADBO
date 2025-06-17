<?php
class Rental {
    private $conn;
    private $table_name = "rentals";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserRentals($user_id) {
        $query = "SELECT r.*, l.name as lens_name 
                 FROM " . $this->table_name . " r 
                 JOIN lenses l ON r.lens_id = l.id 
                 WHERE r.user_id = ? 
                 ORDER BY r.rent_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function create($user_id, $lens_id, $rent_date, $return_date) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (user_id, lens_id, rent_date, return_date, status) 
                 VALUES (?, ?, ?, ?, 'active')";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $lens_id, $rent_date, $return_date]);
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