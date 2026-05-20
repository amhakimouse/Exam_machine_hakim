<?php
namespace App\Controllers;

use Core\Controller;

class EventController extends Controller {

    public function index(): void {
        $this->view('events/index', [
            'title'       => 'Événements — EventHub Pro',
            'currentPage' => 'events',
        ]);
    }

    public function create(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['organizer_logged_in']) || $_SESSION['organizer_logged_in'] !== true) {
            $bp = defined('BASE_PATH') ? BASE_PATH : '';
            header("Location: " . $bp . "/?error=" . urlencode("Veuillez vous connecter pour accéder à cette page."));
            exit;
        }
        $this->view('events/create', [
            'title'       => 'Créer un événement — EventHub Pro',
            'currentPage' => 'create',
        ]);
    }
}
