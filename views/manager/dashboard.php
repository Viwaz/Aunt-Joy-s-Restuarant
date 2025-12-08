<?php
require_once __DIR__.'/../../auth/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->checkRole('manager')) {
    die("Access denied: Manager only.");
}


// import necessary classes
require_once '../../includes/database.php';

class ReportManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function getSalesReport($month, $year) {
        $start_date = "$year-$month-01";
        $end_date = "$year-$month-31";
        
        // Total revenue and orders
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as average_order_value
                  FROM orders
                  WHERE DATE(order_date) BETWEEN ? AND ?
                  AND status = 'delivered'";
        
        $stmt = $this->db->prepare($query);
          if (!$stmt) {
    echo "Error: " . $this->db->error;
    return null;
  }
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();
        
        // Best-selling items
        $query = "SELECT
                    m.name as meal_name,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_revenue
                  FROM order_items oi
                  JOIN menu_items m ON oi.menu_item_id = m.id
                  JOIN orders o ON oi.order_id = o.order_id
                  WHERE DATE(o.order_date) BETWEEN ? AND ?
                  AND o.status = 'delivered'
                  GROUP BY m.id, m.name
                  ORDER BY total_quantity DESC
                  LIMIT 10";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            echo "Error: " . $this->db->error;
            return null;
        }
        $stmt->bind_param("ss", $start_date, $end_date);
        
        $stmt->execute();
        $best_sellers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Daily sales trend
        $query = "SELECT 
                    DATE(order_date) as sale_date,
                    COUNT(*) as daily_orders,
                    SUM(total_amount) as daily_revenue
                  FROM orders
                  WHERE DATE(order_date) BETWEEN ? AND ?
                  AND status = 'delivered'
                  GROUP BY DATE(order_date)
                  ORDER BY sale_date";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $daily_trends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return [
            'summary' => $summary,
            'best_sellers' => $best_sellers,
            'daily_trends' => $daily_trends
        ];
    }
    
    public function exportToPDF($report_data, $month, $year) {
        // Use TCPDF to generate a valid PDF file download
        // Try to load TCPDF (via Composer autoload or common locations)


        if (!class_exists('TCPDF')) {
            // Helpful fallback message when TCPDF is not installed
            header('Content-Type: text/plain; charset=utf-8');
            echo "TCPDF library not found. Please install it with:\n\ncomposer require tecnickcom/tcpdf\n\nor place the TCPDF library under vendor/ or includes/ and try again.";
            exit;
        }

        // Define TCPDF constants if not defined (safer fallback)
        if (!defined('PDF_PAGE_ORIENTATION')) define('PDF_PAGE_ORIENTATION', 'P');
        if (!defined('PDF_UNIT')) define('PDF_UNIT', 'mm');
        if (!defined('PDF_PAGE_FORMAT')) define('PDF_PAGE_FORMAT', 'A4');
        if (!defined('PDF_CREATOR')) define('PDF_CREATOR', "Aunt Joy's Restaurant");

        // Create new PDF document
        /** @var TCPDF $pdf */
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor("Aunt Joy's Restaurant");
        $pdf->SetTitle("Sales Report {$month}/{$year}");
        $pdf->SetSubject('Sales Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Build HTML content for PDF
        $html = '<h1 style="font-family:helvetica;">Aunt Joy\'s Restaurant - Sales Report</h1>';
        $html .= '<p><strong>For:</strong> ' . htmlspecialchars(date('F Y', mktime(0,0,0,$month,1,$year))) . '</p>';

        $summary = $report_data['summary'] ?? [];
        $html .= '<h2>Summary</h2>';
        $html .= '<table border="1" cellpadding="4">'
              . '<tr><th>Total Orders</th><th>Total Revenue</th><th>Average Order Value</th></tr>'
              . '<tr><td>' . ($summary['total_orders'] ?? 0) . '</td>'
              . '<td>MWK ' . number_format($summary['total_revenue'] ?? 0, 2) . '</td>'
              . '<td>MWK ' . number_format($summary['average_order_value'] ?? 0, 2) . '</td></tr>'
              . '</table>';

        $html .= '<h2>Best Sellers</h2>';
        $html .= '<table border="1" cellpadding="4">'
              . '<tr><th>Rank</th><th>Meal Name</th><th>Qty Sold</th><th>Revenue</th></tr>';
        foreach ($report_data['best_sellers'] ?? [] as $index => $item) {
            $html .= '<tr>'
                  . '<td>' . ($index + 1) . '</td>'
                  . '<td>' . htmlspecialchars($item['meal_name']) . '</td>'
                  . '<td>' . ($item['total_quantity'] ?? 0) . '</td>'
                  . '<td>MWK ' . number_format($item['total_revenue'] ?? 0, 2) . '</td>'
                  . '</tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Daily Trends</h2>';
        $html .= '<table border="1" cellpadding="4">'
              . '<tr><th>Date</th><th>Orders</th><th>Revenue</th></tr>';
        foreach ($report_data['daily_trends'] ?? [] as $day) {
            $html .= '<tr>'
                  . '<td>' . htmlspecialchars(date('M d, Y', strtotime($day['sale_date'] ?? ''))) . '</td>'
                  . '<td>' . ($day['daily_orders'] ?? 0) . '</td>'
                  . '<td>MWK ' . number_format($day['daily_revenue'] ?? 0, 2) . '</td>'
                  . '</tr>';
        }
        $html .= '</table>';

        // Output HTML content to PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Force download
        $filename = "sales_report_{$month}_{$year}.pdf";
        $pdf->Output($filename, 'D');
        exit();
    }
    
    public function exportToExcel($report_data, $month, $year) {
        $filename = "sales_report_{$month}_{$year}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Summary
        fputcsv($output, ['Summary']);
        fputcsv($output, ['Total Orders', $report_data['summary']['total_orders'] ?? 0]);
        fputcsv($output, ['Total Revenue', 'MWK ' . number_format($report_data['summary']['total_revenue'] ?? 0, 2)]);
        fputcsv($output, ['Average Order Value', 'MWK ' . number_format($report_data['summary']['average_order_value'] ?? 0, 2)]);
        fputcsv($output, []);
        
        // Best sellers
        fputcsv($output, ['Best Selling Items']);
        fputcsv($output, ['Meal Name', 'Quantity Sold', 'Total Revenue']);
        foreach ($report_data['best_sellers'] as $item) {
            fputcsv($output, [
                $item['meal_name'],
                $item['total_quantity'],
                'MWK ' . number_format($item['total_revenue'], 2)
            ]);
        }
        
        fclose($output);
        exit();
    }
}

$reportManager = new ReportManager();
$current_month = date('m');
$current_year = date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = $_POST['month'] ?? $current_month;
    $year = $_POST['year'] ?? $current_year;
    
    if (isset($_POST['generate_report'])) {
        $report_data = $reportManager->getSalesReport($month, $year);
    } elseif (isset($_POST['export_pdf'])) {
        $report_data = $reportManager->getSalesReport($month, $year);
        $reportManager->exportToPDF($report_data, $month, $year);
    } elseif (isset($_POST['export_excel'])) {
        $report_data = $reportManager->getSalesReport($month, $year);
        $reportManager->exportToExcel($report_data, $month, $year);
    }
} else {
    $month = $current_month;
    $year = $current_year;
    $report_data = $reportManager->getSalesReport($month, $year);
}



// --- Initialization ---
// Defaults if no post request
$month = $_POST['month'] ?? date('m');
$year = $_POST['year'] ?? date('Y');


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - Reports</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Optional: Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Aunt Joy's Manager Panel</h2>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Sales Reports</a></li>
            <!-- Add more manager links here if needed -->
            <li><a href="../../auth/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <header>
            <div>
                <h1>Sales Reporting</h1>
                <p style="color: #777;">Review performance metrics and trends</p>
            </div>
            <div class="user-info">
                <strong>Manager</strong> <i class="fas fa-user-circle fa-lg"></i>
            </div>
        </header>

        <!-- Filter Form -->
        <div class="section-container">
            <h3 class="section-title">Generate Report</h3>
            <form id="report-form" method="POST" action="dashboard.php" class="filter-form">
                <div class="input-group">
                    <label style="margin-right: 10px; font-weight:bold;">Filter By:</label>
                    <select id="month-select" name="month" required>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= sprintf('%02d', $i) ?>" <?= $i == $month ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    
                    <select id="year-select" name="year" required>
                        <?php for ($i = 2020; $i <= date('Y'); $i++): ?>
                            <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="button-group" style="margin-left: auto; display:flex; gap:10px;">
                    <button type="button" name="generate_report" class="btn-primary" onclick="generateReport()"><i class="fas fa-filter"></i> Generate View</button>
                    <button type="button" name="export_pdf" class="btn-pdf" onclick="exportReportPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
                    <button type="button" name="export_excel" class="btn-excel" onclick="exportReportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
                </div>
            </form>
        </div>

        <?php if (isset($report_data)): ?>
            
            <h3 class="section-title">Summary for <?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></h3>

            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card card-blue">
                    <h4>Total Orders</h4>
                    <p><i class="fas fa-shopping-bag" style="color:#3498db; opacity:0.5;"></i> <?= $report_data['summary']['total_orders'] ?? 0 ?></p>
                </div>
                
                <div class="stat-card card-green">
                    <h4>Total Revenue</h4>
                    <p style="color: #27ae60;">MWK <?= number_format($report_data['summary']['total_revenue'] ?? 0, 2) ?></p>
                </div>
                
                <div class="stat-card card-purple">
                    <h4>Avg. Order Value</h4>
                    <p>MWK <?= number_format($report_data['summary']['average_order_value'] ?? 0, 2) ?></p>
                </div>
            </div>

            <!-- Two Column Layout for Tables -->
            <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                
                <!-- Best Sellers -->
                <div>
                    <h3 class="section-title">Best Sellers</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Meal Name</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['best_sellers'] as $index => $item): ?>
                                    <tr>
                                        <td><span class="badge" style="background:#eee;"><?= $index + 1 ?></span></td>
                                        <td style="font-weight:bold;"><?= htmlspecialchars($item['meal_name']) ?></td>
                                        <td><?= $item['total_quantity'] ?></td>
                                        <td>MWK <?= number_format($item['total_revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Daily Trends -->
                <div>
                    <h3 class="section-title">Daily Sales </h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['daily_trends'] as $day): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($day['sale_date'])) ?></td>
                                        <td><?= $day['daily_orders'] ?></td>
                                        <td>MWK <?= number_format($day['daily_revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        <?php endif; ?>

    </div>
</div>

<script src="api-client.js"></script>
<script src="manager.js"></script>
</body>
</html>