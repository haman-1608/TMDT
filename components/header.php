<script>
$(document).ready(function(){
    // Code cũ của bạn...
    $("#toggle-search").click(function(){
        $(".search-form").slideToggle(200);
    });

    // MỚI: Click 3 gạch để mở menu
    $("#hamburger").click(function(){
        $(this).toggleClass("active");    // Xoay nút thành dấu X
        $("#nav-menu").toggleClass("open"); // Đẩy menu ra
    });

    // Tự đóng menu khi click vào một đường link
    $(".menu li a").click(function(){
        $("#nav-menu").removeClass("open");
        $("#hamburger").removeClass("active");
    });
});
</script>
<header>
    <div class="logo">
        <a href="index.php?page=trangchu">
            <img src="imgs/Logo2.png" alt="Good Optic">
        </a>
    </div>

    <div class="menu-hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <nav id="nav-menu">
        <ul class="menu">
            <li><a href="index.php?page=trangchu">Trang Chủ</a></li>
            <li><a href="index.php?page=gongkinh">Gọng Kính</a></li>
            <li><a href="index.php?page=kinhram">Kính Râm</a></li>
            <li><a href="index.php?page=trongkinh">Tròng Kính</a></li>
            <li><a href="index.php?page=gioithieu">Giới Thiệu</a></li>
            <li><a href="index.php?page=lienhe">Liên Hệ</a></li>
        </ul>
    </nav>

    <div class="sxc">
        <div class="icon-search">
            <a href="#">
                <img src="imgs/icon-header/cil--search.svg" alt="Tìm kiếm">
            </a>
        </div>
        <div class="icon-cart">
            <a href="index.php?page=giohang">
                <img src="imgs/icon-header/solar--cart-4-bold.svg" alt="Giỏ hàng">
            </a>
        </div>
        <div class="icon-follow">
            <a href="index.php?page=tracuu">
                <img src="imgs/icon-header/Group.svg" alt="Tra cứu đơn hàng">
            </a>

        </div>
    </div>
</header>