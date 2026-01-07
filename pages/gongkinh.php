<?php
$limit = 12;

$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;

$offset = ($page - 1) * $limit;
$where = "WHERE category_id = 3 AND status = 'Active'";

// Filter chất liệu
if (!empty($_GET['chatlieu'])) {
    $material_id = (int)$_GET['chatlieu'];
    $where .= " AND Material_id = $material_id";
}

// Filter thương hiệu
if (!empty($_GET['thuonghieu'])) {
    $brand_id = (int)$_GET['thuonghieu'];
    $where .= " AND brand_id = $brand_id";
}

// Filter giá tiền
if (!empty($_GET['giatien'])) {
    list($min, $max) = explode('_', $_GET['giatien']);
    $where .= " AND disscounted_price BETWEEN $min AND $max";
}

// Đếm tổng sản phẩm sau khi lọc
$count_sql = "SELECT COUNT(*) AS total FROM products $where";
$count_result = mysqli_query($conn, $count_sql);
$total_row = mysqli_fetch_assoc($count_result);
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $limit);

// Truy vấn sản phẩm có phân trang
$sql = "SELECT product_id, product_name, disscounted_price, images 
        FROM products 
        $where 
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);

if($total_products <= 0){
    echo '<div style = "margin-top: 90px; text-align: center"> Không có sản phẩm nào tồn tại!!!! </div>';
}
?>
<div class="dmsp">
    <p>Trang chủ > <b>Gọng kính</b></p>
    <h2 style="text-align: center;">Gọng Kính</h2>
    <form class="filter" method="get">
        <input type="hidden" name="page" value="gongkinh">
        <select name="chatlieu" id="chatlieu" onchange="this.form.submit()">
            <option value="">Chất liệu</option>
            <?php
                $m_query = mysqli_query($conn, "SELECT material_id, material_name FROM Material WHERE status='Active'");
                while ($m = mysqli_fetch_assoc($m_query)) {
                    $selected = (isset($_GET['chatlieu']) && $_GET['chatlieu'] == $m['material_id']) ? 'selected' : '';
                    echo "<option value='{$m['material_id']}' $selected>{$m['material_name']}</option>";
                }
            ?>
        </select>
        <select name="thuonghieu" id="thuonghieu" onchange="this.form.submit()">
            <option value="">Thương hiệu</option>
            <?php
                $m_query = mysqli_query($conn, "SELECT brand_id, brand_name FROM brands");
                while ($m = mysqli_fetch_assoc($m_query)) {
                    $selected = (isset($_GET['thuonghieu']) && $_GET['thuonghieu'] == $m['brand_id']) ? 'selected' : '';
                    echo "<option value='{$m['brand_id']}' $selected>{$m['brand_name']}</option>";
                }
            ?>
        </select>
        <select name="giatien" id="giatien" onchange="this.form.submit()">
            <option value="">Giá tiền</option>
            <option value="0_1000000" <?php if (isset($_GET['giatien']) && $_GET['giatien'] == '0_1000000') echo 'selected'; ?>>Dưới 1 triệu</option>
            <option value="1000000_2000000" <?php if (isset($_GET['giatien']) && $_GET['giatien'] == '1000000_2000000') echo 'selected'; ?>>1 - 2 triệu</option>
            <option value="2000000_4000000" <?php if (isset($_GET['giatien']) && $_GET['giatien'] == '2000000_4000000') echo 'selected'; ?>>2 - 4 triệu</option>
            <option value="4000000_999999999" <?php if (isset($_GET['giatien']) && $_GET['giatien'] == '4000000_999999999') echo 'selected'; ?>>Trên 4 triệu</option>
        </select>
    </form>

    <div class="gongkinh">
        <?php
        while ($row = mysqli_fetch_assoc($result)):
        ?>

            <a href="index.php?page=chitiet&id=<?php echo $row['product_id']; ?>" class="sp">
                <div class="ndsp">
                    <div class="anhsp">
                        <?php
                        $firstImage = $row['images'];

                        if (preg_match('#^https?://#i', $firstImage)) {
                            // Là URL tuyệt đối
                            $imgSrc = $firstImage;
                        } else {
                            // Là tên file ảnh được upload, kiểm tra file tồn tại
                            $localPath = 'imgs/products/' . $firstImage;
                            if (file_exists($localPath)) {
                                $imgSrc = $localPath;
                            } else {
                                $imgSrc = 'imgs/products/default.jpg'; // fallback ảnh mặc định nếu file không tồn tại
                            }
                        }
                        ?>
                        <img src="<?php echo $imgSrc; ?>" alt="Ảnh sản phẩm" loading="lazy">
                    </div>
                    <p class="tensp"><?php echo htmlspecialchars($row['product_name']); ?></p>
                    <p class="gia" style="margin-left: 10px; margin-top:0px;"><?php echo number_format($row['disscounted_price'], 0, ',', '.') . ' đ'; ?></p>
                </div>
            </a>
        <?php endwhile; ?>
    </div>

    <div class="pagination" style="text-align: center; margin-top: 20px;">
        <?php

        if ($page > 1) {
            $query_params['page_num'] = $page - 1;
            echo '<a href="?' . http_build_query($query_params) . '" class="arrow">‹</a>';
        }

        // Hiển thị tất cả các trang
        // Lấy các tham số lọc để gắn vào link phân trang
        $query_params = $_GET;
        unset($query_params['page_num']); // bỏ page_num cũ

        for ($i = 1; $i <= $total_pages; $i++) {
            $query_params['page_num'] = $i;
            $query_string = http_build_query($query_params);

            if ($i == $page) {
                echo '<span class="current">' . $i . '</span>';
            } else {
                echo '<a href="?' . $query_string . '">' . $i . '</a>';
            }
        }


        // Nút "›" tiến tới nếu không phải trang cuối
        if ($page < $total_pages) {
            $query_params['page_num'] = $page + 1;
            echo '<a href="?' . http_build_query($query_params) . '" class="arrow">›</a>';
        }
        ?>
    </div>
</div>