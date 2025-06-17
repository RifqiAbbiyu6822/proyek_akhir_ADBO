<?php
class HistoryController extends Controller {
    private $rentalModel;

    public function __construct() {
        if(!isset($_SESSION['user_id'])) {
            $this->redirect('auth');
        }
        $this->rentalModel = $this->model('Rental');
    }

    public function index() {
        $rentals = $this->rentalModel->getRentalHistoryByUser($_SESSION['user_id']);
        $this->view('history/index', ['rentals' => $rentals]);
    }

    public function detail($id) {
        $rental = $this->rentalModel->getRentalById($id);
        if($rental && $rental->user_id == $_SESSION['user_id']) {
            $this->view('history/detail', ['rental' => $rental]);
        } else {
            $this->redirect('history');
        }
    }
} 