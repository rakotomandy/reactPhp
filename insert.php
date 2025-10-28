<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Database connection
$host = "localhost";
$dbname = "reactcrud";
$username = "root"; // change if needed
$password = "";     // change if needed

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo json_encode(["status" => "error", "message" => "Database connection failed: " . $e->getMessage()]);
  exit;
}

$method = $_SERVER["REQUEST_METHOD"];

// Handle preflight request for CORS
if ($method === "OPTIONS") {
  http_response_code(200);
  exit;
}

// GET → Fetch all users
if ($method === "GET") {
  $stmt = $pdo->query("SELECT * FROM users ORDER BY idUsers DESC");
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($users);
  exit;
}

// POST → Insert new user
if ($method === "POST") {
  $data = json_decode(file_get_contents("php://input"), true);

  if (!isset($data["name"]) || trim($data["name"]) === "") {
    echo json_encode(["status" => "error", "message" => "Name field is required"]);
    exit;
  }

  $stmt = $pdo->prepare("INSERT INTO users (name) VALUES (:name)");
  $stmt->bindParam(":name", $data["name"]);
  $stmt->execute();

  echo json_encode(["status" => "success", "message" => "User added successfully"]);
  exit;
}

// DELETE → Delete the last inserted user
if ($method === "DELETE") {
  $stmt = $pdo->query("SELECT idUsers FROM users ORDER BY idUsers DESC LIMIT 1");
  $last = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($last) {
    $delete = $pdo->prepare("DELETE FROM users WHERE idUsers = :id");
    $delete->bindParam(":id", $last["idUsers"]);
    $delete->execute();
    echo json_encode(["status" => "success", "message" => "Last user deleted"]);
  } else {
    echo json_encode(["status" => "error", "message" => "No users to delete"]);
  }
  exit;
}

// Invalid method
echo json_encode(["status" => "error", "message" => "Invalid request method"]);
exit;
