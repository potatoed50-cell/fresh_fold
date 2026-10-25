
<?php
// Connect to the existing FreshFold database.
require __DIR__ . '/includes/db.php';

function h($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$order = null;
$error = '';

// Only accept receipt requests submitted through POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'Please open your receipt from the Track Order page.';
} else {
    $orderNumber = filter_var(
        $_POST['order_number'] ?? '',
        FILTER_VALIDATE_INT
    );

    $phone = trim($_POST['phone'] ?? '');

    if (
        $orderNumber === false ||
        $orderNumber === null ||
        $orderNumber < 1 ||
        $phone === '' ||
        strlen($phone) > 30
    ) {
        $error = 'Please provide a valid order number and phone number.';
    } else {
        // Verify both details and retrieve the saved order.
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

        
        $stmt->execute([$orderNumber, $phone]);
        $order = $stmt->fetch();

        if (!$order) {
            $error = 'We could not find an order matching those details.';
        } elseif ($order['status'] !== 'Completed') {
            $order = null;
            $error = 'Your receipt is available only after your order is completed.';
        }

    }
}

// Friendly message based on the latest saved order status.
$statusMessage = '';

if ($order) {
    switch ($order['status']) {
        case 'Pending':
            $statusMessage = 'Your order is waiting to be processed.';
            break;
        case 'Processing':
            $statusMessage = 'Your laundry order is being processed.';
            break;
        case 'Completed':
            $statusMessage = 'Your laundry order is completed.';
            break;
        case 'Cancelled':
            $statusMessage = 'This order has been cancelled.';
            break;
        default:
            $statusMessage = 'Please contact the shop for an update.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Order Receipt | FreshFold Laundry</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            background: #f1f5f9;
            color: #1e293b;
            font-family: Arial, Helvetica, sans-serif;
        }

        .receipt {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            padding: 35px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(15, 23, 42, 0.08);
        }

        .receipt-header {
            padding-bottom: 22px;
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
        }

        .receipt-header h1 {
            margin: 0 0 8px;
            color: #0f766e;
            font-size: 30px;
        }

        .receipt-header p {
            margin: 5px 0;
            color: #64748b;
        }

        .receipt-title {
            margin: 25px 0;
            text-align: center;
            letter-spacing: 2px;
            font-size: 19px;
        }

        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 25px;
        }

        .info-label {
            display: block;
            margin-bottom: 5px;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
        }

        .info-value {
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .items {
            width: 100%;
            margin: 15px 0 20px;
            border-collapse: collapse;
        }

        .items th,
        .items td {
            padding: 13px 8px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .items th:last-child,
        .items td:last-child {
            text-align: right;
        }

        .items th {
            background: #f8fafc;
            font-size: 13px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 18px 0;
            border-top: 2px solid #0f766e;
            font-size: 20px;
            font-weight: bold;
        }

        .status {
            margin-top: 20px;
            padding: 15px;
            background: #f0fdfa;
            border-radius: 8px;
        }

        .status strong {
            color: #0f766e;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #cbd5e1;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 20px auto 0;
        }

        .button {
            display: inline-block;
            padding: 12px 18px;
            border: 0;
            border-radius: 6px;
            background: #0f766e;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .button.secondary {
            background: #475569;
        }

        .error {
            padding: 15px;
            background: #fef2f2;
            color: #b91c1c;
            border-radius: 8px;
            line-height: 1.5;
        }

        @media (max-width: 480px) {
            .receipt {
                padding: 22px;
            }

            .receipt-info {
                grid-template-columns: 1fr;
                gap: 14px;
            }
        }

        @media print {
            @page {
                size: auto;
                margin: 12mm;
            }

            body {
                padding: 0;
                background: #ffffff;
            }

            .receipt {
                max-width: none;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .actions {
                display: none !important;
            }

            .status {
                background: #ffffff;
                border: 1px solid #cbd5e1;
            }
        }
    </style>
</head>
<body>

<?php if (!$order): ?>

    <main class="receipt">
        <h1>FreshFold Laundry</h1>
        <p class="error"><?= h($error) ?></p>

        <div class="actions">
            <a class="button secondary" href="track_order.php">
                Back to Track Order
            </a>
        </div>
    </main>

<?php else: ?>

    <main class="receipt">

        <header class="receipt-header">
            <h1>FreshFold Laundry</h1>
            <p>Fresh clothes. Fresh start.</p>
            <p>Thank you for choosing our laundry service!</p>
        </header>

        <h2 class="receipt-title">ORDER RECEIPT</h2>

        <section class="receipt-info">
            <div>
                <span class="info-label">Order Number</span>
                <span class="info-value">
                    #<?= (int) $order['id'] ?>
                </span>
            </div>

            <div>
                <span class="info-label">Order Date</span>
                <span class="info-value">
                    <?= h(date(
                        'M d, Y h:i A',
                        strtotime($order['created_at'])
                    )) ?>
                </span>
            </div>

            <div>
                <span class="info-label">Customer Name</span>
                <span class="info-value">
                    <?= h($order['customer_name']) ?>
                </span>
            </div>

            <div>
                <span class="info-label">Phone Number</span>
                <span class="info-value">
                    <?= h($order['phone']) ?>
                </span>
            </div>
        </section>

        <table class="items">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>
                        <?= h($order['service_name']) ?>
                        <br>
                        <small>
                            Per <?= h($order['price_unit']) ?>
                        </small>
                    </td>

                    <td><?= (int) $order['quantity'] ?></td>

                    <td>
                        ₱<?= number_format(
                            (float) $order['total_price'],
                            2
                        ) ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="total-row">
            <span>Total Amount</span>
            <span>
                ₱<?= number_format(
                    (float) $order['total_price'],
                    2
                ) ?>
            </span>
        </div>

        <section class="status">
            <p>
                <strong>Order Status:</strong>
                <?= h($order['status']) ?>
            </p>

            <p><?= h($statusMessage) ?></p>
        </section>

        <footer class="footer">
            <p><strong>Thank you for your order!</strong></p>
            <p>Please keep this receipt for your records.</p>
            <p>This receipt confirms your order details. It does not
               necessarily confirm that payment has been made.</p>
        </footer>

        <div class="actions">
            <button
                type="button"
                class="button"
                onclick="window.print()"
            >
                Print / Save as PDF
            </button>

            <a class="button secondary" href="track_order.php">
                Track Another Order
            </a>
        </div>

    </main>

<?php endif; ?>

</body>
</html>
