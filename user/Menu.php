<?php
header("Content-Type: application/json");
require_once dirname(__DIR__) . '/includes/Database.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$menuData = [];

// Fetch categories
$catResult = $conn->query("SELECT * FROM categories ORDER BY id");
$categories = $catResult->fetch_all(MYSQLI_ASSOC);

foreach ($categories as $cat) {
    // Fetch menu items for each category
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category = ?");
    $stmt->bind_param("i", $cat['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $meals = $res->fetch_all(MYSQLI_ASSOC);

    // Format meals
    $formattedMeals = [];
    foreach ($meals as $meal) {
        $formattedMeals[] = [
            "name" => $meal["name"],
            "description" => $meal["description"],
            "price" => $meal["price_MWK"],
            "image" => "uploads/" . $meal["image"]
        ];
    }

    $menuData[] = [
        "category" => $cat["category_name"],
        "meals" => $formattedMeals
    ];
}

echo json_encode($menuData);
?>
