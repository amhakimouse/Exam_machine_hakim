<?php
namespace App\Controllers;

use Core\Controller;
use Core\Database;
use App\Models\EventModel;
use App\Models\RegistrationModel;

class ApiController extends Controller {

    public function searchEvents(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method Not Allowed'], 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $filters = [
            'keyword'  => trim($input['keyword']  ?? ''),
            'category' => trim($input['category'] ?? ''),
        ];

        $allowed = ['', 'tech', 'design', 'business', 'science'];
        if (!in_array($filters['category'], $allowed, true)) {
            $filters['category'] = '';
        }

        try {
            $eventModel = new EventModel();
            $events     = $eventModel->search($filters);
            $this->json($events);
        } catch (\Exception $e) {
            error_log('[ApiController::searchEvents] ' . $e->getMessage());
            $this->json(['error' => 'Erreur serveur lors de la recherche.'], 500);
        }
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method Not Allowed'], 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $eventId   = filter_var($input['event_id']   ?? 0,  FILTER_VALIDATE_INT);
        $userName  = trim($input['user_name']  ?? '');
        $userEmail = filter_var($input['user_email']  ?? '', FILTER_VALIDATE_EMAIL);

        if (!$eventId || $eventId <= 0) {
            $this->json(['error' => 'Identifiant d\'événement invalide.'], 400);
            return;
        }
        if (empty($userName) || mb_strlen($userName) > 255) {
            $this->json(['error' => 'Nom invalide (1–255 caractères requis).'], 400);
            return;
        }
        if (!$userEmail) {
            $this->json(['error' => 'Adresse email invalide.'], 400);
            return;
        }

        try {
            $regModel = new RegistrationModel();
            $result   = $regModel->register($eventId, $userName, $userEmail);

            $registeredCount = $result['registered_count'];
            $capacity        = $result['capacity'];
            $fillRate        = $capacity > 0 ? $registeredCount / $capacity : 1;
            $alert80         = $fillRate >= 0.80;

            $regData = [
                'token'            => $result['token'],
                'registered_count' => $registeredCount,
                'capacity'         => $capacity,
                'user_name'        => $userName,
                'user_email'       => $userEmail,
            ];

            try {
                $pdfCtrl  = new PdfController();
                $mailCtrl = new MailController();

                $ticketPath = $pdfCtrl->generateTicket($regData, $result['event']);
                $mailCtrl->sendConfirmation($regData, $result['event'], $ticketPath);
                $pdfCtrl->cleanup($ticketPath);

                if ($alert80 && (int)$result['event']['alert_sent'] === 0) {
                    $reportPath = $pdfCtrl->generateOrganizerReport($result['event'], $registeredCount);
                    if ($mailCtrl->sendCapacityAlert($result['event'], $registeredCount, $reportPath)) {
                        $updateAlertStmt = Database::getInstance()->prepare("UPDATE events SET alert_sent = 1 WHERE id = :id");
                        $updateAlertStmt->execute([':id' => $eventId]);
                    }
                    $pdfCtrl->cleanup($reportPath);
                }

            } catch (\Exception $mailErr) {
                error_log('[ApiController::register] mail/pdf error: ' . $mailErr->getMessage());
            }

            $this->json([
                'success'          => true,
                'token'            => $result['token'],
                'registered_count' => $registeredCount,
                'capacity'         => $capacity,
                'alert_80'         => $alert80,
                'message'          => 'Inscription réussie ! Vous recevrez votre ticket PDF par email.',
            ]);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function createEvent(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method Not Allowed'], 405);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['organizer_logged_in']) || $_SESSION['organizer_logged_in'] !== true) {
            $this->json(['error' => 'Accès non autorisé. Veuillez vous connecter.'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $required = ['title', 'description', 'date', 'location', 'capacity', 'category', 'organizer_email'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->json(['error' => "Le champ « $field » est requis."], 400);
                return;
            }
        }

        $capacity = filter_var($input['capacity'], FILTER_VALIDATE_INT);
        if ($capacity === false || $capacity < 1 || $capacity > 100000) {
            $this->json(['error' => 'La capacité doit être un entier entre 1 et 100 000.'], 400);
            return;
        }

        $email = filter_var($input['organizer_email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $this->json(['error' => 'Email organisateur invalide.'], 400);
            return;
        }

        $allowed = ['tech', 'design', 'business', 'science'];
        if (!in_array($input['category'], $allowed, true)) {
            $this->json(['error' => 'Catégorie invalide.'], 400);
            return;
        }

        $dateRaw = str_replace('T', ' ', $input['date']);
        $date    = \DateTime::createFromFormat('Y-m-d H:i', $dateRaw)
                   ?: \DateTime::createFromFormat('Y-m-d H:i:s', $dateRaw);
        if (!$date) {
            $this->json(['error' => 'Format de date invalide.'], 400);
            return;
        }

        try {
            $eventModel = new EventModel();
            $eventModel->create([
                'title'           => htmlspecialchars(strip_tags($input['title']),    ENT_QUOTES, 'UTF-8'),
                'description'     => htmlspecialchars(strip_tags($input['description']), ENT_QUOTES, 'UTF-8'),
                'date'            => $date->format('Y-m-d H:i:s'),
                'location'        => htmlspecialchars(strip_tags($input['location']), ENT_QUOTES, 'UTF-8'),
                'capacity'        => $capacity,
                'category'        => $input['category'],
                'organizer_email' => $email,
            ]);

            $newId = Database::getInstance()->lastInsertId();
            $this->json(['success' => true, 'event_id' => (int) $newId]);

        } catch (\Exception $e) {
            error_log('[ApiController::createEvent] ' . $e->getMessage());
            $this->json(['error' => 'Erreur serveur lors de la création.'], 500);
        }
    }

    public function stats(): void {
        try {
            $db = Database::getInstance();

            $totalRegs = (int) $db->query(
                "SELECT COUNT(*) FROM registrations"
            )->fetchColumn();

            $new24h = (int) $db->query(
                "SELECT COUNT(*) FROM registrations
                 WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            )->fetchColumn();

            $avgFill = (float) $db->query(
                "SELECT IFNULL(AVG(fill_pct), 0) FROM
                    (SELECT ROUND(COUNT(r.id) / e.capacity * 100, 1) AS fill_pct
                     FROM events e
                     LEFT JOIN registrations r ON r.event_id = e.id
                     GROUP BY e.id) AS t"
            )->fetchColumn();

            $eventsAt80 = (int) $db->query(
                "SELECT COUNT(*) FROM
                    (SELECT e.id
                     FROM events e
                     LEFT JOIN registrations r ON r.event_id = e.id
                     GROUP BY e.id, e.capacity
                     HAVING COUNT(r.id) / e.capacity >= 0.80) AS t"
            )->fetchColumn();

            $top3 = $db->query(
                "SELECT e.id, e.title, e.category, e.capacity,
                        COUNT(r.id)                              AS registered_count,
                        ROUND(COUNT(r.id) / e.capacity * 100)   AS fill_rate
                 FROM events e
                 LEFT JOIN registrations r ON r.event_id = e.id
                 GROUP BY e.id, e.title, e.category, e.capacity
                 ORDER BY fill_rate DESC
                 LIMIT 3"
            )->fetchAll();

            $this->json([
                'total_registrations' => $totalRegs,
                'new_24h'             => $new24h,
                'avg_fill_rate'       => (int) round($avgFill),
                'events_at_80'        => $eventsAt80,
                'top_events'          => $top3,
                'generated_at'        => date('H:i:s'),
            ]);

        } catch (\Exception $e) {
            error_log('[ApiController::stats] ' . $e->getMessage());
            $this->json(['error' => 'Impossible de charger les statistiques.'], 500);
        }
    }
}
