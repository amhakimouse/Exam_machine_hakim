<?php
namespace App\Models;

use Core\Database;
use PDO;

class EventModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function search($filters = []) {
        $query = "SELECT e.*, e.event_date AS date, 
                  (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered_count 
                  FROM events e WHERE 1=1";
        $params = [];

        if (!empty($filters['keyword'])) {
            $query .= " AND (e.title LIKE :keyword OR e.description LIKE :keyword OR e.location LIKE :keyword)";
            $params[':keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['category'])) {
            $query .= " AND e.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        $query .= " ORDER BY e.event_date ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT e.*, e.event_date AS date, 
                  (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered_count 
                  FROM events e WHERE e.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $query = "INSERT INTO events (title, description, event_date, location, capacity, category, organizer_email) 
                  VALUES (:title, :description, :event_date, :location, :capacity, :category, :organizer_email)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':event_date' => $data['date'],
            ':location' => $data['location'],
            ':capacity' => $data['capacity'],
            ':category' => $data['category'],
            ':organizer_email' => $data['organizer_email']
        ]);
    }
}
