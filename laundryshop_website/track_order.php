
<?php
require __DIR__ . '/includes/db.php';

$order = null;
$error = '';
$searched = false;

$orderNumber = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;

    $orderNumber = trim($_POST['order_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validate the information entered by the customer.
    if (
        $orderNumber === '' ||
        !ctype_digit($orderNumber) ||
        $phone === ''
    ) {
        $error = 'Please enter a valid order number and phone number.';
    } elseif (strlen($phone) > 30 || strlen($orderNumber) > 10) {
        $error = 'Please check your order number and phone number.';
    } else {
        // The order number AND phone number must match.
        $stmt = $pdo->prepare(
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
             WHERE o.id = ? AND o.phone = ?
             LIMIT 1'
        );

        $stmt->execute([(int) $orderNumber, $phone]);
        $order = $stmt->fetch();

        if (!$order) {
            // Use the same message for either incorrect detail.
            $error = 'No matching order was found. Please check your order number and phone number.';
        }
    }
}

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
    <title>Track Your Order | FreshFold Laundry</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="index.php">FreshFold Laundry</a>

        <nav>
            <a href="index.php#services">Services</a>
            <a href="index.php#order">Place an Order</a>
            <a href="index.php#contact">Contact Us</a>
        </nav>
    </div>
</header>

<main class="section container">
    <p class="eyebrow">ORDER TRACKING</p>
    <h1>Track Your Laundry Order</h1>
    <p>
        Enter the order number from your confirmation and
        the phone number you used when placing your order.
    </p>

    <div class="contact-card">
        <h2>Find Your Order</h2>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="post" action="track_order.php">
            <label for="order_number">Order Number</label>
            <input
                type="number"
                id="order_number"
                name="order_number"
                min="1"
                max="2147483647"
                value="<?= h($orderNumber) ?>"
                required
            >

            <label for="phone">Phone Number</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                maxlength="30"
                value="<?= h($phone) ?>"
                autocomplete="tel"
                required
            >

            <button type="submit">Track Order</button>
        </form>
    </div>

    <?php if ($order): ?>
        <section class="contact-card" aria-live="polite">
            <h2>Order Details</h2>

            <p>
                <strong>Order Number:</strong>
                #<?= (int) $order['id'] ?>
            </p>

            <p>
                <strong>Customer:</strong>
                <?= h($order['customer_name']) ?>
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
                <strong>Total Price:</strong>
                ₱<?= number_format((float) $order['total_price'], 2) ?>
            </p>

            <p>
                <strong>Order Date:</strong>
                <?= h($order['created_at']) ?>
            </p>

            <p>
                <strong>Current Status:</strong>
                <strong><?= h($order['status']) ?></strong>
            </p>

            <?php if ($order['status'] === 'Pending'): ?>
                <p>Your order has been received and is awaiting processing.</p>
            <?php elseif ($order['status'] === 'Processing'): ?>
                <p>Your laundry order is currently being processed.</p>
            <?php elseif ($order['status'] === 'Completed'): ?>
                <p>Your laundry order is completed. Please contact the shop to arrange collection or delivery.</p>
            <?php elseif ($order['status'] === 'Cancelled'): ?>
                <p>This order has been cancelled. Please contact the shop if you have questions.</p>
            <?php endif; ?>
<form method="post" action="receipt.php">
    <input
        type="hidden"
        name="order_number"
        value="<?= htmlspecialchars(
            (string) ($_POST['order_number'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <input
        type="hidden"
        name="phone"
        value="<?= htmlspecialchars(
            (string) ($_POST['phone'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <button type="submit">Print Order Receipt</button>
</form>

            
        </section>
    <?php endif; ?>

    <p><a href="index.php">← Back to Homepage</a></p>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> FreshFold Laundry Shop</p>
    </div>
</footer>

</body>
</html>
