<?php
// Include database connection
require_once '../config/db.php';

$errors = [];
$success = '';

$field_errors = [
    'firstname' => '',
    'lastname' => '',
    'introduction' => '',
    'deposit' => '',
    'total_confirmed_amount' => '',
    'currency' => '',
    'status' => '',
    'due_date' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $introduction = trim($_POST['introduction'] ?? '');
    $deposit = trim($_POST['deposit'] ?? '');
    $total_confirmed_amount = trim($_POST['total_confirmed_amount'] ?? '');
    $currency = trim($_POST['currency'] ?? '');
    // Set status: if checkbox is checked, it's 'active'; otherwise 'inactive'
    $status = isset($_POST['status']) && $_POST['status'] === 'active' ? 'active' : 'inactive';
    $due_date = trim($_POST['due_date'] ?? '');

    // Validation
    if ($firstname === '') $field_errors['firstname'] = 'First name is required.';
    if ($lastname === '') $field_errors['lastname'] = 'Last name is required.';
    if ($introduction === '') $field_errors['introduction'] = 'Introduction is required.';
    if ($deposit === '' || !is_numeric($deposit)) $field_errors['deposit'] = 'Deposit is required and must be a number.';
    if ($total_confirmed_amount === '' || !is_numeric($total_confirmed_amount)) $field_errors['total_confirmed_amount'] = 'Deal Amount is required and must be a number.';
    if (!in_array($currency, ['INR', 'USD', 'AUD'])) $field_errors['currency'] = 'Currency is required.';
    if (!in_array($status, ['active', 'inactive'])) $field_errors['status'] = 'Status is required.';
    if ($due_date === '') $field_errors['due_date'] = 'Due date is required.';

    // Insert to DB
    if (!array_filter($field_errors)) {
        $stmt = $conn->prepare("INSERT INTO User (firstname, lastname, introduction, deposit, total_confirmed_amount, currency, status, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'sssddsss', // FIXED: currency is now 's'
            $firstname,
            $lastname,
            $introduction,
            $deposit,
            $total_confirmed_amount,
            $currency,
            $status,
            $due_date
        );

        if ($stmt->execute()) {
            // Redirect to users.php after successful registration
            header("Location: users.php");
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
    <title>Add User</title>
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .container {
            max-width: 450px;
            margin: 40px auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            padding: 32px 32px 24px 32px;
        }

        .back-home {
            color: #2563eb;
            text-decoration: underline;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-home:hover {
            color: #1d4ed8;
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 28px;
            color: #3a3d4d;
        }

        label {
            font-weight: 500;
            color: #3a3d4d;
            margin-bottom: 4px;
            display: block;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7ef;
            border-radius: 4px;
            background: #f7fafd;
            margin-bottom: 6px;
            font-size: 1rem;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border: 1.5px solid #3a7afe;
            background: #fff;
            outline: none;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .success {
            color: #27ae60;
            font-size: 1.1rem;
            margin-bottom: 18px;
        }

        .status-group {
            margin-bottom: 18px;
        }

        .status-group label {
            display: inline-block;
            margin-right: 18px;
        }

        .submit-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 10px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #1741a6;
        }

        .required {
            color: #e74c3c;
        }

        /* === Toggle Switch for Status === */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            vertical-align: middle;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 24px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .slider::before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: rgb(0, 176, 32);
        }

        input:checked+.slider::before {
            transform: translateX(26px);
        }

        .switch-label {
            margin-left: 10px;
            font-weight: 500;
            display: inline-block;
            vertical-align: middle;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header-row">
            <h1>Add User</h1>
            <a href="users.php" class="back-home">
                <i class="fa fa-home"></i> Back to Home
            </a>
        </div>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label for="firstname">First Name <span class="required">*</span></label>
            <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>">
            <?php if ($field_errors['firstname']): ?><div class="error-message"><?= htmlspecialchars($field_errors['firstname']) ?></div><?php endif; ?>

            <label for="lastname">Last Name <span class="required">*</span></label>
            <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>">
            <?php if ($field_errors['lastname']): ?><div class="error-message"><?= htmlspecialchars($field_errors['lastname']) ?></div><?php endif; ?>

            <label for="introduction">Introduction <span class="required">*</span></label>
            <textarea id="introduction" name="introduction"><?= htmlspecialchars($_POST['introduction'] ?? '') ?></textarea>
            <?php if ($field_errors['introduction']): ?><div class="error-message"><?= htmlspecialchars($field_errors['introduction']) ?></div><?php endif; ?>

            <label for="deposit">Deposit <span class="required">*</span></label>
            <input type="number" step="0.01" id="deposit" name="deposit" value="<?= htmlspecialchars($_POST['deposit'] ?? '') ?>">
            <?php if ($field_errors['deposit']): ?><div class="error-message"><?= htmlspecialchars($field_errors['deposit']) ?></div><?php endif; ?>

            <label for="total_confirmed_amount">Deal Amount <span class="required">*</span></label>
            <input type="number" step="0.01" id="total_confirmed_amount" name="total_confirmed_amount" value="<?= htmlspecialchars($_POST['total_confirmed_amount'] ?? '') ?>">
            <?php if ($field_errors['total_confirmed_amount']): ?><div class="error-message"><?= htmlspecialchars($field_errors['total_confirmed_amount']) ?></div><?php endif; ?>

            <label for="currency">Currency <span class="required">*</span></label>
            <select id="currency" name="currency">
                <option value="">-- Select Currency --</option>
                <option value="INR" <?= ($_POST['currency'] ?? '') === 'INR' ? 'selected' : '' ?>>INR</option>
                <option value="USD" <?= ($_POST['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                <option value="AUD" <?= ($_POST['currency'] ?? '') === 'AUD' ? 'selected' : '' ?>>AUD</option>
            </select>
            <?php if ($field_errors['currency']): ?><div class="error-message"><?= htmlspecialchars($field_errors['currency']) ?></div><?php endif; ?>

            <label>Status <span class="required">*</span></label><br>
            <label class="switch">
                <input type="checkbox" name="status" value="active" <?= (isset($_POST['status']) && $_POST['status'] === 'active') ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
            <span class="switch-label"><?= (isset($_POST['status']) && $_POST['status'] === 'active') ? 'active' : 'inactive' ?></span>
            <div class="error-message" id="status_error"><?php if (!empty($field_errors['status'])) echo htmlspecialchars($field_errors['status']); ?></div>


            <label for="due_date">Due Date <span class="required">*</span></label>
            <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>">
            <?php if ($field_errors['due_date']): ?><div class="error-message"><?= htmlspecialchars($field_errors['due_date']) ?></div><?php endif; ?>

            <button type="submit" class="submit-btn">Submit</button>
        </form>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <?= htmlspecialchars($error) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

<script>
    const statusCheckbox = document.querySelector('input[name="status"]');
    const statusLabel = document.querySelector('.switch-label');

    function updateStatusLabel() {
        statusLabel.textContent = statusCheckbox.checked ? 'Active' : 'Inactive';
    }
    if (statusCheckbox && statusLabel) {
        statusCheckbox.addEventListener('change', updateStatusLabel);
        updateStatusLabel(); // Initialize on page load
    }
</script>

</html>





