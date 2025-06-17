<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function validatePassword($password) {
        // Password must be at least 8 characters long and contain at least one number and one letter
        return strlen($password) >= 8 && preg_match('/[0-9]/', $password) && preg_match('/[a-zA-Z]/', $password);
    }

    public function register() {
        // Validate input
        if (!$this->validateEmail($this->email)) {
            throw new Exception("Email tidak valid");
        }

        if (!$this->validatePassword($this->password)) {
            throw new Exception("Password harus minimal 8 karakter dan mengandung angka dan huruf");
        }

        if (strlen($this->name) < 2) {
            throw new Exception("Nama harus minimal 2 karakter");
        }

        // Check if email exists
        if ($this->emailExists()) {
            throw new Exception("Email sudah terdaftar");
        }

        $query = "INSERT INTO " . $this->table_name . " (name, email, password) VALUES (:name, :email, :password)";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);

        try {
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            throw new Exception("Terjadi kesalahan saat registrasi");
        }
    }

    public function emailExists() {
        $query = "SELECT id, name, password FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        
        try {
            $stmt->execute();
            $num = $stmt->rowCount();
            
            if($num > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->password = $row['password'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            throw new Exception("Terjadi kesalahan saat memeriksa email");
        }
    }

    public function login($password) {
        if (!$this->emailExists()) {
            return false;
        }

        return password_verify($password, $this->password);
    }
}
?>
