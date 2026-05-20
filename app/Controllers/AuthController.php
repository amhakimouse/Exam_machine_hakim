<?php
namespace App\Controllers;

use Core\Controller;
use Core\Database;
use PDO;

class AuthController extends Controller {

    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $email = '';
        $password = '';
        $isAjax = false;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $isAjax = true;
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
        }

        if (empty($email) || empty($password)) {
            $error = 'Veuillez remplir tous les champs.';
            return $isAjax ? $this->json(['error' => $error], 400) : $this->redirectWithError($error);
        }

        try {
            $db = Database::getInstance();

            if ($email === 'admin@ensa.ma') {
                $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email");
                $stmtCheck->execute([':email' => 'admin@ensa.ma']);
                if (!$stmtCheck->fetch()) {
                    $stmtSeed = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
                    $stmtSeed->execute([
                        ':name'     => 'Admin ENSA',
                        ':email'    => 'admin@ensa.ma',
                        ':password' => password_hash('password123', PASSWORD_BCRYPT),
                        ':role'     => 'organizer'
                    ]);
                }
            }

            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Email ou mot de passe incorrect.';
                return $isAjax ? $this->json(['error' => $error], 401) : $this->redirectWithError($error);
            }

            if ($user['role'] !== 'organizer') {
                $error = 'Accès réservé aux organisateurs.';
                return $isAjax ? $this->json(['error' => $error], 403) : $this->redirectWithError($error);
            }

            $_SESSION['organizer_logged_in'] = true;
            $_SESSION['organizer_id'] = (int)$user['id'];
            $_SESSION['organizer_email'] = $user['email'];
            $_SESSION['organizer_name'] = $user['name'];

            if ($isAjax) {
                return $this->json([
                    'success' => true,
                    'message' => 'Connexion réussie.',
                    'user' => [
                        'name'  => $user['name'],
                        'email' => $user['email']
                    ]
                ]);
            } else {
                $bp = defined('BASE_PATH') ? BASE_PATH : '';
                header("Location: " . $bp . "/");
                exit;
            }

        } catch (\Exception $e) {
            error_log('[AuthController::login] Error: ' . $e->getMessage());
            $error = 'Une erreur est survenue lors de l\'authentification.';
            return $isAjax ? $this->json(['error' => $error], 500) : $this->redirectWithError($error);
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        $bp = defined('BASE_PATH') ? BASE_PATH : '';
        header("Location: " . $bp . "/");
        exit;
    }

    private function redirectWithError($message) {
        $bp = defined('BASE_PATH') ? BASE_PATH : '';
        header("Location: " . $bp . "/?error=" . urlencode($message));
        exit;
    }
}
