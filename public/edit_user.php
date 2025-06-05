<?php
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { die('Invalid user ID.'); }

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM User WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
if (!$user) { die('User not found.'); }

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $introduction = trim($_POST['introduction'] ?? '');
    $deposit = trim($_POST['deposit'] ?? '');
    $total_confirmed_amount = trim($_POST['total_confirmed_amount'] ?? '');
    $currency = trim($_POST['currency'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');

    if ($firstname === '') $errors[] = 'First name is required.';
    if ($lastname === '') $errors[] = 'Last name is required.';
    if ($introduction === '') $errors[] = 'Introduction is required.';
    if ($deposit === '' || !is_numeric($deposit)) $errors[] = 'Deposit is required and must be a number.';
    if ($total_confirmed_amount === '' || !is_numeric($total_confirmed_amount)) $errors[] = 'Deal Amount is required and must be a number.';
    if (!in_array($currency, ['INR', 'USD', 'AUD'])) $errors[] = 'Currency is required.';
    if (!in_array($status, ['active', 'inactive'])) $errors[] = 'Status is required.';
    if ($due_date === '') $errors[] = 'Due date is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE User SET firstname=?, lastname=?, introduction=?, deposit=?, total_confirmed_amount=?, currency=?, status=?, due_date=? WHERE id=?");
        $stmt->bind_param('sssddsssi', $firstname, $lastname, $introduction, $deposit, $total_confirmed_amount, $currency, $status, $due_date, $id);
        if ($stmt->execute()) {
            header('Location: users.php?msg=User+updated+successfully');
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
    <title>Edit User</title>
    <style>body{font-family:sans-serif;background:#f8f9fa;}form{max-width:500px;margin:40px auto;background:#fff;padding:32px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}label{font-weight:500;display:block;margin-top:12px;}input,select,textarea{width:100%;padding:8px;margin-top:4px;margin-bottom:10px;border:1px solid #ccc;border-radius:4px;}button{background:#2563eb;color:#fff;padding:10px 32px;border:none;border-radius:4px;font-size:1.1rem;font-weight:600;cursor:pointer;}button:hover{background:#1741a6;}.error{color:#e74c3c;}</style>
</head>
<body>
<h2 style="text-align:center;">Edit User</h2>
<?php if ($errors): ?><div class="error"><?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div><?php endif; ?>
<form method="post">
    <label>First Name *</label>
    <input type="text" name="firstname" value="<?= htmlspecialchars($_POST['firstname'] ?? $user['firstname']) ?>">
    <label>Last Name *</label>
    <input type="text" name="lastname" value="<?= htmlspecialchars($_POST['lastname'] ?? $user['lastname']) ?>">
    <label>Introduction *</label>
    <textarea name="introduction"><?= htmlspecialchars($_POST['introduction'] ?? $user['introduction']) ?></textarea>
    <label>Deposit *</label>
    <input type="number" name="deposit" step="0.01" value="<?= htmlspecialchars($_POST['deposit'] ?? $user['deposit']) ?>">
    <label>Deal Amount *</label>
    <input type="number" name="total_confirmed_amount" step="0.01" value="<?= htmlspecialchars($_POST['total_confirmed_amount'] ?? $user['total_confirmed_amount']) ?>">
    <label>Currency *</label>
    <select name="currency">
        <option value="INR" <?= (($_POST['currency'] ?? $user['currency']) === 'INR') ? 'selected' : '' ?>>INR</option>
        <option value="USD" <?= (($_POST['currency'] ?? $user['currency']) === 'USD') ? 'selected' : '' ?>>USD</option>
        <option value="AUD" <?= (($_POST['currency'] ?? $user['currency']) === 'AUD') ? 'selected' : '' ?>>AUD</option>
    </select>
    <label>Status *</label>
    <?php $currentStatus = $_POST['status'] ?? $user['status']; ?>
    <select name="status">
        <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <label>Due Date *</label>
    <input type="date" name="due_date" value="<?= htmlspecialchars($_POST['due_date'] ?? $user['due_date']) ?>">
    <button type="submit">Update User</button>
</form>
</body>
</html> 