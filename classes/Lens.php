<?php
class Lens {
    private $conn;
    private $table_name = "lenses";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error reading all lenses: " . $e->getMessage());
            throw new Exception("Gagal mengambil data lensa");
        }
    }

    public function readAvailable() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE status = 'available' ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error reading available lenses: " . $e->getMessage());
            throw new Exception("Gagal mengambil data lensa yang tersedia");
        }
    }

    public function readOne($id) {
        try {
            if (!is_numeric($id) || $id <= 0) {
                throw new Exception("ID lensa tidak valid");
            }

            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                throw new Exception("Lensa tidak ditemukan");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error reading lens: " . $e->getMessage());
            throw new Exception("Gagal mengambil data lensa");
        }
    }

    public function updateStatus($id, $status) {
        try {
            if (!is_numeric($id) || $id <= 0) {
                throw new Exception("ID lensa tidak valid");
            }

            if (!in_array($status, ['available', 'rented'])) {
                throw new Exception("Status tidak valid");
            }

            $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt->execute([$status, $id])) {
                throw new Exception("Gagal mengupdate status lensa");
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error updating lens status: " . $e->getMessage());
            throw new Exception("Gagal mengupdate status lensa");
        }
    }

    public function create($name, $description, $price_per_day) {
        try {
            // Validate input
            if (empty($name) || strlen($name) < 3) {
                throw new Exception("Nama lensa harus minimal 3 karakter");
            }

            if (empty($description)) {
                throw new Exception("Deskripsi lensa harus diisi");
            }

            if (!is_numeric($price_per_day) || $price_per_day <= 0) {
                throw new Exception("Harga sewa harus lebih dari 0");
            }

            $query = "INSERT INTO " . $this->table_name . " 
                     (name, description, price_per_day, status) 
                     VALUES (?, ?, ?, 'available')";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$name, $description, $price_per_day]);
        } catch (PDOException $e) {
            error_log("Error creating lens: " . $e->getMessage());
            throw new Exception("Gagal menambahkan lensa baru");
        }
    }

    public function update($id, $name, $description, $price_per_day) {
        try {
            if (!is_numeric($id) || $id <= 0) {
                throw new Exception("ID lensa tidak valid");
            }

            // Validate input
            if (empty($name) || strlen($name) < 3) {
                throw new Exception("Nama lensa harus minimal 3 karakter");
            }

            if (empty($description)) {
                throw new Exception("Deskripsi lensa harus diisi");
            }

            if (!is_numeric($price_per_day) || $price_per_day <= 0) {
                throw new Exception("Harga sewa harus lebih dari 0");
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET name = ?, description = ?, price_per_day = ? 
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$name, $description, $price_per_day, $id]);
        } catch (PDOException $e) {
            error_log("Error updating lens: " . $e->getMessage());
            throw new Exception("Gagal mengupdate data lensa");
        }
    }

    public function delete($id) {
        try {
            if (!is_numeric($id) || $id <= 0) {
                throw new Exception("ID lensa tidak valid");
            }

            // Check if lens is currently rented
            $query = "SELECT status FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $lens = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lens['status'] === 'rented') {
                throw new Exception("Tidak dapat menghapus lensa yang sedang disewa");
            }

            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting lens: " . $e->getMessage());
            throw new Exception("Gagal menghapus lensa");
        }
    }
}
?>