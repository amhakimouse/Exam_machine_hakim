<?php
namespace App\Models;

use Core\Database;
use PDO;
use Exception;

class RegistrationModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function register(int $eventId, string $userName, string $userEmail): array {

        $dupStmt = $this->db->prepare(
            "SELECT id FROM registrations
             WHERE event_id = :event_id AND email = :email
             LIMIT 1"
        );
        $dupStmt->execute([':event_id' => $eventId, ':email' => $userEmail]);
        if ($dupStmt->fetch()) {
            throw new Exception("Vous êtes déjà inscrit à cet événement.");
        }

        $this->db->beginTransaction();

        try {
            $eventStmt = $this->db->prepare(
                "SELECT e.id, e.title, e.capacity, e.location, e.event_date AS date,
                        e.organizer_email, e.category, e.alert_sent,
                        COUNT(r.id) AS registered
                 FROM events e
                 LEFT JOIN registrations r ON r.event_id = e.id
                 WHERE e.id = :event_id
                 GROUP BY e.id
                 FOR UPDATE"
            );
            $eventStmt->execute([':event_id' => $eventId]);
            $event = $eventStmt->fetch();

            if (!$event) {
                $this->db->rollBack();
                throw new Exception("Événement introuvable.");
            }

            $registered = (int) $event['registered'];
            $capacity   = (int) $event['capacity'];

            if ($registered >= $capacity) {
                $this->db->rollBack();
                throw new Exception("L'événement est complet.");
            }

            $token = bin2hex(random_bytes(16));

            $insertStmt = $this->db->prepare(
                "INSERT INTO registrations (event_id, name, email, token)
                 VALUES (:event_id, :name, :email, :token)"
            );
            $insertStmt->execute([
                ':event_id'   => $eventId,
                ':name'  => $userName,
                ':email' => $userEmail,
                ':token'      => $token,
            ]);

            $this->db->commit();

            return [
                'token'            => $token,
                'registered_count' => $registered + 1,
                'capacity'         => $capacity,
                'event'            => $event,
            ];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getByEventId(int $eventId): array {
        $stmt = $this->db->prepare(
            "SELECT id, name AS user_name, email AS user_email, token, registered_at
             FROM registrations
             WHERE event_id = :event_id
             ORDER BY registered_at ASC"
        );
        $stmt->execute([':id' => $eventId]);
        return $stmt->fetchAll();
    }
}
