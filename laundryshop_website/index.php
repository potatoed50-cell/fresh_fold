
<?php
require __DIR__ . '/includes/db.php';

$success = '';
$error = '';
$orderSuccess = '';
$orderError = '';

// Process submitted forms.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    // CUSTOMER INQUIRY FORM
    if ($formType === 'inquiry') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $phone === '' || $message === '') {
            $error = 'Please complete all fields.';
        } elseif (
            strlen($name) > 100 ||
            strlen($phone) > 30 ||
            strlen($message) > 5000
        ) {
            $error = 'Some fields exceed the allowed length.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO inquiries (name, phone, message)
                 VALUES (?, ?, ?)'
            );

            $stmt->execute([$name, $phone, $message]);
            $success = 'Thank you! Your inquiry has been sent.';
        }
    }

    // CUSTOMER LAUNDRY ORDER FORM
    elseif ($formType === 'order') {
        $customerName = trim($_POST['customer_name'] ?? '');
        $orderPhone = trim($_POST['order_phone'] ?? '');
        $serviceId = filter_var(
            $_POST['service_id'] ?? '',
            FILTER_VALIDATE_INT
        );
        $quantity = filter_var(
            $_POST['quantity'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (
            $customerName === '' ||
            $orderPhone === '' ||
            $serviceId === false ||
            $serviceId === null ||
            $quantity === false ||
            $quantity === null
        ) {
            $orderError = 'Please complete all order fields correctly.';
        } elseif (
            strlen($customerName) > 100 ||
            strlen($orderPhone) > 30
        ) {
            $orderError = 'Your name or phone number is too long.';
        } elseif ($serviceId < 1 || $quantity < 1 || $quantity > 100) {
            $orderError = 'Please select a valid service and quantity (1–100).';
        } else {
            // Get the service price directly from the database.
            $stmt = $pdo->prepare(
                'SELECT id, name, price, price_unit
                 FROM services
                 WHERE id = ? AND is_active = 1'
            );

            $stmt->execute([$serviceId]);
            $service = $stmt->fetch();

            if (!$service) {
                $orderError = 'That service is unavailable. Please choose another.';
            } else {
                // Calculate the total using the database price.
                $totalPrice = (float) $service['price'] * $quantity;

                // Save the order with the default Pending status.
                $stmt = $pdo->prepare(
                    'INSERT INTO orders
                     (customer_name, phone, service_id, quantity, total_price)
                     VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $customerName,
                    $orderPhone,
                    $serviceId,
                    $quantity,
                    $totalPrice
                ]);

                $orderNumber = $pdo->lastInsertId();

                $orderSuccess =
                    'Order submitted successfully! Your order number is #' .
                    $orderNumber .
                    '. Total: ₱' .
                    number_format($totalPrice, 2) .
                    '. Status: Pending.';
            }
        }
    }
}

// Load active services for display and ordering.
$stmt = $pdo->query(
    'SELECT id, name, description, price, price_unit
     FROM services
     WHERE is_active = 1
     ORDER BY sort_order, id'
);

$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FreshFold Laundry Shop</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="index.php">FreshFold Laundry</a>

        <nav>
            <a href="#services">Services</a>
            <a href="#order">Place an Order</a>
            <a href="track_order.php">Track Order</a>
            <a href="#contact">Contact Us</a>
            <a href="admin/login.php">Admin</a>
        </nav>
    </div>
</header>

<main>
    <!-- HERO SECTION -->
    <section class="hero">
        <div class="container">
            <p class="eyebrow">YOUR CLOTHES, OUR CARE</p>
            <h1>Fresh clothes.<br>Fresh start.</h1>
            <p>
                Convenient and reliable laundry services
                to help make your everyday life easier.
            </p>
            <a class="button" href="#services">Explore Our Services</a>
        </div>
    </section>

    <!-- LAUNDRY SERVICES SECTION -->
    <section class="section container" id="services">
        <p class="eyebrow">WHAT WE OFFER</p>
        <h2>Our Laundry Services</h2>
        <p>Choose the service that works best for you.</p>

        <div class="service-grid">
            <?php if (!$services): ?>
                <p>Our services will be listed here soon.</p>
            <?php endif; ?>

            <?php foreach ($services as $service): ?>
                <article class="service-card">
                    <h3>
                        <?= htmlspecialchars(
                            $service['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <p>
                        <?= nl2br(htmlspecialchars(
                            $service['description'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        )) ?>
                    </p>

                    <p class="price">
                        ₱<?= number_format(
                            (float) $service['price'],
                            2
                        ) ?>
                        <span>
                            / <?= htmlspecialchars(
                                $service['price_unit'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CUSTOMER ORDER SECTION -->
    <section class="section contact-section" id="order">
        <div class="container contact-layout">
            <div>
                <p class="eyebrow">ORDER YOUR LAUNDRY</p>
                <h2>Ready for fresh clothes?</h2>
                <p>
                    Choose a laundry service, provide your contact
                    details, and submit your order.
                </p>
                <p>
                    <strong>How it works:</strong>
                    Your order starts as Pending. Contact the shop
                    if you need to confirm availability or timing.
                </p>
            </div>

            <div class="contact-card">
                <h3>Place a Laundry Order</h3>

                <?php if ($orderSuccess !== ''): ?>
                    <p class="success">
                        <?= htmlspecialchars(
                            $orderSuccess,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if ($orderError !== ''): ?>
                    <p class="error">
                        <?= htmlspecialchars(
                            $orderError,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if ($services): ?>
                    <form method="post" action="#order">
                        <input
                            type="hidden"
                            name="form_type"
                            value="order"
                        >

                        <label for="customer_name">Full Name</label>
                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >

                        <label for="order_phone">Phone Number</label>
                        <input
                            type="tel"
                            id="order_phone"
                            name="order_phone"
                            maxlength="30"
                            autocomplete="tel"
                            required
                        >

                        <label for="service_id">Choose a Service</label>
                        <select
                            id="service_id"
                            name="service_id"
                            required
                        >
                            <option value="">Select a service</option>

                            <?php foreach ($services as $service): ?>
                                <option
                                    value="<?= (int) $service['id'] ?>"
                                >
                                    <?= htmlspecialchars(
                                        $service['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                    — ₱<?= number_format(
                                        (float) $service['price'],
                                        2
                                    ) ?>
                                    / <?= htmlspecialchars(
                                        $service['price_unit'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="quantity">Quantity</label>
                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            min="1"
                            max="100"
                            step="1"
                            value="1"
                            required
                        >

                        <button type="submit">Submit Order</button>
                    </form>
                <?php else: ?>
                    <p>There are currently no available services to order.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section class="section contact-section" id="contact">
        <div class="container contact-layout">
            <div>
                <p class="eyebrow">GET IN TOUCH</p>
                <h2>Have a question?</h2>
                <p>
                    Send us an inquiry and our laundry shop
                    can get back to you.
                </p>
                <p><strong>Address:</strong> Graceville IV, City of San Jose del Monte, Bulacan</p>
                <p><strong>Phone:</strong> 09123456789</p>
                <p><strong>Hours:</strong> 10:00 AM - 10:00 PM</p>
            </div>

            <div class="contact-card">
                <h3>Send an Inquiry</h3>

                <?php if ($success !== ''): ?>
                    <p class="success">
                        <?= htmlspecialchars(
                            $success,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <p class="error">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                <?php endif; ?>

                <form method="post" action="#contact">
                    <input
                        type="hidden"
                        name="form_type"
                        value="inquiry"
                    >

                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="100"
                        required
                    >

                    <label for="phone">Phone Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        maxlength="30"
                        required
                    >

                    <label for="message">Your Message</label>
                    <textarea
                        id="message"
                        name="message"
                        rows="5"
                        maxlength="5000"
                        required
                    ></textarea>

                    <button type="submit">Send Inquiry</button>
                </form>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> FreshFold Laundry Shop</p>
    </div>
</footer>

</body>
</html>
