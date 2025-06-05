<?php
require_once '../config/db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    // Delete transactions
    $stmt = $conn->prepare("DELETE FROM Transaction WHERE user_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    // Delete user
    $stmt2 = $conn->prepare("DELETE FROM User WHERE id = ?");
    $stmt2->bind_param('i', $id);
    $stmt2->execute();
    $stmt2->close();
    header('Location: users.php?msg=User+and+transactions+deleted');
    exit;
} else {
    header('Location: users.php?msg=Invalid+user+ID');
    exit;
} 