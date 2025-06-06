<?php
include 'db_conn.php';

if (!isset($_GET['id'])) {
    echo "No sale ID provided.";
    exit;
}

$sale_id = intval($_GET['id']);

// Fetch sale info
$saleQuery = "
    SELECT sales.*, users.name AS seller_name
    FROM sales
    LEFT JOIN users ON sales.seller = users.id
    WHERE sales.id = ?
";
$stmt = $conn->prepare($saleQuery);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$saleResult = $stmt->get_result();
$sale = $saleResult->fetch_assoc();
$stmt->close();

if (!$sale) {
    echo "Sale not found.";
    exit;
}

// Fetch sale items
$itemsQuery = "
    SELECT products.Description, sales_items.quantity, sales_items.price_at_sale
    FROM sales_items
    JOIN products ON products.id = sales_items.product_id
    WHERE sales_items.sale_id = ?
";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$itemsResult = $stmt->get_result();
$sale['cash_received'];
$sale['change_given'];
$display_number = isset($_GET['display']) ? intval($_GET['display']) : $sale['id'];

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Receipt #<?= $sale_id ?></title>
        <link rel="icon" type="image/x-icon" href="img/daraa.ico">
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
            .receipt { width: 300px; margin: auto; padding: 20px; border: 1px solid #ddd; }
            .receipt h2 { text-align: center; font-size: 24px; margin-bottom: 10px; }
            .receipt p { margin: 5px 0; }
            .items td { padding: 6px; text-align: right; }
            .items th { text-align: left; padding: 6px; border-bottom: 1px solid #000; }
            .items td:first-child { text-align: left; }
            .items td:last-child { text-align: right; }
            hr { border: 1px solid #ddd; margin: 15px 0; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="receipt">
            <h2>Sales Receipt</h2>
            <p><strong>Receipt #:</strong> <?= $display_number ?></p>
            <p><strong>Seller:</strong> <?= htmlspecialchars($sale['seller'] ?? 'Unknown Seller') ?></p>
            <p><strong>Date:</strong> <?= date("F j, Y", strtotime($sale['date'])) ?></p>

            <table class="items" width="100%">
                <tr><th>Item</th><th>Qty</th><th>Price</th></tr>
                <?php while ($item = $itemsResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['Description']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>₱<?= number_format(round($item['price_at_sale']), 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>

            <hr>
            <p><strong>Subtotal:</strong> <span style="float: right;">₱<?= number_format(round($sale['subtotal']), 2) ?></span></p>
            <p><strong>Markup:</strong> <span style="float: right;">₱<?= number_format(floor($sale['markup']), 2) ?></span></p>
            <hr>
            <p><strong>Total:</strong> <span style="float: right;"><strong>₱<?= number_format(floor($sale['total_cost']), 2) ?></strong></span></p>
            <p><strong>Cash Received:</strong> <span style="float: right;">₱<?= number_format(round($sale['cash_received']), 2) ?></span></p>
            <p><strong>Change:</strong> <span style="float: right;">₱<?= number_format(round($sale['change_given']), 2) ?></span></p>
        </div>
        <script>
            function printReceipt(saleId, displayNumber) {
                var printWindow = window.open('receipt.php?id=' + saleId + '&display=' + displayNumber, '_blank');
                printWindow.onload = function () {
                    printWindow.print();
                };
            }
        </script>
    </body>
</html>
