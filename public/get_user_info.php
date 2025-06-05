<?php
require_once '../config/db.php';
$user_id = intval($_GET['user_id'] ?? 0);
$response = [
    'success' => false,
    'currency' => '',
    'status' => '',
    'due_date' => '',
    'deal_amount' => 0,
    'deposit' => 0,
    'paid_sum' => 0,
];
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT currency, status, due_date, total_confirmed_amount, deposit FROM User WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($currency, $status, $due_date, $deal_amount, $deposit);
    if ($stmt->fetch()) {
        $response['currency'] = $currency;
        $response['status'] = $status;
        $response['due_date'] = $due_date;
        $response['deal_amount'] = floatval($deal_amount);
        $response['deposit'] = floatval($deposit);
        $response['success'] = true;
    }
    $stmt->close();
    $stmt2 = $conn->prepare("SELECT SUM(amount) FROM Transaction WHERE user_id = ?");
    $stmt2->bind_param('i', $user_id);
    $stmt2->execute();
    $stmt2->bind_result($paid_sum);
    $stmt2->fetch();
    $response['paid_sum'] = floatval($paid_sum);
    $stmt2->close();
}
header('Content-Type: application/json');
echo json_encode($response);          
 