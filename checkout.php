<?php
/*
 * CHECKOUT - customers only.
 *
 * This one line is the lock on the door: without a logged-in customer the
 * script stops immediately and sends the visitor to login.php.
 * So even if someone edits the HTML or calls checkout.php directly,
 * no order can be placed without logging in.
 */
require_once __DIR__ . '/require_user.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/products.php';

$pageTitle = 'Azulis | Checkout';

$errors = [];
$userId = (int) $_SESSION['user_id'];

/* ------------------------------------------------------------------
   A) ORDER CONFIRMATION
   After a successful checkout we redirect to checkout.php?order=12 and
   show a receipt. The user_id check keeps people out of other orders.
   ------------------------------------------------------------------ */
$confirmation = null;
$orderId = (int) ($_GET['order'] ?? 0);

if ($orderId > 0) {
    $stmt = $conn->prepare(
        'SELECT id, customer_name, phone, address, total, status, created_at
         FROM orders WHERE id = ? AND user_id = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        $itemStmt = $conn->prepare(
            'SELECT product_name, price, quantity FROM order_items WHERE order_id = ?'
        );
        $itemStmt->bind_param('i', $orderId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();

        $confirmation = ['order' => $order, 'items' => $items];
    }
}

/* ------------------------------------------------------------------
   B) REBUILD THE CART
   shop.php posts its cart as JSON: [{"id":4,"qty":2}, ...]
   We only trust the product id and quantity. The price always comes
   from products.php, so a tampered browser cart cannot change totals.
   ------------------------------------------------------------------ */
$cartLines = [];   // [product_id => quantity]

$postedCart = $_POST['cart'] ?? '';

if (is_string($postedCart) && $postedCart !== '') {
    $decoded = json_decode($postedCart, true);

    if (is_array($decoded)) {
        foreach ($decoded as $line) {
            $productId = (int) ($line['id'] ?? 0);
            $quantity  = (int) ($line['qty'] ?? 0);

            if (isset($products[$productId]) && $quantity > 0 && $quantity <= 99) {
                $cartLines[$productId] = ($cartLines[$productId] ?? 0) + $quantity;
            }
        }
    }
}

$cartTotal = 0.00;
foreach ($cartLines as $productId => $quantity) {
    $cartTotal += $products[$productId]['price'] * $quantity;
}

// The id + quantity list goes back into the hidden field of the form.
$cartForForm = [];
foreach ($cartLines as $productId => $quantity) {
    $cartForForm[] = ['id' => $productId, 'qty' => $quantity];
}
$cartJson = htmlspecialchars(json_encode($cartForForm), ENT_QUOTES, 'UTF-8');

/* ------------------------------------------------------------------
   C) SAVE THE ORDER
   ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
    $name    = trim($_POST['customer_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $errors[] = 'Please enter the name for delivery.';
    }

    if ($phone === '') {
        $errors[] = 'Please enter a contact number.';
    }

    if ($address === '') {
        $errors[] = 'Please enter the delivery address.';
    }

    if (!$cartLines) {
        $errors[] = 'Your shopping bag is empty.';
    }

    if (!$errors) {
        // An order touches two tables, so both writes succeed or neither does.
        $conn->begin_transaction();

        try {
            $total = $cartTotal;

            $stmt = $conn->prepare(
                'INSERT INTO orders (user_id, customer_name, phone, address, total)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('isssd', $userId, $name, $phone, $address, $total);
            $stmt->execute();
            $newOrderId = (int) $conn->insert_id;
            $stmt->close();

            $itemStmt = $conn->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($cartLines as $productId => $quantity) {
                $productName = $products[$productId]['name'];
                $price       = (float) $products[$productId]['price'];
                $itemStmt->bind_param('iisdi', $newOrderId, $productId, $productName, $price, $quantity);
                $itemStmt->execute();
            }

            $itemStmt->close();
            $conn->commit();

            // Redirect after saving so a page refresh cannot place the order twice.
            header('Location: checkout.php?order=' . $newOrderId);
            exit;
        } catch (Throwable $error) {
            $conn->rollback();
            $errors[] = 'Your order could not be saved. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="shop.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <header>
        <a href="Index.php" class="logo"><img src="Logo.png" alt="Azulis Logo"></a>
        <nav>
            <a href="Index.php">Home</a>
            <a href="shop.php" class="active">Products</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact</a>
        </nav>
        <div class="icons">
            <span class="user-greeting"><?= htmlspecialchars(current_user_name(), ENT_QUOTES, 'UTF-8') ?></span>
            <a href="logout.php" title="Log Out" aria-label="Log Out">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </header>

    <main class="checkout-main">

        <?php if ($confirmation): ?>

            <!-- ============ THANK YOU ============ -->
            <section class="checkout-card">
                <p class="eyebrow">Order Confirmed</p>
                <h1>Thank you for your order</h1>
                <p class="checkout-note">
                    Order #<?= (int) $confirmation['order']['id'] ?> was saved to your account.
                    Our team will contact you about delivery.
                </p>

                <table class="checkout-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($confirmation['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td>₱<?= number_format((float) $item['price'], 2) ?></td>
                                <td>₱<?= number_format((float) $item['price'] * (int) $item['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="checkout-total">
                    Total paid on delivery: <strong>₱<?= number_format((float) $confirmation['order']['total'], 2) ?></strong>
                </p>

                <p class="checkout-note">
                    Delivering to<br>
                    <strong><?= htmlspecialchars($confirmation['order']['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                    <?= nl2br(htmlspecialchars($confirmation['order']['address'], ENT_QUOTES, 'UTF-8')) ?><br>
                    <?= htmlspecialchars($confirmation['order']['phone'], ENT_QUOTES, 'UTF-8') ?>
                </p>

                <a href="shop.php" class="checkout-btn">Continue Shopping</a>
            </section>

            <script>
                // The order is safely stored in the database, so clear the browser cart.
                localStorage.removeItem('azulis_cart');
            </script>

        <?php elseif (!$cartLines): ?>

            <!-- ============ EMPTY CART ============ -->
            <section class="checkout-card">
                <p class="eyebrow">Checkout</p>
                <h1>Your bag is empty</h1>
                <p class="checkout-note">Add a bag to your cart first, then come back here to order.</p>
                <a href="shop.php" class="checkout-btn">Browse Bags</a>
            </section>

        <?php else: ?>

            <!-- ============ REVIEW + DELIVERY FORM ============ -->
            <section class="checkout-card">
                <p class="eyebrow">Checkout</p>
                <h1>Review Your Order</h1>
                <p class="checkout-note">
                    Logged in as <strong><?= htmlspecialchars(current_user_name(), ENT_QUOTES, 'UTF-8') ?></strong>
                    (<?= htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                </p>

                <?php foreach ($errors as $error): ?>
                    <div class="form-alert error">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endforeach; ?>

                <table class="checkout-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartLines as $productId => $quantity): ?>
                            <?php $product = $products[$productId]; ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $quantity ?></td>
                                <td>₱<?= number_format((float) $product['price'], 2) ?></td>
                                <td>₱<?= number_format((float) $product['price'] * $quantity, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="checkout-total">Total: <strong>₱<?= number_format($cartTotal, 2) ?></strong></p>

                <form class="checkout-form" method="post" action="checkout.php">
                    <input type="hidden" name="action" value="place_order">
                    <input type="hidden" name="cart" value="<?= $cartJson ?>">

                    <label for="customer_name">Full Name</label>
                    <input type="text" id="customer_name" name="customer_name"
                           value="<?= htmlspecialchars($_POST['customer_name'] ?? current_user_name(), ENT_QUOTES, 'UTF-8') ?>"
                           required>

                    <label for="phone">Contact Number</label>
                    <input type="text" id="phone" name="phone"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="09XX XXX XXXX" required>

                    <label for="address">Delivery Address</label>
                    <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

                    <button type="submit" class="checkout-btn">Place Order</button>
                </form>

                <p class="checkout-note">
                    <a href="shop.php" class="checkout-back">&larr; Back to shop</a>
                </p>
            </section>

        <?php endif; ?>

    </main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>
</html>
