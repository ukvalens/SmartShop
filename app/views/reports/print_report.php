<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../helpers/Language.php';

$lang = $_GET['lang'] ?? 'en';

$db = new Database();
$conn = $db->getConnection();

$report_type = $_GET['type'] ?? 'daily';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

// If no data found for specific customer/date, show all today's data
if ($report_type === 'customer' && $customer_id) {
    $check_data = $conn->query("SELECT COUNT(*) as count FROM sales WHERE customer_id = $customer_id AND DATE(sale_date) = '$selected_date'")->fetch_assoc();
    if ($check_data['count'] == 0) {
        $selected_date = date('Y-m-d'); // Use today's date
    }
}

if ($report_type === 'customer' && $customer_id) {
    // Customer specific report
    $customer_info = $conn->query("SELECT full_name FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
    if (!$customer_info) {
        $customer_info = ['full_name' => 'Customer #' . $customer_id];
    }
    
    $sales_data = $conn->query("
        SELECT s.sale_id, s.sale_date, s.total_amount, s.payment_method,
               GROUP_CONCAT(CONCAT(p.product_name, ' (', sd.quantity_sold, ')') SEPARATOR ', ') as product_name,
               SUM(sd.quantity_sold) as quantity,
               s.total_amount as unit_price,
               SUM(sd.quantity_sold * p.cost_price) as cost_price,
               s.total_amount as item_total,
               (s.total_amount - SUM(sd.quantity_sold * p.cost_price)) as item_profit
        FROM sales s
        LEFT JOIN sale_details sd ON s.sale_id = sd.sale_id
        LEFT JOIN products p ON sd.product_id = p.product_id
        WHERE s.customer_id = $customer_id AND DATE(s.sale_date) = '$selected_date'
        GROUP BY s.sale_id
        ORDER BY s.sale_date DESC
        LIMIT 20
    ");
} else {
    // Daily report for all customers
    $sales_data = $conn->query("
        SELECT s.sale_id, s.sale_date, s.total_amount, s.payment_method,
               COALESCE(c.full_name, 'Walk-in Customer') as customer_name,
               GROUP_CONCAT(CONCAT(p.product_name, ' (', sd.quantity_sold, ')') SEPARATOR ', ') as product_name,
               SUM(sd.quantity_sold) as quantity,
               s.total_amount as unit_price,
               SUM(sd.quantity_sold * p.cost_price) as cost_price,
               s.total_amount as item_total,
               (s.total_amount - SUM(sd.quantity_sold * p.cost_price)) as item_profit
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.customer_id
        LEFT JOIN sale_details sd ON s.sale_id = sd.sale_id
        LEFT JOIN products p ON sd.product_id = p.product_id
        WHERE DATE(s.sale_date) = '$selected_date'
        GROUP BY s.sale_id
        ORDER BY s.sale_date DESC
        LIMIT 20
    ");
}

// Get credit sales and collections for the date
$credit_stats = $conn->query("
    SELECT 
        COALESCE(SUM(CASE WHEN payment_method = 'Credit' THEN total_amount END), 0) as credit_sales,
        COUNT(CASE WHEN payment_method = 'Credit' THEN 1 END) as credit_transactions
    FROM sales 
    WHERE DATE(sale_date) = '$selected_date'
")->fetch_assoc();

$credit_collections = $conn->query("
    SELECT 
        COALESCE(SUM(amount), 0) as collections_amount,
        COUNT(*) as collections_count
    FROM customer_credits 
    WHERE status = 'paid' AND DATE(created_at) = '$selected_date'
")->fetch_assoc();

$total_revenue = 0;
$total_cost = 0;
$total_profit = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SmartSHOP - Trading Report</title>
    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            left: auto !important;
            z-index: 1000 !important;
        }
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company-name { font-size: 24px; font-weight: bold; color: #1a237e; }
        .report-title { font-size: 18px; margin: 10px 0; }
        .report-date { font-size: 14px; color: #666; }
        
        .summary-section { margin: 20px 0; padding: 15px; background: #f5f5f5; }
        .summary-row { display: flex; justify-content: space-between; margin: 5px 0; }
        .summary-label { font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; font-weight: bold; }
        .number { text-align: right; }
        .profit { color: green; font-weight: bold; }
        .loss { color: red; font-weight: bold; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">SMARTSHOP</div>
        <div class="report-title">
            <?php if ($report_type === 'customer' && $customer_id): ?>
                Customer Trading Report - <?php echo $customer_info['full_name']; ?>
            <?php else: ?>
                Daily Trading Report
            <?php endif; ?>
        </div>
        <div class="report-date">Date: <?php echo date('F d, Y', strtotime($selected_date)); ?></div>
        <div class="report-date">Generated: <?php echo date('F d, Y H:i:s'); ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Time</th>
                <?php if ($report_type !== 'customer'): ?>
                    <th>Customer</th>
                <?php endif; ?>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Cost Price</th>
                <th>Revenue</th>
                <th>Profit/Loss</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $has_data = false;
            while ($row = $sales_data->fetch_assoc()): 
                $has_data = true;
                $total_revenue += $row['item_total'];
                $total_cost += ($row['quantity'] * $row['cost_price']);
                $total_profit += $row['item_profit'];
            ?>
                <tr>
                    <td><?php echo date('H:i', strtotime($row['sale_date'])); ?></td>
                    <?php if ($report_type !== 'customer'): ?>
                        <td><?php echo $row['customer_name']; ?></td>
                    <?php endif; ?>
                    <td><?php echo $row['product_name']; ?></td>
                    <td class="number"><?php echo $row['quantity']; ?></td>
                    <td class="number"><?php echo number_format($row['unit_price']); ?> RWF</td>
                    <td class="number"><?php echo number_format($row['cost_price']); ?> RWF</td>
                    <td class="number"><?php echo number_format($row['item_total']); ?> RWF</td>
                    <td class="number <?php echo $row['item_profit'] >= 0 ? 'profit' : 'loss'; ?>">
                        <?php echo number_format($row['item_profit']); ?> RWF
                    </td>
                    <td><?php echo $row['payment_method']; ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$has_data): ?>
                <tr>
                    <td colspan="<?php echo ($report_type !== 'customer') ? '9' : '8'; ?>" style="text-align: center; padding: 2rem; color: #666;">
                        No sales data found for <?php echo date('F d, Y', strtotime($selected_date)); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-section">
        <h3>TRADING SUMMARY</h3>
        <div class="summary-row">
            <span class="summary-label">Total Revenue:</span>
            <span><?php echo number_format($total_revenue); ?> RWF</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Cost:</span>
            <span><?php echo number_format($total_cost); ?> RWF</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Net Profit/Loss:</span>
            <span class="<?php echo $total_profit >= 0 ? 'profit' : 'loss'; ?>">
                <?php echo number_format($total_profit); ?> RWF
            </span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Profit Margin:</span>
            <span><?php echo $total_revenue > 0 ? number_format(($total_profit / $total_revenue) * 100, 2) : 0; ?>%</span>
        </div>
    </div>
    
    <div class="summary-section">
        <h3>CREDIT & COLLECTIONS SUMMARY</h3>
        <div class="summary-row">
            <span class="summary-label">Credit Sales:</span>
            <span style="color: #ff6b35; font-weight: bold;"><?php echo number_format($credit_stats['credit_sales'] ?? 0); ?> RWF</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Credit Transactions:</span>
            <span><?php echo $credit_stats['credit_transactions'] ?? 0; ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Collections Received:</span>
            <span style="color: #28a745; font-weight: bold;"><?php echo number_format($credit_collections['collections_amount'] ?? 0); ?> RWF</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Payments Received:</span>
            <span><?php echo $credit_collections['collections_count'] ?? 0; ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Net Credit Impact:</span>
            <span class="<?php echo (($credit_stats['credit_sales'] ?? 0) - ($credit_collections['collections_amount'] ?? 0)) > 0 ? 'loss' : 'profit'; ?>">
                <?php echo number_format(($credit_stats['credit_sales'] ?? 0) - ($credit_collections['collections_amount'] ?? 0)); ?> RWF
            </span>
        </div>
    </div>
    
    <div class="summary-section">
        <h3>TOTAL MONEY SUMMARY</h3>
        <?php 
        $cash_revenue = $total_revenue - ($credit_stats['credit_sales'] ?? 0);
        $total_cash_in = $cash_revenue + ($credit_collections['collections_amount'] ?? 0);
        ?>
        <div class="summary-row">
            <span class="summary-label">Cash Sales:</span>
            <span><?php echo number_format($cash_revenue); ?> RWF</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Credit Collections:</span>
            <span><?php echo number_format($credit_collections['collections_amount'] ?? 0); ?> RWF</span>
        </div>
        <div class="summary-row" style="border-top: 2px solid #333; margin-top: 10px; padding-top: 10px;">
            <span class="summary-label" style="font-size: 18px;">TOTAL CASH IN HAND:</span>
            <span style="font-size: 18px; font-weight: bold; color: #1a237e;"><?php echo number_format($total_cash_in); ?> RWF</span>
        </div>
    </div>

    <div class="footer">
        <p>SmartSHOP - Your Smart Shopping Solution</p>
        <p>This report is computer generated and does not require signature</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #1a237e; color: white; border: none; border-radius: 5px;">Print Report</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; margin-left: 10px;">Close</button>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>