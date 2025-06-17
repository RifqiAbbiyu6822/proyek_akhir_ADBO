<?php
class ReturnController extends Controller {
    private $rentalModel;
    private $lensModel;
    private $fineModel;

    public function __construct() {
        if(!isset($_SESSION['user_id'])) {
            $this->redirect('auth');
        }
        $this->rentalModel = $this->model('Rental');
        $this->lensModel = $this->model('Lens');
        $this->fineModel = $this->model('Fine');
    }

    public function index() {
        try {
            $activeRentals = $this->rentalModel->getActiveRentalsByUser($_SESSION['user_id']);
            if($activeRentals === false) {
                throw new Exception('Failed to fetch active rentals');
            }
            $this->view('return/return_form', ['rentals' => $activeRentals]);
        } catch (Exception $e) {
            error_log("Error in return index: " . $e->getMessage());
            $this->view('return/return_form', [
                'error' => 'Failed to load active rentals. Please try again.',
                'rentals' => []
            ]);
        }
    }

    public function process() {
        try {
            if($_SERVER['REQUEST_METHOD'] == 'POST') {
                $validator = new Validator($_POST);
                $rules = [
                    'rental_id' => 'required|numeric'
                ];

                if($validator->validate($rules)) {
                    $rental_id = filter_var($_POST['rental_id'], FILTER_SANITIZE_NUMBER_INT);
                    $rental = $this->rentalModel->getRentalById($rental_id);
                    
                    if(!$rental) {
                        throw new Exception('Invalid rental');
                    }

                    if($rental->user_id != $_SESSION['user_id']) {
                        throw new Exception('Unauthorized access to rental');
                    }

                    if($rental->status !== 'active') {
                        throw new Exception('Rental is already returned');
                    }

                    // Calculate fine if late
                    $fine_amount = $this->calculateFine($rental->return_date);
                    
                    // Start transaction
                    $this->db->beginTransaction();

                    try {
                        // Update rental status
                        if(!$this->rentalModel->updateRentalStatus($rental_id, 'returned')) {
                            throw new Exception('Failed to update rental status');
                        }
                        
                        // Update lens status
                        if(!$this->lensModel->updateLensStatus($rental->lens_id, 'available')) {
                            throw new Exception('Failed to update lens status');
                        }
                        
                        // Create fine if applicable
                        if($fine_amount > 0) {
                            $fine_data = [
                                'rental_id' => $rental_id,
                                'amount' => $fine_amount,
                                'status' => 'pending'
                            ];
                            
                            if(!$this->fineModel->createFine($fine_data)) {
                                throw new Exception('Failed to create fine record');
                            }
                        }

                        $this->db->commit();
                        
                        // Log successful return
                        error_log("User {$_SESSION['user_email']} returned rental ID {$rental_id} at " . date('Y-m-d H:i:s'));
                        
                        $this->view('return/success', ['fine_amount' => $fine_amount]);
                    } catch (Exception $e) {
                        $this->db->rollBack();
                        throw $e;
                    }
                } else {
                    $this->view('return/return_form', [
                        'errors' => $validator->getErrors(),
                        'rentals' => $this->rentalModel->getActiveRentalsByUser($_SESSION['user_id'])
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log("Error in return process: " . $e->getMessage());
            $this->view('return/return_form', [
                'error' => $e->getMessage(),
                'rentals' => $this->rentalModel->getActiveRentalsByUser($_SESSION['user_id'])
            ]);
        }
    }

    private function calculateFine($expected_return_date) {
        try {
            $today = new DateTime();
            $return_date = new DateTime($expected_return_date);
            
            if($today > $return_date) {
                $diff = $today->diff($return_date);
                return $diff->days * 50000; // Rp 50,000 per day late
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Error calculating fine: " . $e->getMessage());
            return 0;
        }
    }

    public function success() {
        $this->view('return/success');
    }
} 