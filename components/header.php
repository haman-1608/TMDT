<script>
$(document).ready(function(){
    // Code cũ của bạn...
    const $toggleSearch = $('#toggle-search');
    const $searchForm = $('.search-form');

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

    // Chọn các phần tử cần thiết
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.querySelector('#key');

    // 1. Tự động đóng khi người dùng cuộn trang (Lướt điện thoại)
    window.addEventListener('scroll', () => {
        if (searchForm.style.display !== 'none') {
            searchForm.style.display = 'none'; 
            // Hoặc dùng class nếu bạn đang ẩn/hiện bằng class: 
            // searchForm.classList.remove('active');
        }
    }, { passive: true });
    
    // Click vào nút Search để mở/đóng
    $toggleSearch.click(function(e){
        e.stopPropagation(); // Quan trọng: Ngăn không cho sự kiện lan ra document
        $searchForm.stop().slideToggle(200);
    });

    // NGĂN CHẶN việc đóng form khi nhấn vào bên trong form
    $searchForm.click(function(e){
        e.stopPropagation(); // Khi nhấn vào ô input hay trong form, nó sẽ KHÔNG bị tắt
    });

    // Tự động đóng khi nhấn vào bất kỳ vị trí nào bên ngoài
    document.addEventListener('click', (event) => {
        const isClickInside = searchForm.contains(event.target);
        const isSearchButton = event.target.closest('#toggle-search'); // ID nút mở tìm kiếm

        if (!isClickInside && !isSearchButton) {
            searchForm.style.display = 'none';
            // searchForm.classList.remove('active');
        }
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
            <button id="toggle-search" name="toggle-search"><img src="imgs/icon-header/cil--search.svg" alt="Tìm kiếm"></button>
            <div class="search-form" style="display: none;">
                <form method="get">
                    <input type="hidden" name="page" value="timkiem">
                    <input type="text" name="key" id="key" placeholder="Nhập từ khóa tìm kiếm">
                    <button type="submit" name="search"><img src="imgs/icon-header/cil--search.svg" alt="Tìm kiếm"></button>
                </form>
            </div>
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