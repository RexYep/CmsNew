<?php
require 'config/database.php';

$email = 'admin@cms.com'; // change to the test user's email
$stmt = $conn->prepare("SELECT user_id, email, password, role, status FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo '<pre>';
var_dump($user);
if ($user) {
    echo "password_verify('Admin@123', password) => ";
    echo password_verify('Admin@123', $user['password']) ? "TRUE\n" : "FALSE\n";
}
echo '</pre>';