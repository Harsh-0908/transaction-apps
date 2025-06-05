<?php
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { die('Invalid transaction ID.'); }

// Fetch transaction data
$stmt = $conn->prepare("SELECT * FROM Transaction WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$txn = $result->fetch_assoc();
$stmt->close();
if (!$txn) { die('Transaction not found.'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = trim($_POST['amount'] ?? '');
    $currency = trim($_POST['currency'] ?? '');
    $paymethod = trim($_POST['paymethod'] ?? '');
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if ($amount === '' || !is_numeric($amount)) $errors[] = 'Amount is required and must be a number.';
    if (!in_array($currency, ['INR', 'USD', 'AUD'])) $errors[] = 'Currency is required.';
    if (!in_array($paymethod, ['cash', 'cheque', 'online'])) $errors[] = 'Payment method is required.';
    if (!in_array($status, ['active', 'inactive'])) $errors[] = 'Status is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE Transaction SET amount=?, currency=?, paymethod=?, status=? WHERE id=?");
        $stmt->bind_param('dsssi', $amount, $currency, $paymethod, $status, $id);
        if ($stmt->execute()) {
            header('Location: users.php?msg=Transaction+updated+successfully');
            exit;
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Transaction</title>
    <style>body{font-family:sans-serif;background:#f8f9fa;}form{max-width:500px;margin:40px auto;background:#fff;padding:32px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}label{font-weight:500;display:block;margin-top:12px;}input,select{width:100%;padding:8px;margin-top:4px;margin-bottom:10px;border:1px solid #ccc;border-radius:4px;}button{background:#2563eb;color:#fff;padding:10px 32px;border:none;border-radius:4px;font-size:1.1rem;font-weight:600;cursor:pointer;}button:hover{background:#1741a6;}.error{color:#e74c3c;}</style>
</head>
<body>
<h2 style="text-align:center;">Edit Transaction</h2>
<?php if ($errors): ?><div class="error"><?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div><?php endif; ?>
<form method="post">
    <label>Amount *</label>
    <input type="number" name="amount" step="0.01" value="<?= htmlspecialchars($_POST['amount'] ?? $txn['amount']) ?>">
    <label>Currency *</label>
    <select name="currency">
        <option value="INR" <?= (($_POST['currency'] ?? $txn['currency']) === 'INR') ? 'selected' : '' ?>>INR</option>
        <option value="USD" <?= (($_POST['currency'] ?? $txn['currency']) === 'USD') ? 'selected' : '' ?>>USD</option>
        <option value="AUD" <?= (($_POST['currency'] ?? $txn['currency']) === 'AUD') ? 'selected' : '' ?>>AUD</option>
    </select>
    <label>Payment Method *</label>
    <select name="paymethod">
        <option value="cash" <?= (($_POST['paymethod'] ?? $txn['paymethod']) === 'cash') ? 'selected' : '' ?>>Cash</option>
        <option value="cheque" <?= (($_POST['paymethod'] ?? $txn['paymethod']) === 'cheque') ? 'selected' : '' ?>>Cheque</option>
        <option value="online" <?= (($_POST['paymethod'] ?? $txn['paymethod']) === 'online') ? 'selected' : '' ?>>Online</option>
    </select>
    <label>Status *</label>
    <select name="status">
        <option value="active" <?= (($_POST['status'] ?? $txn['status']) === 'active') ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= (($_POST['status'] ?? $txn['status']) === 'inactive') ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button type="submit">Update Transaction</button>
</form>
</body>
</html> 