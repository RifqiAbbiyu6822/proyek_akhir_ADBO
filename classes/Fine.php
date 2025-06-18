<?php

class Fine {
    private $conn;
    private $table_name = "fines";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserFines($user_id) {
        try {
            $query = "SELECT f.*, r.rent_date, r.return_date, l.name as lens_name
                     FROM " . $this->table_name . " f
                     JOIN rentals r ON f.rental_id = r.id
                     JOIN lenses l ON r.lens_id = l.id
                     WHERE f.user_id = ? 
                     ORDER BY f.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            // Tampilkan error detail
            echo '<b>PDO Error (getUserFines):</b> ' . $e->getMessage() . '<br>';
            error_log("Error getting user fines: " . $e->getMessage());
            throw new Exception("Gagal mengambil data denda");
        }
    }

    public function create($user_id, $rental_id, $amount, $description) {
        try {
            // Validate input
            if ($amount <= 0) {
                throw new Exception("Jumlah denda harus lebih dari 0");
            }

            // Check if rental exists and belongs to user
            $query = "SELECT id FROM rentals WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$rental_id, $user_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception("Data penyewaan tidak ditemukan");
            }

            // Check if fine already exists for this rental
            $query = "SELECT id FROM " . $this->table_name . " WHERE rental_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$rental_id]);
            
            if ($stmt->fetch()) {
                throw new Exception("Denda untuk penyewaan ini sudah ada");
            }

            // Create fine
            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, rental_id, amount, description, status) 
                     VALUES (?, ?, ?, ?, 'pending')";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id, $rental_id, $amount, $description]);
        } catch (PDOException $e) {
            error_log("Error creating fine: " . $e->getMessage());
            throw new Exception("Gagal membuat denda");
        }
    }

    public function updateStatus($id, $status) {
        try {
            if (!in_array($status, ['pending', 'paid'])) {
                throw new Exception("Status tidak valid");
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET status = ? 
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Error updating fine status: " . $e->getMessage());
            throw new Exception("Gagal mengupdate status denda");
        }
    }

    public function calculateLateFine($rental_id) {
        try {
            // Get rental details
            $query = "SELECT r.*, l.price_per_day 
                     FROM rentals r 
                     JOIN lenses l ON r.lens_id = l.id 
                     WHERE r.id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$rental_id]);
            $rental = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rental) {
                throw new Exception("Data penyewaan tidak ditemukan");
            }

            $return_date = strtotime($rental['return_date']);
            $today = strtotime(date('Y-m-d'));
            
            if ($today <= $return_date) {
                return 0; // No fine if returned on time
            }

            $days_late = ceil(($today - $return_date) / (60 * 60 * 24));
            $fine_amount = $days_late * ($rental['price_per_day'] * 0.5); // 50% of daily rate per day late

            return $fine_amount;
        } catch (PDOException $e) {
            error_log("Error calculating late fine: " . $e->getMessage());
            throw new Exception("Gagal menghitung denda keterlambatan");
        }
    }
}

?>