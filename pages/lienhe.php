<script>
$(document).ready(function(){
    $(".tab_button").click(function(){
        let index = $(this).data("index");

        // Bỏ active và đổi màu
        $(".tab_button").removeClass("active").css({"background-color": "white", "color": "black"});
        $(this).addClass("active").css({"background-color": "black", "color": "white"});

        // Ẩn tất cả danh sách tab, sau đó chỉ hiện tab tương ứng
        $(".tab_ds").removeClass("active").hide();
        $(".tab_ds").eq(index).addClass("active").show();
    });

    // Hover effect
    $(".tab_button").hover(
        function() {
            if (!$(this).hasClass("active")) {
                $(this).css({"background-color": "black", "color": "white"});
            }
        },
        function() {
            if (!$(this).hasClass("active")) {
                $(this).css({"background-color": "white", "color": "black"});
            }
        }
    );

    // Mặc định: chỉ hiện tab đầu
    $(".tab_ds").hide();           // Ẩn tất cả
    $(".tab_ds").eq(0).show();     // Chỉ hiện tab đầu tiên
});
</script>

<div class="lienhe">
    
    <div style="display: flex; justify-content: center;">
        <div style="display: flex; flex-direction:column;">
            <p>Trang chủ > <span style="font-weight: bold;">Liên hệ</span></p>

            <h1>LIÊN HỆ</h1>
            <p>
                <b>- Giờ làm việc:</b> 9h30 - 21h30 (Từ thứ 2 đến chủ nhật)<br>
                <b>- Hotline:</b> 19002005<br>
                <ul>
                    <li><b>Quận Bình Thạnh:</b> Số 2, Đường Võ Oanh, Phường 25</li>
                    <li><b>Quận 12:</b> Số 70 đường Tô Ký, phường Tân Chánh Hiệp</li>
                    <li><b>Quận Thủ Đức:</b> Số 10 đường số 12, KP3, P. An Khánh</li>
                </ul>
            </p>

            <h2>Bấm vào địa chỉ để xem bản đồ đường đi</h2>
            <div class="dchi">
                <div class="bando">
                    <div class="tab_ds active" >
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.090452037361!2d106.71352817345705!3d10.804384089346076!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317528a40b315555%3A0x2638975d90873616!2zMiDEkC4gVsO1IE9hbmgsIFBoxrDhu51uZyAyNSwgQsOsbmggVGjhuqFuaCwgSOG7kyBDaMOtIE1pbmgsIFZp4buHdCBOYW0!5e0!3m2!1svi!2s!4v1748945690755!5m2!1svi!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <div class="tab_ds">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1959.148585638595!2d106.6171077!3d10.8649871!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752b2a11844fb9%3A0xbed3d5f0a6d6e0fe!2zVHLGsOG7nW5nIMSQ4bqhaSBI4buNYyBHaWFvIFRow7RuZyBW4bqtbiBU4bqjaSBUaMOgbmggUGjhu5EgSOG7kyBDaMOtIE1pbmggKFVUSCkgLSBDxqEgc-G7nyAz!5e0!3m2!1svi!2s!4v1750585109272!5m2!1svi!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <div class="tab_ds">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d7838.355445169634!2d106.7281612!3d10.7976965!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317527e8172bbda9%3A0xbfd3261164d0ae65!2zMTcgVHLhuqduIE7Do28sIFBoxrDhu51uZyBBbiBLaMOhbmgsIFRo4bunIMSQ4bupYywgSOG7kyBDaMOtIE1pbmg!5e0!3m2!1svi!2s!4v1750585241318!5m2!1svi!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="chon_dchi">
                    <div class="tab_button active" data-index="0">Địa chỉ: Số 2, Đường Võ Oanh, Phường 25, Quận Bình Thạnh</div>
                    <div class="tab_button" data-index="1">Địa chỉ: Số 70 Đường Tô Ký, phường Tân Chánh Hiệp, Quận 12</div>
                    <div class="tab_button" data-index="2">Địa chỉ: Số 10 đường số 12, KP3, P. An Khánh, Quận Thủ Đức</div>
                </div>
            </div>

            <div class="dichvu"> 
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
    </div>

   
</div>