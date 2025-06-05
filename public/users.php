<?php
require_once '../config/db.php';

// Count total and active users
$total_users = 0;
$active_users = 0;

$total_result = $conn->query("SELECT COUNT(*) as total FROM user");
if ($total_result) {
    $total_row = $total_result->fetch_assoc();
    $total_users = $total_row['total'];
}



$active_result = $conn->query("SELECT COUNT(*) as active FROM user WHERE status = 'active'");
if ($active_result) {
    $active_row = $active_result->fetch_assoc();
    $active_users = $active_row['active'];
}

$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

$user_query = $conn->query("SELECT * FROM User ORDER BY firstname, lastname");
while ($user = $user_query->fetch_assoc()) {
    $stmt = $conn->prepare("SELECT SUM(amount) as paid_sum FROM Transaction WHERE user_id = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $stmt->bind_result($paid_sum);
    $stmt->fetch();
    $stmt->close();
    $paid_sum = $paid_sum ? $paid_sum : 0;

    $total_confirmed_amount = floatval($user['total_confirmed_amount']);
    $deposit = floatval($user['deposit']);
    $paid_total = $deposit + $paid_sum;
    $due = $total_confirmed_amount - $paid_total;

    $currency = htmlspecialchars($user['currency']) ?: '₹'; // default fallback

    $users[] = [
        'id' => $user['id'],
        'name' => $user['firstname'] . ' ' . $user['lastname'],
        'user_id' => $user['id'],
        'introduction' => mb_strimwidth($user['introduction'], 0, 50, '...'),
        'paid' => $paid_total,
        'due' => $due,
        'total_confirmed_amount' => $total_confirmed_amount,
        'currency' => $currency,
        'status' => $user['status'],
    ];
}

// Example variables (replace with your actual logic)
$start = ($page - 1) * $per_page + 1;
$end = min($start + $per_page - 1, $total_users);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Table</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 24px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .header-bar .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e2e8f0;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .header-bar input[type="search"] {
            background: transparent;
            border: none;
            outline: none;
            font-size: 1rem;
            color: #1a202c;
            width: 180px;
        }

        .add-customer-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
        }

        .add-customer-btn:hover {
            background: #1d4ed8;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            background: #e0f2fe;
            color: #0284c7;
            font-weight: 600;
        }

        .action-btn {
            border: none;
            background: none;
            cursor: pointer;
            margin: 0 4px;
            font-size: 1.1rem;
        }

        .action-btn.edit {
            color: #2563eb;
        }

        .action-btn.delete {
            color: #e11d48;
        }

        .action-btn.add {
            color: #16a34a;
        }

        table.dataTable tbody tr:hover {
            background-color: #f9fafb;
        }

        small {
            color: #6b7280;
        }

        .dataTables_filter {
            display: none !important;
        }

        .footer-container {
            width: 100%;
            border-top: 1px solid #e5e7eb;
            background: #fff;
            margin-top: 0;
            padding: 0;
        }

        .footer-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px 8px 16px;
            min-width: 350px;
        }

        .active-customers-summary {
            font-weight: 600;
            color: #64748b;
            font-size: 0.95rem;
        }

        .active-customers-summary strong {
            color: #1e293b;
        }

        .table-footer {
            display: flex;
            align-items: center;
            gap: 24px;
            color: #64748b;
            font-size: 0.98rem;
        }

        .rows-per-page select {
            border: none;
            background: transparent;
            font-size: 1rem;
            color: #64748b;
            margin-left: 4px;
            outline: none;
        }

        .pagination-info {
            min-width: 100px;
            text-align: center;
        }

        .pagination-arrows button {
            background: none;
            border: none;
            color: #64748b;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 2px 8px;
            transition: color 0.2s;
        }

        .pagination-arrows button:disabled {
            color: #cbd5e1;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header-bar">
            <div class="search-box">
                <i class="fa fa-filter text-blue-500"></i>
                <input type="search" id="customSearch" placeholder="Search...">
            </div>
            <div class="button-group">
                <button class="add-customer-btn" onclick="window.location.href='index.php'">
                    <i class="fa fa-plus"></i> Add Customer
                </button>
                <button class="add-customer-btn" style="margin-left: 10px;" onclick="window.location.href='transaction.php'">
                    <i class="fa fa-plus"></i> Add Transaction
                </button>
            </div>
        </div>

        <table id="userTable" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><input type="checkbox" class="row-select"></td>
                        <td>
                            <?= htmlspecialchars($user['name']) ?>
                            <br><small><?= $user['user_id'] ?></small>
                        </td>
                        <td><?= htmlspecialchars($user['introduction']) ?></td>
                        <td><?= number_format($user['paid'], 2) . ' ' . $user['currency'] ?></td>
                        <td style="color: <?= $user['due'] < 0 ? '#e11d48' : '#16a34a' ?>;">
                            <?= number_format($user['due'], 2) . ' ' . $user['currency'] ?>
                        </td>
                        <td><?= number_format($user['total_confirmed_amount']) . ' ' . $user['currency'] ?></td>
                        <td><span class="badge <?php echo $user['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $user['status']; ?>
                            </span></td>
                        <td>
                            <button class="action-btn edit" onclick="window.location.href='edit_user.php?id=<?= $user['id'] ?>'">
                                <i class="fa fa-pen"></i>
                            </button>
                            <button class="action-btn delete" onclick="if(confirm('Are you sure?')) window.location.href='delete_user.php?id=<?= $user['id'] ?>'">
                                <i class="fa fa-trash"></i>
                            </button>
                            <button class="action-btn add" onclick="window.location.href='transaction.php?user_id=<?= $user['id'] ?>'">
                                <i class="fa fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer-container">
            <div class="footer-flex">
                <div class="active-customers-summary">
                    ACTIVE CUSTOMERS: <strong><?= $active_users ?></strong>/<?= $total_users ?>
                </div>
                <div class="table-footer">
                    <div class="rows-per-page">
                        Rows per page:
                        <select onchange="location.href='?per_page='+this.value+'&page=1'">
                            <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>
                    <div class="pagination-info">
                        <?= $start ?>-<?= $end ?> of <?= $total_users ?>
                    </div>
                    <div class="pagination-arrows">
                        <button onclick="location.href='?per_page=<?= $per_page ?>&page=<?= max(1, $page - 1) ?>'" <?= $page == 1 ? 'disabled' : '' ?>>&#60;</button>
                        <button onclick="location.href='?per_page=<?= $per_page ?>&page=<?= min(ceil($total_users / $per_page), $page + 1) ?>'" <?= $end == $total_users ? 'disabled' : '' ?>>&#62;</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script>
            $(document).ready(function() {
                const table = $('#userTable').DataTable({
                    paging: false, // Disable pagination
                    responsive: false,
                    lengthChange: false,
                    info: false,
                });

                $('#customSearch').on('keyup', function() {
                    table.search(this.value).draw();
                });
            });
        </script>
</body>
</html>


