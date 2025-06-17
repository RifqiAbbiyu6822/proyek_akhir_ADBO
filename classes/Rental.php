<?php
class Rental {
    private $conn;
    private $table_name = "rentals";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserRentals($user_id) {
        try {
            $query = "SELECT r.*, l.name as lens_name, l.price_per_day,
                     (DATEDIFF(r.return_date, r.rent_date) + 1) * l.price_per_day as total_price
                     FROM " . $this->table_name . " r 
                     JOIN lenses l ON r.lens_id = l.id 
                     WHERE r.user_id = ? 
                     ORDER BY r.rent_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error getting user rentals: " . $e->getMessage());
            throw new Exception("Gagal mengambil data penyewaan");
        }
    }

    public function create($user_id, $lens_id, $rent_date, $return_date, $total_price) {
        try {
            // Validate input
            if (!$this->validateDates($rent_date, $return_date)) {
                throw new Exception("Tanggal tidak valid");
            }

            // Check if lens is available
            $query = "SELECT status FROM lenses WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$lens_id]);
            $lens = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lens || $lens['status'] !== 'available') {
                throw new Exception("Lensa tidak tersedia");
            }

            // Check if user has active rentals
            $query = "SELECT COUNT(*) as active_rentals FROM " . $this->table_name . " 
                     WHERE user_id = ? AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['active_rentals'] >= 3) {
                throw new Exception("Anda telah mencapai batas maksimum penyewaan aktif (3)");
            }

            // Create rental
            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, lens_id, rent_date, return_date, status, total_price) 
                     VALUES (?, ?, ?, ?, 'active', ?)";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id, $lens_id, $rent_date, $return_date, $total_price]);
        } catch (PDOException $e) {
            error_log("Error creating rental: " . $e->getMessage());
            throw new Exception("Gagal membuat penyewaan");
        }
    }

    public function updateStatus($id, $status) {
        try {
            if (!in_array($status, ['active', 'returned'])) {
                throw new Exception("Status tidak valid");
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET status = ? 
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Error updating rental status: " . $e->getMessage());
            throw new Exception("Gagal mengupdate status penyewaan");
        }
    }

    private function validateDates($rent_date, $return_date) {
        $rent_timestamp = strtotime($rent_date);
        $return_timestamp = strtotime($return_date);
        $today_timestamp = strtotime(date('Y-m-d'));

        return $rent_timestamp >= $today_timestamp && 
               $return_timestamp > $rent_timestamp;
    }
}
?> 