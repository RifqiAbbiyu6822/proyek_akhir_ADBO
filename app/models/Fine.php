<?php
class Fine extends Model {
    public function createFine($data) {
        $this->db->query('INSERT INTO fines (rental_id, amount, status) 
                         VALUES (:rental_id, :amount, :status)');
        
        $this->db->bind(':rental_id', $data['rental_id']);
        $this->db->bind(':amount', $data['amount']);
        $this->db->bind(':status', $data['status']);

        return $this->db->execute();
    }

    public function getFinesByRental($rental_id) {
        $this->db->query('SELECT * FROM fines WHERE rental_id = :rental_id');
        $this->db->bind(':rental_id', $rental_id);
        return $this->db->resultSet();
    }

    public function updateFineStatus($id, $status) {
        $this->db->query('UPDATE fines SET status = :status WHERE id = :id');
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
} 