header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../classes/Rental.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$rental = new Rental($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['user_id'])) {
            $stmt = $rental->getUserRentals($_GET['user_id']);
            $rentals = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rentals[] = $row;
            }
            echo json_encode($rentals);
        } else {
            echo json_encode(['error' => 'User ID required']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>