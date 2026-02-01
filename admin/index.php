<?php
error_reporting(0);
ini_set('display_errors', 0);

// Kết nối cơ sở dữ liệu
$server = "localhost";
$user = "root";
$password = "";
$db = "goodoptic";
$port = 3306;
$socket = "mysql";
$conn = mysqli_connect($server, $user, $password, $db, $port, $socket);

include "./header.php";
include "./sidebar.php";
include "./topbar.php";
require './carbon/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;

/* ======================================================
  1. NHẬN BỘ LỌC
====================================================== */
$revType = $_GET['revenue_type'] ?? 'month';
$from    = $_GET['from_date'] ?? null;
$to      = $_GET['to_date'] ?? null;
/* ======================================================
  2. CÁC BIẾN WHERE (CHỈ DÙNG CHO BIỂU ĐỒ + BẢNG)
====================================================== */
$whereCommon  = "";
$whereRevenue = "";
$whereOrder20 = "";
/* ======================================================
  3. CÁC Ô THỐNG KÊ (KHÔNG LỌC)
====================================================== */
// Tổng doanh thu
$sqlRevenue = "
    SELECT SUM(od.total) AS total
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    WHERE o.status != 'Đã huỷ'
";
$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, $sqlRevenue))['total'] ?? 0;
// Tổng khách hàng
$sqlCustomers = "SELECT COUNT(DISTINCT customer_id) AS total FROM orders";
$totalCustomers = mysqli_fetch_assoc(mysqli_query($conn, $sqlCustomers))['total'] ?? 0;
// Tổng sản phẩm đã bán
$sqlSold = "
    SELECT SUM(od.quantity) AS total
    FROM order_details od
    JOIN orders o ON o.order_id = od.order_id
";
$totalSoldProducts = mysqli_fetch_assoc(mysqli_query($conn, $sqlSold))['total'] ?? 0;
// Tổng đơn hàng
$sqlOrders = "SELECT COUNT(*) AS total FROM orders";
$totalOrders = mysqli_fetch_assoc(mysqli_query($conn, $sqlOrders))['total'] ?? 0;
/* ======================================================
  4. ÁP DỤNG LỌC NGÀY (CHỈ CHO BIỂU ĐỒ + BẢNG)
====================================================== */
if ($from && $to) {
    $fromDate = Carbon::parse($from)->startOfDay()->toDateTimeString();
    $toDate   = Carbon::parse($to)->endOfDay()->toDateTimeString();
    $whereCommon  .= " AND o.created_at BETWEEN '$fromDate' AND '$toDate'";
    $whereRevenue .= " AND o.created_at BETWEEN '$fromDate' AND '$toDate'";
    $whereOrder20 .= " AND o.created_at BETWEEN '$fromDate' AND '$toDate'";
}
/* ======================================================
  5. TOP 10 SẢN PHẨM
====================================================== */
$sqlTopProduct = "
    SELECT
        od.product_id,
        p.product_name,
        SUM(od.quantity) AS total_qty,
        SUM(od.total) AS total_money
    FROM order_details od
    JOIN orders o ON o.order_id = od.order_id
    JOIN products p ON p.product_id = od.product_id
    WHERE o.status != 'Đã huỷ'
    $whereCommon
    GROUP BY od.product_id, p.product_name
    ORDER BY total_qty DESC
    LIMIT 10
";
$topProduct = mysqli_query($conn, $sqlTopProduct);
$topProducts = $topLabels = $topValues = [];
while ($row = mysqli_fetch_assoc($topProduct)) {
    $topProducts[] = $row;
    $topLabels[]  = $row['product_name'];
    $topValues[]  = (int)$row['total_qty'];
}
/* ======================================================
  6. TOP 20 ĐƠN HÀNG
====================================================== */
$sqlTopOrder = "
    SELECT
        o.order_id,
        o.customer_name,
        o.phone,
        o.pay_method,
        o.status,
        SUM(od.total) AS tongtien,
        o.created_at
    FROM orders o
    LEFT JOIN order_details od ON o.order_id = od.order_id
    WHERE 1=1
    $whereOrder20
    GROUP BY
        o.order_id,
        o.customer_name,
        o.phone,
        o.pay_method,
        o.status,
        o.created_at
    ORDER BY o.created_at DESC
    LIMIT 20
";
$topOrder = mysqli_query($conn, $sqlTopOrder);
/* ======================================================
  7. BIỂU ĐỒ DOANH THU
====================================================== */
$groupBy = "DATE(o.created_at)";
if ($revType === 'month') {
    $groupBy = "DATE_FORMAT(o.created_at,'%Y-%m')";
} elseif ($revType === 'year') {
    $groupBy = "YEAR(o.created_at)";
}
$revenueData = [];
$sql = "
    SELECT
        o.created_at,
        SUM(od.total) AS revenue
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    WHERE o.status != 'Đã huỷ'
    $whereRevenue
    GROUP BY $groupBy
    ORDER BY $groupBy
";
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $revenueData[] = [
        'x' => Carbon::parse($row['created_at'])->format('Y-m-d'),
        'y' => (float)$row['revenue']
    ];
}
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<main style="margin-top: 40px;">
  <div class="admin-content container all Content-section">

    <h2 style="margin: 30px 0 50px;">📊 <strong>THỐNG KÊ HỆ THỐNG</strong></h2>
    <!-- 4 Ô THỐNG KÊ -->
    <div class="stats-grid">
      <div class="stat-card">
          <h4>Tổng doanh thu</h4>
          <p class="stat-number">
              <?= number_format($totalRevenue ?? 0) ?> đ
          </p>
      </div>

      <div class="stat-card">
          <h4>Tổng số khách hàng</h4>
          <p class="stat-number">
              <?= $totalCustomers ?? 0 ?>
          </p>
      </div>

      <div class="stat-card">
          <h4>Tổng số sản phẩm đã bán</h4>
          <p class="stat-number">
              <?= $totalSoldProducts ?? 0 ?>
          </p>
      </div>

      <div class="stat-card">
          <h4>Tổng số đơn hàng</h4>
          <p class="stat-number">
              <?= $totalOrders ?? 0 ?>
          </p>
      </div>
    </div>
    <div class="revenue-section">
      <!-- BIỂU ĐỒ 1 (BIỂU ĐỒ DOANH THU) -->
      <div class="revenue-chart">
          <h3 style="padding: 20px 0px 20px 50px;">📈 Doanh thu theo thời gian</h3>
          <div id="revenueChart" style="height: 320px;">
          </div>
      </div>
      <!-- BỘ LỌC -->
      <div class="revenue-filter">
          <h4>Bộ lọc doanh thu</h4>
          <form method="GET" class="filter-form">
              <label>Kiểu thống kê
                  <p style="font-size:10px;color:#666">
                      *Bộ lọc này áp dụng cho toàn bộ biểu đồ và bảng dữ liệu
                  </p>
              </label>
              <select name="revenue_type">
                  <option value="day" <?= $revType == 'day' ? 'selected' : '' ?>>Theo ngày</option>
                  <option value="month" <?= $revType == 'month' ? 'selected' : '' ?>>Theo tháng</option>
                  <option value="year" <?= $revType == 'year' ? 'selected' : '' ?>>Theo năm</option>
              </select>
              <label>Từ ngày</label>
              <input type="date" name="from_date" value="<?= htmlspecialchars($from) ?>">
              <label>Đến ngày</label>
              <input type="date" name="to_date" value="<?= htmlspecialchars($to) ?>">
              <button type="submit">Lọc doanh thu</button>
          </form>
      </div>
    </div>
    <!-- TOP 10 SẢN PHẨM BÁN CHẠY -->
    <div class="card mb-4">
        <h3 class="section-title">🔥 Top 10 sản phẩm bán chạy</h3>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Mã SP</th>
                        <th>Tên sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($topProducts)): ?>
                        <?php $i=1; foreach ($topProducts as $row): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= $row['product_id'] ?></td>
                                <td><?= $row['product_name'] ?></td>
                                <td><?= $row['total_qty'] ?></td>
                                <td><?= number_format($row['total_money']) ?> đ</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:#999;">
                                Không có dữ liệu
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- BIỂU ĐỒ 2 (TOP 10 SẢN PHẨM BÁN CHẠY) -->
    <div class="card mb-4">
        <div id="topProductDonut">
    </div>
    <!-- TOP 20 ĐƠN HÀNG -->
    <div class="card">
        <h3 class="section-title">🛒 Top 20 đơn hàng mới nhất</h3>
        <p style="font-size:18px; color:#888; margin-top:6px">
            *Bảng hiển thị các đơn hàng phát sinh trong khoảng ngày đã chọn
        </p>
        <div class="card-body">
            <table class="table table-striped">
              <thead class="table-light">
                  <tr>
                      <th>Mã đơn</th>
                      <th>Khách hàng</th>
                      <th>SĐT</th>
                      <th>Thanh toán</th>
                      <th>Trạng thái</th>
                      <th>Tổng tiền</th>
                      <th>Ngày đặt</th>
                  </tr>
              </thead>
              <tbody>
              <?php while($row = mysqli_fetch_assoc($topOrder)) { ?>
                  <tr class="order-row"
                      data-id="<?= $row['order_id'] ?>"
                      style="cursor:pointer;">
                      <td><?= $row['order_id'] ?></td>
                      <td><?= $row['customer_name'] ?></td>
                      <td><?= $row['phone'] ?></td>
                      <td><?= $row['pay_method'] ?></td>
                      <td><?= $row['status'] ?></td>
                      <td><?= number_format($row['tongtien'],0,',','.') ?> đ</td>
                      <td><?= $row['created_at'] ?></td>
                  </tr>
              <?php } ?>
              </tbody>
            </table>
        </div>
    </div>
  </div>
</main>
<script>
/* ================= BIỂU ĐỒ DOANH THU ================= */
    const revenueData = <?= json_encode($revenueData, JSON_NUMERIC_CHECK) ?>;
    const options = {
        chart: {
            type: 'line',
            height: 520,
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        series: [{
            name: 'Doanh thu',
            data: revenueData
        }],
        xaxis: {
            type: 'category',
            labels: {
                rotate: -45
            }
        },
        stroke: {
            curve: 'straight',
            width: 3
        },
        markers: {
            size: 6,
            hover: {
                size: 8
            }
        },
        tooltip: {
            shared: false,
            intersect: true,
            x: {
                format: 'dd/MM/yyyy'
            },
            y: {
                formatter: val =>
                    val.toLocaleString('vi-VN') + ' đ'
            }
        },
        grid: {
            strokeDashArray: 4
        },
        colors: ['#0f62fe'],
        yaxis: {
            labels: {
                formatter: val =>
                    val.toLocaleString('vi-VN') + ' đ'
            }
        }
    };
    const chart = new ApexCharts(
        document.querySelector("#revenueChart"),
        options
    );
    chart.render();
/* ================= TOP SẢN PHẨM ================= */
const topLabels = <?= json_encode($topLabels) ?>;
const topValues = <?= json_encode($topValues, JSON_NUMERIC_CHECK) ?>;

if (topValues.length > 0) {
    const donutOptions = {
        chart: {
            type: 'donut',
            height: 560,
            offsetY: 50
        },
        series: topValues,
        labels: topLabels,
        plotOptions: {
            pie: {
                donut: {
                    size: '50%'
                }
            }
        },
        title: {
            text: 'Biểu đồ thể hiện tỷ lệ đóng góp số lượng bán ra của 10 sản phẩm có doanh số cao nhất.',
            align: 'center',
            position: 'bottom',
            margin: 10,
            offsetY: -10,
            style: {
                fontSize: '18px',
                fontWeight: '600'
            }
        },
        legend: {
            position: 'right',
            horizontalAlign: 'center',
            fontSize: '13px',
            offsetX: 80,
            offsetY: 130,
            markers: {
                width: 10,
                height: 10,
                radius: 10
            },
            itemMargin: {
                vertical: 6
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(1) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val.toLocaleString('vi-VN') + ' sản phẩm';
                }
            }
        },
        stroke: {
            width: 2
        },
        colors: [
            '#0f62fe', '#42be65', '#ff832b', '#be95ff', '#fa4d56',
            '#1192e8', '#a56eff', '#009d9a', '#ffb000', '#d12771'
        ],
        responsive: [{
            breakpoint: 1025,
            options: {
                chart: { height: 540 },
                legend: { position: 'bottom', offsetX: 0, offsetY: 50 }
            }
        },
        {
            breakpoint: 1441,
            options: {
                chart: { height: 560, offsetY: 50 },
                legend: { position: 'right', offsetX: -20, offsetY: 130, }
            }
        },
        {
            breakpoint: 769,
            options: {
                chart: { height: 500 },
                legend: { position: 'bottom', offsetX: 0, offsetY: 70 }
            }
        }]
    };
    new ApexCharts(
        document.querySelector("#topProductDonut"),
        donutOptions
    ).render();
}

// click để xem chi tiết đơn hàng
document.querySelectorAll('.order-row').forEach(row => {
    row.addEventListener('click', function () {
        const orderId = this.dataset.id;
        window.location.href = 'viewEachOrders.php?id=' + orderId;
    });
});
</script>
<?php include "footer.php"; ?>