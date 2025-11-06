<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=hcc_asset_management", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $password = 'password123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $stmt->execute([$hash, 'mico.macapugay2004']);

    echo "Password updated successfully. Hash: " . $hash;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
