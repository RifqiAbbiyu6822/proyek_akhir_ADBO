<?php
class RentalController extends Controller {
    private $lensModel;
    private $rentalModel;

    public function __construct() {
        if(!isset($_SESSION['user_id'])) {
            $this->redirect('auth');
        }
        $this->lensModel = $this->model('Lens');
        $this->rentalModel = $this->model('Rental');
    }

    public function index() {
        $lenses = $this->lensModel->getAvailableLenses();
        $this->view('rental/rent_form', ['lenses' => $lenses]);
    }

    public function rent() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'lens_id' => $_POST['lens_id'],
                'user_id' => $_SESSION['user_id'],
                'rental_date' => date('Y-m-d'),
                'return_date' => $_POST['return_date'],
                'status' => 'active'
            ];

            if($this->rentalModel->createRental($data)) {
                $this->lensModel->updateLensStatus($data['lens_id'], 'rented');
                $this->redirect('rental/success');
            } else {
                $this->view('rental/rent_form', ['error' => 'Failed to process rental']);
            }
        }
    }

    public function success() {
        $this->view('rental/success');
    }
} 