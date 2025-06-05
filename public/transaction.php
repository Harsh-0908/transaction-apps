<?php
require_once '../config/db.php';

// Pre-select user if user_id is in URL
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Fetch users for dropdown
$user_options = [];
$user_query = $conn->query("SELECT id, firstname, lastname FROM User ORDER BY firstname, lastname");
while ($row = $user_query->fetch_assoc()) {
    $user_options[] = $row;
}

$errors = [];
$success = '';
$field_errors = [
    'user_id' => '',
    'amount' => '',
    'currency' => '',
    'paymethod' => '',
    'status' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate form data
    $user_id = trim($_POST['user_id'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $currency = trim($_POST['currency'] ?? '');
    $paymethod = trim($_POST['paymethod'] ?? '');
    $status = isset($_POST['status']) && $_POST['status'] === 'active' ? 'active' : 'inactive';
    $datetime = date('Y-m-d H:i:s');

    // Validation (server-side, similar to AJAX)
    if ($user_id === '' || !is_numeric($user_id)) $field_errors['user_id'] = 'User is required.';
    if ($amount === '' || !is_numeric($amount)) $field_errors['amount'] = 'Amount is required and must be a number.';
    if (!in_array($currency, ['INR', 'USD', 'AUD'])) $field_errors['currency'] = 'Currency is required.';
    if (!in_array($paymethod, ['cash', 'cheque', 'online'])) $field_errors['paymethod'] = 'Payment method is required.';
    if (!in_array($status, ['active', 'inactive'])) $field_errors['status'] = 'Status is required.';

    // Advanced business rules
    if ($user_id && is_numeric($user_id)) {
        $user_info = [];
        $stmt = $conn->prepare("SELECT currency, status, due_date, total_confirmed_amount, deposit FROM User WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($u_currency, $u_status, $u_due_date, $u_deal, $u_deposit);
        if ($stmt->fetch()) {
            $user_info = [
                'currency' => $u_currency,
                'status' => $u_status,
                'due_date' => $u_due_date,
                'deal_amount' => floatval($u_deal),
                'deposit' => floatval($u_deposit)
            ];
        }
        $stmt->close();
        $stmt2 = $conn->prepare("SELECT SUM(amount) as paid_sum FROM Transaction WHERE user_id = ?");
        $stmt2->bind_param('i', $user_id);
        $stmt2->execute();
        $stmt2->bind_result($paid_sum);
        $stmt2->fetch();
        $stmt2->close();
        $paid_sum = $paid_sum ? $paid_sum : 0;
        $paid_total = $user_info['deposit'] + $paid_sum;
        $due = $user_info['deal_amount'] - $paid_total;
        $now = date('Y-m-d');
        $penalty = 0;
        if ($now > $user_info['due_date']) {
            $penalty = 0.1 * $due;
            $due += $penalty;
        }
        if ($user_info['status'] !== 'active') {
            $field_errors['user_id'] = 'User is inactive. Cannot add transaction.';
        }
        if ($currency !== $user_info['currency']) {
            $field_errors['currency'] = 'Currency must match user currency.';
        }
        if ($amount > $due) {
            $field_errors['amount'] = 'Entered amount is more than due amount. Due: ' . number_format($due, 2) . ' ' . $user_info['currency'] . ($penalty ? ' (includes 10% penalty)' : '');
        }
    }

    if (!array_filter($field_errors)) {
        // Prepare and execute INSERT statement
        $stmt = $conn->prepare("INSERT INTO Transaction (user_id, amount, currency, status, paymethod, datetime) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('idssss', $user_id, $amount, $currency, $status, $paymethod, $datetime);
        if ($stmt->execute()) {
            header('Location: users.php?msg=Transaction+added+successfully');
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
    <title>Add New Transaction</title>
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
            padding: 32px;
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
        select:focus {
            border: 1.5px solid #3a7afe;
            outline: none;
            background: #fff;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.95rem;
            margin-bottom: 10px;
            margin-top: -2px;
        }

        .success {
            color: #27ae60;
            font-size: 1.1rem;
            margin-bottom: 18px;
        }

        .required {
            color: #e74c3c;
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
            margin-top: 8px;
        }

        .submit-btn:hover {
            background: #1741a6;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
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
            background-color: #2196F3;
        }

        input:checked+.slider::before {
            transform: translateX(26px);
        }

        .info-box {
            background: #f1f8ff;
            border: 1px solid #b6d4fe;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 12px;
            color: #2563eb;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Add New Transaction</h1>
        <div id="user-info" class="info-box" style="display:none;"></div>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post" action="transaction.php" id="txnForm" autocomplete="off">
            <label for="user_id">User:</label>
            <select id="user_id" name="user_id" <?= $selected_user_id ? 'disabled' : '' ?>>
                <option value="">-- Select User --</option>
                <?php foreach ($user_options as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= ($selected_user_id ? $selected_user_id : ($_POST['user_id'] ?? '')) == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($selected_user_id): ?><input type="hidden" name="user_id" value="<?= $selected_user_id ?>"><?php endif; ?>
            <?php if ($field_errors['user_id']): ?><div class="error-message" id="user_id_error"><?= htmlspecialchars($field_errors['user_id']) ?></div><?php else: ?><div class="error-message" id="user_id_error"></div><?php endif; ?>

            <label for="amount">Amount <span class="required">*</span></label>
            <input type="number" id="amount" name="amount" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
            <div class="error-message" id="amount_error"><?php if ($field_errors['amount']) echo htmlspecialchars($field_errors['amount']); ?></div>

            <label for="currency">Currency <span class="required">*</span></label>
            <select id="currency" name="currency">
                <option value="">-- Select Currency --</option>
                <option value="INR">INR</option>
                <option value="USD">USD</option>
                <option value="AUD">AUD</option>
            </select>
            <!-- Hidden input will be added by JS -->

            <label for="paymethod">Payment Method <span class="required">*</span></label>
            <select id="paymethod" name="paymethod">
                <option value="">-- Select Payment Method --</option>
                <option value="cash" <?= ($_POST['paymethod'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
                <option value="cheque" <?= ($_POST['paymethod'] ?? '') === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                <option value="online" <?= ($_POST['paymethod'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
            </select>
            <div class="error-message" id="paymethod_error"><?php if ($field_errors['paymethod']) echo htmlspecialchars($field_errors['paymethod']); ?></div>

            <label>Status <span class="required">*</span></label><br>
            <label class="switch">
                <input type="checkbox" name="status" value="active" <?= (isset($_POST['status']) && $_POST['status'] === 'active') ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
            <span class="switch-label"><?= (isset($_POST['status']) && $_POST['status'] === 'active') ? 'Active' : 'Inactive' ?></span>
            <div class="error-message" id="status_error"><?php if ($field_errors['status']) echo htmlspecialchars($field_errors['status']); ?></div>

            <br><br>

            <div class="form-actions">
                <button type="submit" class="submit-btn" id="submitBtn">Submit</button>
                <a href="users.php" class="back-home">
                    <i class="fa fa-home">Back to Home</i>
                </a>
            </div>
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

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
    let userInfo = null;

    function showUserInfo(info) {
        let html = '';
        if (!info) {
            $('#user-info').hide();
            return;
        }
        let paid = info.deposit + info.paid_sum;
        html += '<b>Deal Amount:</b> ' + info.deal_amount + ' ' + info.currency + '<br>';
        html += '<b>Deposit:</b> ' + info.deposit + ' ' + info.currency + '<br>';
        html += '<b>Paid:</b> ' + paid + ' ' + info.currency + '<br>';
        let due = info.deal_amount - paid;
        let penalty = 0;
        let today = new Date().toISOString().slice(0, 10);
        if (today > info.due_date) {
            penalty = 0.1 * due;
            due += penalty;
        }
        html += '<b>Due:</b> ' + due.toFixed(2) + ' ' + info.currency;
        if (penalty) html += ' <span style="color:#e67e22;">(includes 10% penalty)</span>';
        $('#user-info').html(html).show();
    }

    function validateAmount(amount) {
        if (!userInfo) return '';
        let due = userInfo.deal_amount - userInfo.deposit - userInfo.paid_sum;
        let penalty = 0;
        let today = new Date().toISOString().slice(0, 10);
        if (today > userInfo.due_date) {
            penalty = 0.1 * due;
            due += penalty;
        }
        if (parseFloat(amount) > due) {
            return 'Entered amount is more than due amount. Due: ' + due.toFixed(2) + ' ' + userInfo.currency + (penalty ? ' (includes 10% penalty)' : '');
        }
        return '';
    }

    function setCurrency(currency) {
        $('#currency').val(currency).prop('disabled', true);
        // Add or update hidden input
        if ($('#currency_hidden').length) {
            $('#currency_hidden').val(currency);
        } else {
            $('<input>').attr({
                type: 'hidden',
                id: 'currency_hidden',
                name: 'currency',
                value: currency
            }).appendTo('#txnForm');
        }
    }

    function setStatus(status) {
        if (status !== 'active') {
            $('#user_id_error').text('User is inactive. Cannot add transaction.');
            $('#submitBtn').prop('disabled', true);
        } else {
            $('#user_id_error').text('');
            $('#submitBtn').prop('disabled', false);
        }
    }
    $(function() {
        function fetchUserInfo(uid) {
            if (!uid) {
                userInfo = null;
                showUserInfo(null);
                setCurrency('');
                setStatus('active');
                return;
            }
            $.get('get_user_info.php', {
                user_id: uid
            }, function(data) {
                if (data.success) {
                    userInfo = data;
                    showUserInfo(data);
                    setCurrency(data.currency);
                    setStatus(data.status);
                } else {
                    userInfo = null;
                    showUserInfo(null);
                    setCurrency('');
                    setStatus('active');
                }
            }, 'json');
        }
        // On user select
        $('#user_id').on('change', function() {
            fetchUserInfo($(this).val());
        });
        // On page load, if user is pre-selected
        let preselected = $('#user_id').val();
        if (preselected) fetchUserInfo(preselected);
        // On amount input
        $('#amount').on('input', function() {
            let err = validateAmount(this.value);
            $('#amount_error').text(err);
            if (err) $('#submitBtn').prop('disabled', true);
            else if (userInfo && userInfo.status === 'active') $('#submitBtn').prop('disabled', false);
        });
    });
    // Status label toggle
    const statusCheckbox = document.querySelector('input[name="status"]');
    const statusLabel = document.querySelector('.switch-label');

    function updateStatusLabel() {
        statusLabel.textContent = statusCheckbox.checked ? 'active' : 'inactive';
    }
    if (statusCheckbox && statusLabel) {
        statusCheckbox.addEventListener('change', updateStatusLabel);
        updateStatusLabel();
    }
</script>

</html>