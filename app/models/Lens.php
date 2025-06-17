<?php
class Lens extends Model {
    public function getAvailableLenses() {
        $this->db->query('SELECT * FROM lenses WHERE status = :status');
        $this->db->bind(':status', 'available');
        return $this->db->resultSet();
    }

    public function getLensById($id) {
        $this->db->query('SELECT * FROM lenses WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function updateLensStatus($id, $status) {
        $this->db->query('UPDATE lenses SET status = :status WHERE id = :id');
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function addLens($data) {
        $this->db->query('INSERT INTO lenses (name, description, price_per_day, status) 
                         VALUES (:name, :description, :price_per_day, :status)');
        
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':price_per_day', $data['price_per_day']);
        $this->db->bind(':status', 'available');

        return $this->db->execute();
    }
} 