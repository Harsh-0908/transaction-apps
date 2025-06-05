<?php
require_once '../config/db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM Transaction WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: users.php?msg=Transaction+deleted');
    exit;
} else {
    header('Location: users.php?msg=Invalid+transaction+ID');
    exit;
} 