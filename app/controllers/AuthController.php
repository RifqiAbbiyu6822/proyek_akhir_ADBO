<?php
class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        $this->userModel = $this->model('User');
    }

    public function index() {
        if(isset($_SESSION['user_id'])) {
            $this->redirect('rental');
        }
        $this->view('auth/login');
    }

    public function login() {
        try {
            if($_SERVER['REQUEST_METHOD'] == 'POST') {
                $validator = new Validator($_POST);
                $rules = [
                    'email' => 'required|email',
                    'password' => 'required|min:6'
                ];

                if($validator->validate($rules)) {
                    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                    $password = $_POST['password'];

                    $user = $this->userModel->findUserByEmail($email);

                    if($user && password_verify($password, $user->password)) {
                        $_SESSION['user_id'] = $user->id;
                        $_SESSION['user_email'] = $user->email;
                        $_SESSION['user_name'] = $user->name;
                        
                        // Log successful login
                        error_log("User {$user->email} logged in successfully at " . date('Y-m-d H:i:s'));
                        
                        $this->redirect('rental');
                    } else {
                        // Log failed login attempt
                        error_log("Failed login attempt for email: {$email} at " . date('Y-m-d H:i:s'));
                        
                        $this->view('auth/login', [
                            'error' => 'Invalid email or password',
                            'email' => $email
                        ]);
                    }
                } else {
                    $this->view('auth/login', [
                        'errors' => $validator->getErrors(),
                        'email' => $_POST['email']
                    ]);
                }
            } else {
                $this->view('auth/login');
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $this->view('auth/login', [
                'error' => 'An error occurred during login. Please try again.'
            ]);
        }
    }

    public function logout() {
        try {
            if(isset($_SESSION['user_email'])) {
                error_log("User {$_SESSION['user_email']} logged out at " . date('Y-m-d H:i:s'));
            }
            
            session_unset();
            session_destroy();
            
            $this->redirect('auth');
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            $this->redirect('auth');
        }
    }

    public function register() {
        try {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $validator = new Validator($_POST);
                $rules = [
                    'name' => 'required|min:3',
                    'email' => 'required|email',
                    'password' => 'required|min:6',
                    'confirm_password' => 'required|min:6'
                ];

                $isValid = $validator->validate($rules);
                $errors = $validator->getErrors();

                // Cek konfirmasi password
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $errors['confirm_password'][] = 'Konfirmasi password tidak cocok';
                    $isValid = false;
                }

                // Cek email sudah terdaftar
                if ($this->userModel->findUserByEmail($_POST['email'])) {
                    $errors['email'][] = 'Email sudah terdaftar';
                    $isValid = false;
                }

                if ($isValid) {
                    $data = [
                        'name' => $_POST['name'],
                        'email' => $_POST['email'],
                        'password' => $_POST['password']
                    ];
                    if ($this->userModel->register($data)) {
                        // Log registrasi
                        error_log("User {$data['email']} registered successfully at " . date('Y-m-d H:i:s'));
                        $this->redirect('auth/login');
                    } else {
                        $this->view('auth/register', [
                            'error' => 'Gagal mendaftar, silakan coba lagi',
                            'name' => $_POST['name'],
                            'email' => $_POST['email']
                        ]);
                    }
                } else {
                    $this->view('auth/register', [
                        'errors' => $errors,
                        'name' => $_POST['name'],
                        'email' => $_POST['email']
                    ]);
                }
            } else {
                $this->view('auth/register');
            }
        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            $this->view('auth/register', [
                'error' => 'Terjadi kesalahan saat registrasi. Silakan coba lagi.'
            ]);
        }
    }
} 