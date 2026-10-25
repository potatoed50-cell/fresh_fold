
<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add a new laundry service.
    if ($action === 'add_service') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? '';
        $unit = trim($_POST['price_unit'] ?? 'load');

        if (
            $name === '' ||
            strlen($name) > 100 ||
            !is_numeric($price) ||
            !is_finite((float) $price) ||
            (float) $price < 0 ||
            (float) $price > 99999999
        ) {
            $error = 'Enter a valid service name and price.';
        } elseif (!in_array($unit, ['load', 'item'], true)) {
            $error = 'Choose a valid price unit.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO services
                 (name, description, price, price_unit)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $description, $price, $unit]);
            $message = 'Service added successfully.';
        }
    }

    // Show or hide a service.
    elseif ($action === 'toggle_service') {
        $id = (int) ($_POST['service_id'] ?? 0);

        $stmt = $pdo->prepare(
            'UPDATE services
             SET is_active = IF(is_active = 1, 0, 1)
             WHERE id = ?'
        );
        $stmt->execute([$id]);
        $message = 'Service status updated.';
    }

    // Delete an inquiry.
    elseif ($action === 'delete_inquiry') {
        $id = (int) ($_POST['inquiry_id'] ?? 0);

        $stmt = $pdo->prepare(
            'DELETE FROM inquiries WHERE id = ?'
        );
        $stmt->execute([$id]);
        $message = 'Inquiry deleted.';
    }

    // Update an order status.
    elseif ($action === 'update_order') {
        $orderId = filter_var(
            $_POST['order_id'] ?? '',
            FILTER_VALIDATE_INT
        );
        $newStatus = $_POST['status'] ?? '';

        $allowedStatuses = [
            'Pending',
            'Processing',
            'Completed',
            'Cancelled'
        ];

        if (
            $orderId === false ||
            $orderId === null ||
            $orderId < 1 ||
            !in_array($newStatus, $allowedStatuses, true)
        ) {
            $error = 'Invalid order or status.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE orders SET status = ? WHERE id = ?'
            );
            $stmt->execute([$newStatus, $orderId]);

            $check = $pdo->prepare(
                'SELECT status FROM orders WHERE id = ?'
            );
            $check->execute([$orderId]);
            $currentOrder = $check->fetch();

            if (!$currentOrder) {
                $error = 'Order not found.';
            } elseif ($currentOrder['status'] === $newStatus) {
                $message = 'Order #' . $orderId .
                    ' status is ' . $newStatus . '.';
            }
        }
    }
}

// Load services.
$services = $pdo->query(
    'SELECT * FROM services ORDER BY sort_order, id'
)->fetchAll();

// Load the latest 50 inquiries.
$inquiries = $pdo->query(
    'SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 50'
)->fetchAll();

// Load the latest 200 orders.
$orders = $pdo->query(
    'SELECT
        o.id,
        o.customer_name,
        o.phone,
        o.quantity,
        o.total_price,
        o.status,
        o.created_at,
        s.name AS service_name,
        s.price_unit
     FROM orders o
     INNER JOIN services s ON o.service_id = s.id
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT 200'
)->fetchAll();

// Count orders by status.
$orderCounts = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Processing'), 0) AS processing,
        COALESCE(SUM(status = 'Completed'), 0) AS completed,
        COALESCE(SUM(status = 'Cancelled'), 0) AS cancelled
     FROM orders"
)->fetch();

function h($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | FreshFold Laundry</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="dashboard">

    <header>
        <h1>FreshFold Laundry</h1>
        <p>
            Welcome,
            <?= h($_SESSION['admin_username'] ?? 'Admin') ?>!
        </p>
        <a href="../index.php">View Homepage</a> |
        <a href="logout.php">Log Out</a>
    </header>

    <?php if ($message !== ''): ?>
        <p class="success"><?= h($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <hr>

    <!-- ORDER MANAGEMENT -->
    <h2>Manage Customer Orders</h2>

    <p>
        Total orders: <strong><?= (int) $orderCounts['total'] ?></strong> |
        Pending: <strong><?= (int) $orderCounts['pending'] ?></strong> |
        Processing: <strong><?= (int) $orderCounts['processing'] ?></strong> |
        Completed: <strong><?= (int) $orderCounts['completed'] ?></strong> |
        Cancelled: <strong><?= (int) $orderCounts['cancelled'] ?></strong>
    </p>

    <?php if (!$orders): ?>
        <p>No customer orders yet.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <section class="contact-card">
                <h3>Order #<?= (int) $order['id'] ?></h3>

                <p>
                    <strong>Customer:</strong>
                    <?= h($order['customer_name']) ?>
                </p>

                <p>
                    <strong>Phone:</strong>
                    <?= h($order['phone']) ?>
                </p>

                <p>
                    <strong>Service:</strong>
                    <?= h($order['service_name']) ?>
                </p>

                <p>
                    <strong>Quantity:</strong>
                    <?= (int) $order['quantity'] ?>
                    <?= h($order['price_unit']) ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    ₱<?= number_format((float) $order['total_price'], 2) ?>
                </p>

                <p>
                    <strong>Date ordered:</strong>
                    <?= h($order['created_at']) ?>
                </p>

                <p>
                    <strong>Current status:</strong>
                    <?= h($order['status']) ?>
                </p>

                <form method="post">
                    <input
                        type="hidden"
                        name="action"
                        value="update_order"
                    >

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= (int) $order['id'] ?>"
                    >

                    <label for="status-<?= (int) $order['id'] ?>">
                        Update Status
                    </label>

                    <select
                        id="status-<?= (int) $order['id'] ?>"
                        name="status"
                        required
                    >
                        <?php foreach (
                            ['Pending', 'Processing', 'Completed', 'Cancelled']
                            as $status
                        ): ?>
                            <option
                                value="<?= h($status) ?>"
                                <?= $order['status'] === $status
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= h($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Update Order</button>
                </form>
            </section>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- ADD SERVICE -->
    <h2>Add Laundry Service</h2>

    <form method="post">
        <input type="hidden" name="action" value="add_service">

        <label for="name">Service Name</label>
        <input id="name" name="name" maxlength="100" required>

        <label for="description">Description</label>
        <textarea id="description" name="description"></textarea>

        <label for="price">Price (₱)</label>
        <input
            id="price"
            type="number"
            name="price"
            min="0"
            max="99999999"
            step="0.01"
            required
        >

        <label for="price_unit">Price Unit</label>
        <select id="price_unit" name="price_unit">
            <option value="load">Per load</option>
            <option value="item">Per item</option>
        </select>

        <button type="submit">Add Service</button>
    </form>

    <!-- MANAGE SERVICES -->
    <h2>Manage Services</h2>

    <?php if (!$services): ?>
        <p>No services found.</p>
    <?php else: ?>
        <?php foreach ($services as $service): ?>
            <section>
                <h3><?= h($service['name']) ?></h3>

                <p>
                    ₱<?= number_format((float) $service['price'], 2) ?>
                    per <?= h($service['price_unit']) ?>
                </p>

                <p>
                    Status:
                    <?= $service['is_active'] ? 'Active' : 'Hidden' ?>
                </p>

                <form method="post">
                    <input
                        type="hidden"
                        name="action"
                        value="toggle_service"
                    >
                    <input
                        type="hidden"
                        name="service_id"
                        value="<?= (int) $service['id'] ?>"
                    >
                    <button type="submit">
                        <?= $service['is_active']
                            ? 'Hide Service'
                            : 'Activate Service' ?>
                    </button>
                </form>
            </section>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- CUSTOMER INQUIRIES -->
    <h2>Customer Inquiries</h2>

    <?php if (!$inquiries): ?>
        <p>No customer inquiries yet.</p>
    <?php else: ?>
        <?php foreach ($inquiries as $inquiry): ?>
            <section>
                <h3><?= h($inquiry['name']) ?></h3>

                <p>Phone: <?= h($inquiry['phone']) ?></p>

                <p><?= nl2br(h($inquiry['message'])) ?></p>

                <small><?= h($inquiry['created_at']) ?></small>

                <form
                    method="post"
                    onsubmit="return confirm('Delete this inquiry?');"
                >
                    <input
                        type="hidden"
                        name="action"
                        value="delete_inquiry"
                    >
                    <input
                        type="hidden"
                        name="inquiry_id"
                        value="<?= (int) $inquiry['id'] ?>"
                    >
                    <button type="submit">Delete Inquiry</button>
                </form>
            </section>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>

</main>
</body>
</html>
