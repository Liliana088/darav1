<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

include "db_conn.php"; //connect to database

// Get today's total sales
$sql = "SELECT SUM(total_cost) AS total_sales FROM sales WHERE DATE(date) = CURDATE()";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$total_sales = $row['total_sales'] ?? 0; // Default to 0 if no sales for today

// Count number of categories
$categoryQuery = "SELECT COUNT(*) as category_count FROM categories";
$categoryResult = mysqli_query($conn, $categoryQuery);
$categoryData = mysqli_fetch_assoc($categoryResult);
$categoryCount = $categoryData['category_count'];

// For the low stock
$lowStockThreshold = 5;
$lowStockQuery = "SELECT COUNT(*) as low_count FROM products WHERE Stock <= $lowStockThreshold";
$lowStockResult = mysqli_query($conn, $lowStockQuery);
$lowStockData = mysqli_fetch_assoc($lowStockResult);
$lowStockCount = $lowStockData['low_count'];

// Show modal only once per session
if (!isset($_SESSION['shown_low_stock_popup'])) {
    $query = "SELECT COUNT(*) as low_stock_count FROM products WHERE stock <= 5";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);

    if ($data['low_stock_count'] > 0) {
        $_SESSION['show_low_stock_modal'] = true;
    }

    $_SESSION['shown_low_stock_popup'] = true;
}

// Weekly sales data
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));

$weeklySalesQuery = "
    SELECT DATE(date) AS sale_date, SUM(total_cost) AS total_sales
    FROM sales
    WHERE DATE(date) BETWEEN '$startOfWeek' AND '$endOfWeek'
    GROUP BY DATE(date)
    ORDER BY sale_date ASC";

$weeklyResult = mysqli_query($conn, $weeklySalesQuery);

$weeklyDates = [];
$weeklyTotals = [];

while ($row = mysqli_fetch_assoc($weeklyResult)) {
    $weeklyDates[] = $row['sale_date'];
    $weeklyTotals[] = round($row['total_sales'], 2);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>POS System Dashboard</title>
    <link href="bootstrap-offline/css/bootstrap.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" type="image/x-icon" href="img/daraa.ico">
    <link href="css/dashboard.css" rel="stylesheet">
  </head>
  <body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
      <div class="container-fluid d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
              <!-- Dara Logo -->
              <img src="img/daralogo.svg" alt="Dara Logo" style="height: 40px;" />
              <!-- Hamburger Icon -->
              <button class="navbar-toggler text-white border-0 bg-transparent" type="button" onclick="toggleSidebar()">
                  <i class="bi bi-list"></i>
              </button>
          </div>
        <!-- User -->
        <div class="dropdown">
            <a class="dropdown-toggle text-white text-decoration-none" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <?php echo $_SESSION['user_name']; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- Sidebar -->
    <div id="sidebarMenu" class="sidebar">
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link" href="/dara/dashboard.php">
            <i class="bi bi-house"></i>
            <span>Home</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dara/categories.php">
            <i class="bi bi-grid"></i>
            <span>Categories</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dara/products.php">
            <i class="bi bi-box-seam"></i>
            <span>Products</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dara/sales.php">
            <i class="bi bi-currency-dollar"></i>
            <span>Sales</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dara/sales-report.php">
            <i class="bi bi-bar-chart-line"></i>
            <span>Sales Report</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/dara/users.php">
            <i class="bi bi-person-circle"></i>
            <span>User Information</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <h2 class="section-header">Dashboard</h2>
      <div class="row g-4">

    <!-- Sales Card -->
    <div class="col-md-4">
      <div class="card text-white position-relative" style="background-color: #7673C0;">
        <div class="card-body">
          <div style="font-size: 1.5rem; font-weight: bold;">₱<?php echo number_format(round($total_sales), 2); ?></div>
          <div>Today's Sales</div>
        </div>
        <div class="card-footer border-0">
          <a href="sales.php" class="text-white text-decoration-none">More info <i class="bi bi-arrow-right"></i></a>
        </div>
        <i class="bi bi-cash-coin position-absolute" style="font-size: 6rem; opacity: 0.1; bottom: 10px; right: 10px;"></i>
      </div>
    </div>

    <!-- Categories Card -->
    <div class="col-md-4">
      <div class="card text-white position-relative" style="background-color: #3F417D;">
        <div class="card-body">
          <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $categoryCount; ?></div>
          <div>Categories</div>
        </div>
        <div class="card-footer border-0">
          <a href="categories.php" class="text-white text-decoration-none">More info <i class="bi bi-arrow-right"></i></a>
        </div>
        <i class="bi bi-clipboard2-fill position-absolute" style="font-size: 6rem; opacity: 0.1; bottom: 10px; right: 10px;"></i>
      </div>
    </div>

    <!-- Products Card -->
    <div class="col-md-4">
      <div class="card text-white position-relative" style="background-color: #824C83;">
        <div class="card-body">
          <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $lowStockCount; ?>
          </div>
          <div>Products With Low Stock</div>
        </div>
        <div class="card-footer border-0">
          <a href="products.php" class="text-white text-decoration-none">More info <i class="bi bi-arrow-right"></i></a>
        </div>
        <i class="bi bi-box-seam position-absolute" style="font-size: 6rem; opacity: 0.1; bottom: 10px; right: 10px;"></i>
      </div>
    </div>
  </div>

      <div class="sales-graph mt-4">
        <h4 class="text-center">SALES THIS WEEK</h4>
        <canvas id="weeklySalesChart" height="100"></canvas>
      </div>
    </div>


  <!-- Low Stock Modal -->
  <div class="modal fade" id="lowStockModal" tabindex="-1" aria-labelledby="lowStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #393E75; color: #fff;">
          <h5 class="modal-title" id="lowStockModalLabel">Low Stock Alert</h5>
        </div>
        <div class="modal-body">
          Some products are low in stock. Please check your inventory.
        </div>
        <div class="modal-footer">
          <a href="products.php?lowstock=1" class="btn" style="background-color: #393E75; color: #fff;">View Low Stock Products</a>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Dismiss</button>
        </div>
      </div>
    </div>
  </div>


    <!-- Scripts -->
    <script src="bootstrap-offline/js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.umd.min.js"></script>
    <script>
      function toggleSidebar() {
        const sidebar = document.getElementById("sidebarMenu");
        sidebar.classList.toggle("expand");
      }

      const weeklySalesChart = new Chart(document.getElementById('weeklySalesChart').getContext('2d'), {
      type: 'bar',
      data: {
          labels: <?php echo json_encode($weeklyDates); ?>,
          datasets: [{
              label: 'Sales (₱)',
              data: <?php echo json_encode($weeklyTotals); ?>,
              backgroundColor: 'rgba(120, 71, 161, 0.2)',
              borderColor: 'rgba(161, 71, 153, 0.2)',
              borderWidth: 1
          }]
      },
      options: {
          scales: {
              y: {
                  beginAtZero: true
                }
              }
            }
      });
    </script>
    <?php if (isset($_SESSION['show_low_stock_modal']) && $_SESSION['show_low_stock_modal']): ?>
    <script>
        var myModal = new bootstrap.Modal(document.getElementById('lowStockModal'));
        window.addEventListener('load', () => {
          myModal.show();
        });
    </script>  
    <?php unset($_SESSION['show_low_stock_modal']); endif; ?>
  </body>
</html>