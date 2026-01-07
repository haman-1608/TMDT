<?php
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT p.*, m.material_name , b.brand_name, stock
                FROM products p 
                JOIN brands b ON p.brand_id = b.brand_id 
                JOIN Material m ON p.Material_id = m.material_id 
                WHERE p.product_id = $id AND p.status = 'Active' AND m.status = 'Active' AND b.status = 'Active'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
    } else {
        echo "<p>Sản phẩm không tồn tại.</p>";
        return;
    }
} else {
    echo "<p>Không tìm thấy sản phẩm.</p>";
    return;
}


?>
<script type="text/javascript">
    $(document).ready(function() {
        $("input[name ='add_to_cart']").hover(
            function() {
                $(this).css({
                    "background-color": "white",
                    "color": "black"
                });
            },
            function() {
                $(this).css({
                    "background-color": "black",
                    "color": "white"
                });
            }
        );

    })
</script>
<div class="chitiet">
    <div class="ctietsp">
        <div class="ctsp1">
            <?php
            $giam_phantram = 0;
            if ($product['price'] > 0 && $product['disscounted_price'] < $product['price']) {
                $giam_phantram = 100 - ($product['disscounted_price'] / $product['price']) * 100;
                $giam_phantram = round($giam_phantram);
            }
            ?>
            <?php if ($giam_phantram > 0): ?>
                <p style="font-size: 20px;
                    z-index: 1;
                    top: 250px;
                    margin: 0;
                    border-radius: 0px 30px 30px 0px;
                    width: 100px;
                    padding: 11px 13px;
                    font-weight: bold;
                    color: white;
                    background-color: red;
                    position: absolute;">Giảm <?php echo $giam_phantram; ?>%</p>
            <?php endif; ?>
            <?php
            $firstImage = $product['images'];

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
            <img style="width: 100%;" src="<?php echo $imgSrc; ?>" alt="Ảnh sản phẩm" loading="lazy">
        </div>
        <div class="ctsp2">
            <h1 style="margin-top: 0;"><?php echo $product['product_name']; ?></h1>
            <p>Mã sản phẩm: <?php echo $product['product_id']; ?></p>
            <div class="price">
                <b style="font-size: 25px;"><?php echo number_format($product['disscounted_price'], 0, ',', '.') . ' VNĐ'; ?></b>
                <i style="text-decoration: line-through; margin-top: 10px;"><?php echo number_format($product['price'], 0, ',', '.') . ' VNĐ'; ?></i>
            </div>
            <div class="ttin">
                <p>
                    &nbsp;&nbsp;&nbsp;THÔNG TIN SẢN PHẨM<br>
                    * Mô tả: <?php echo $product['description']; ?><br>
                    * Thương Hiệu: <?php echo $product['brand_name']; ?><br>
                    * Mã sản phẩm: <?php echo $product['product_id']; ?><br>
                    * Chất liệu: <?php echo $product['material_name']; ?><br>
                    * Giá sản phẩm: <?php echo number_format($product['price'], 0, ',', '.') . ' VNĐ'; ?><br>
                    * CẢNH BÁO: BẢO QUẢN TRONG HỘP KÍNH<br>
                    * HDSD: DÙNG ĐỂ ĐEO MẮT, TRÁNH NHIỆT ĐỘ CAO & VA CHẠM MẠNH<br>
                </p>
                <b style="font-size: 18px; margin-top: 40px;">Số lượng</b>
                <form method="post" name="addcart" id="addcart" onsubmit="return checkAddToCart();">
                    <div class="add">
                        <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">
                        <input type="hidden" name="name" value="<?php echo $product['product_name']; ?>">
                        <input type="hidden" name="price" value="<?php echo $product['disscounted_price']; ?>">
                        <?php $firstImage = $product['images'];

                        if (preg_match('#^https?://#i', $firstImage)) {
                            $imgPath = $firstImage; // URL
                        } else {
                            $imgPath = basename($firstImage); // chỉ tên file có định dạng như .jpg
                        }
                        ?>
                        <input type="hidden" name="imgs" value="<?php echo $imgPath; ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>"/>
                        <input type="submit" name="add_to_cart" id="add_to_cart" value="ADD TO CART"></input>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="dichvu" style="border-top: 0.5px solid black; border-bottom: 0.5px solid black;"> 
        <div class="dvu1">
            <div>
            <img src="imgs/trangchu/mdi--support 2.svg" alt="" class="">
            <p> <b>Vệ Sinh Kính Miễn Phí</b> <br>
            tại toàn bộ hệ thống mắt kính Good Optic
            </p>
            </div>
            <div>
            <img src="imgs/trangchu/carbon--delivery 1.svg" alt="" class="">
            <p> <b >Giao Hàng Nhanh</b><br>
            chỉ từ 2 ngày trên toàn quốc
            </p>
            </div>
        </div>
        
        <div class="dvu1"> 
            <div>
            <img src="imgs/trangchu/carbon--ibm-data-product-exchange 1.svg" alt="" class="">
            <p> <b>Thu Cũ Đổi Mới </b><br>
            trợ giá lên đến 200.000đ
            </p>
            </div>
            <div>
            <img src="imgs/trangchu/image 1.svg" alt="" class="">
            <p> <b>Hỗ Trợ Đo Mắt</b> <br>
            tại toàn bộ hệ thống mắt kính Good Optic
            </p>
            </div>
        </div>
        
    </div>

    <?php
    $material_id = intval($product['Material_id']);
    $product_id = intval($product['product_id']);

    $sql_goiy = "SELECT product_id, product_name, disscounted_price, images 
                    FROM products 
                    WHERE Material_id = $material_id 
                    AND product_id != $product_id 
                    AND status = 'Active' 
                    LIMIT 4";
    $result_goiy = mysqli_query($conn, $sql_goiy);
    ?>
    <div class="like" style="margin: 0 auto; max-width: 1250px;">
        <h3 style=" margin-bottom: -20px; margin-left: 30px;">CÓ THỂ BẠN CŨNG THÍCH</h3>
        <div class="goiy">
            <?php if ($result_goiy && mysqli_num_rows($result_goiy) > 0): ?>
                <?php while ($goiy = mysqli_fetch_assoc($result_goiy)): ?>
                    <a href="index.php?page=chitiet&id=<?php echo $goiy['product_id']; ?>" class="sp">
                        <div class="ndsp">
                            <div class="anhsp">
                                <?php
                                $firstImage = $goiy['images'];

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
                            <p class="tensp"><?php echo htmlspecialchars($goiy['product_name']); ?></p>
                            <p class="gia" style="margin-top: 0px; margin-left: 10px;"><?php echo number_format($goiy['disscounted_price'], 0, ',', '.') . ' đ'; ?></p>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Không có sản phẩm gợi ý.</p>
            <?php endif; ?>
        </div>
    </div>
</div>