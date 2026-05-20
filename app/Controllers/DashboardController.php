<?php
namespace App\Controllers;

use Core\Controller;

class DashboardController extends Controller {

    public function index(): void {
        $this->view('dashboard/index', [
            'title'       => 'Dashboard — EventHub Pro',
            'currentPage' => 'dashboard',
        ]);
    }
}
