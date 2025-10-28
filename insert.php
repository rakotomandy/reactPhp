<?php
// Allow requests from any origin (CORS)
header("Access-Control-Allow-Origin: *");

// Specify allowed HTTP methods for CORS
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

// Allow the Content-Type header in requests
header("Access-Control-Allow-Headers: Content-Type");

// Set response type to JSON
header("Content-Type: application/json");

// --- Database connection settings ---
$host = "localhost";     // Database host
$dbname = "reactcrud";   // Database name
$username = "root";      // Database username (change if needed)
$password = "";          // Database password (change if needed)

try {
  // Create a new PDO instance for database connection
  $pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8",
    $username,
    $password
  );

  // Set error mode to exception to handle errors properly
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  // If connection fails, return JSON error message and stop script
  echo json_encode([
    "status" => "error",
    "message" => "Database connection failed: " . $e->getMessage()
  ]);
  exit;
}

// Get the HTTP request method (GET, POST, DELETE, etc.)
$method = $_SERVER["REQUEST_METHOD"];

// --- Handle preflight request for CORS ---
if ($method === "OPTIONS") {
  // Respond with 200 OK for preflight requests
  http_response_code(200);
  exit;
}

// --- GET → Fetch all users ---
if ($method === "GET") {
  // Prepare and execute SQL query to fetch all users ordered by newest first
  $stmt = $pdo->query("SELECT * FROM users ORDER BY idUsers DESC");

  // Fetch all results as an associative array
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Return the users as JSON
  echo json_encode($users);
  exit;
}

// --- POST → Insert a new user ---
if ($method === "POST") {
  // Read JSON data from request body
  $data = json_decode(file_get_contents("php://input"), true);

  // Validate that the name field exists and is not empty
  if (!isset($data["name"]) || trim($data["name"]) === "") {
    echo json_encode([
      "status" => "error",
      "message" => "Name field is required"
    ]);
    exit;
  }

  // Prepare SQL statement to insert a new user
  $stmt = $pdo->prepare("INSERT INTO users (name) VALUES (:name)");

  // Bind the name parameter to the value from JSON
  $stmt->bindParam(":name", $data["name"]);

  // Execute the insert statement
  $stmt->execute();

  // Return success message
  echo json_encode([
    "status" => "success",
    "message" => "User added successfully"
  ]);
  exit;
}

// --- DELETE → Delete the last inserted user ---
if ($method === "DELETE") {
  // Fetch the last user by ID
  $stmt = $pdo->query("SELECT idUsers FROM users ORDER BY idUsers DESC LIMIT 1");
  $last = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($last) {
    // Prepare statement to delete the last user
    $delete = $pdo->prepare("DELETE FROM users WHERE idUsers = :id");

    // Bind the ID parameter
    $delete->bindParam(":id", $last["idUsers"]);

    // Execute the delete statement
    $delete->execute();

    // Return success message
    echo json_encode([
      "status" => "success",
      "message" => "Last user deleted"
    ]);
  } else {
    // No users to delete
    echo json_encode([
      "status" => "error",
      "message" => "No users to delete"
    ]);
  }
  exit;
}

// --- Handle invalid request methods ---
echo json_encode([
  "status" => "error",
  "message" => "Invalid request method"
]);
exit;
