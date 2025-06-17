<?php
class Rental extends Model {
    public function createRental($data) {
        $this->db->query('INSERT INTO rentals (user_id, lens_id, rental_date, return_date, status) 
                         VALUES (:user_id, :lens_id, :rental_date, :return_date, :status)');
        
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':lens_id', $data['lens_id']);
        $this->db->bind(':rental_date', $data['rental_date']);
        $this->db->bind(':return_date', $data['return_date']);
        $this->db->bind(':status', $data['status']);

        return $this->db->execute();
    }

    public function getActiveRentalsByUser($user_id) {
        $this->db->query('SELECT r.*, l.name as lens_name, l.description 
                         FROM rentals r 
                         JOIN lenses l ON r.lens_id = l.id 
                         WHERE r.user_id = :user_id AND r.status = :status');
        
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':status', 'active');
        
        return $this->db->resultSet();
    }

    public function getRentalById($id) {
        $this->db->query('SELECT r.*, l.name as lens_name, l.description, l.price_per_day,
                         u.name as user_name, u.email as user_email
                         FROM rentals r 
                         JOIN lenses l ON r.lens_id = l.id 
                         JOIN users u ON r.user_id = u.id
                         WHERE r.id = :id');
        
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function updateRentalStatus($id, $status) {
        $this->db->query('UPDATE rentals SET status = :status WHERE id = :id');
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getRentalHistoryByUser($user_id) {
        $this->db->query('SELECT r.*, l.name as lens_name, l.description,
                         (SELECT amount FROM fines WHERE rental_id = r.id LIMIT 1) as fine_amount
                         FROM rentals r 
                         JOIN lenses l ON r.lens_id = l.id 
                         WHERE r.user_id = :user_id 
                         ORDER BY r.created_at DESC');
        
        $this->db->bind(':user_id', $user_id);
        return $this->db->resultSet();
    }
} 