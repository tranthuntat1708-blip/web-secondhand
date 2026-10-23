<?php
class ChinhSachController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiChinhSach($loai) {
        // Lấy danh mục cho menu
        require_once __DIR__ . '/../../models/DanhMucModel.php';
        $danhMucModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $danhMucModel->layCayDanhMuc();

        $tieu_de = "";
        $noi_dung = "";

        switch ($loai) {
            case 'bao-mat':
                $tieu_de = "Chính Sách Bảo Mật Thông Tin";
                $noi_dung = "
                    <h3>1. Mục đích và phạm vi thu thập</h3>
                    <p>Việc thu thập dữ liệu chủ yếu trên website bao gồm: email, điện thoại, mật khẩu đăng nhập, địa chỉ khách hàng. Đây là các thông tin cần thiết để chúng tôi liên hệ xác nhận khi khách hàng đăng ký sử dụng dịch vụ và để đảm bảo quyền lợi cho cho người tiêu dùng.</p>
                    <h3>2. Phạm vi sử dụng thông tin</h3>
                    <p>Website sử dụng thông tin khách hàng cung cấp để:</p>
                    <ul>
                        <li>Cung cấp các dịch vụ/sản phẩm đến khách hàng.</li>
                        <li>Gửi các thông báo về các hoạt động trao đổi thông tin giữa khách hàng và website.</li>
                        <li>Ngăn ngừa các hoạt động phá hủy tài khoản người dùng hoặc các hoạt động giả mạo.</li>
                        <li>Liên lạc và giải quyết với khách hàng trong những trường hợp đặc biệt.</li>
                    </ul>
                    <h3>3. Thời gian lưu trữ thông tin</h3>
                    <p>Dữ liệu cá nhân của khách hàng sẽ được lưu trữ cho đến khi có yêu cầu hủy bỏ hoặc tự khách hàng đăng nhập và thực hiện hủy bỏ. Còn lại trong mọi trường hợp thông tin cá nhân khách hàng sẽ được bảo mật trên máy chủ của chúng tôi.</p>
                    <h3>4. Cam kết bảo mật thông tin cá nhân</h3>
                    <p>Thông tin cá nhân của khách hàng được cam kết bảo mật tuyệt đối theo chính sách bảo vệ thông tin cá nhân của website. Việc thu thập và sử dụng thông tin của mỗi khách hàng chỉ được thực hiện khi có sự đồng ý của khách hàng đó trừ những trường hợp pháp luật có quy định khác.</p>
                ";
                break;
            
            case 'van-chuyen':
                $tieu_de = "Chính Sách Vận Chuyển Và Kiểm Tra Hàng";
                $noi_dung = "
                    <h3>1. Phương thức vận chuyển</h3>
                    <p>Chúng tôi hợp tác với các đơn vị vận chuyển uy tín (GHTK, Viettel Post, GHN...) để giao hàng tận nơi trên toàn quốc.</p>
                    <h3>2. Thời gian giao hàng</h3>
                    <ul>
                        <li>Khu vực nội thành: Giao trong vòng 24h.</li>
                        <li>Khu vực ngoại thành và các tỉnh: Từ 2 - 4 ngày làm việc.</li>
                    </ul>
                    <p><em>Lưu ý: Thời gian giao hàng có thể thay đổi do các yếu tố khách quan như thời tiết, thiên tai.</em></p>
                    <h3>3. Chính sách kiểm tra hàng</h3>
                    <p>Khách hàng được quyền <strong>kiểm tra ngoại quan sản phẩm</strong> (mở hộp xem sản phẩm có đúng màu sắc, tình trạng nguyên vẹn hay không) trước khi thanh toán cho nhân viên giao hàng.</p>
                    <p>Tuy nhiên, đối với các sản phẩm nguyên seal, khách hàng <strong>KHÔNG</strong> được bóc seal hoặc cắm thử điện trước khi thanh toán.</p>
                ";
                break;

            case 'thanh-toan':
                $tieu_de = "Chính Sách Thanh Toán";
                $noi_dung = "
                    <h3>1. Thanh toán khi nhận hàng (COD)</h3>
                    <p>Khách hàng nhận hàng, kiểm tra sản phẩm (theo chính sách kiểm tra hàng) và thanh toán trực tiếp bằng tiền mặt cho nhân viên giao hàng.</p>
                    <h3>2. Thanh toán chuyển khoản ngân hàng</h3>
                    <p>Khách hàng có thể thanh toán bằng cách chuyển khoản qua tài khoản ngân hàng của công ty. Vui lòng ghi rõ mã đơn hàng trong nội dung chuyển khoản.</p>
                    <h3>3. Thanh toán qua ví điện tử MoMo</h3>
                    <p>Hỗ trợ thanh toán nhanh chóng qua ví điện tử MoMo bằng cách quét mã QR hoặc chuyển tiền vào số điện thoại của cửa hàng.</p>
                ";
                break;

            case 'doi-tra':
                $tieu_de = "Chính Sách Đổi Trả";
                $noi_dung = "
                    <h3>1. Điều kiện đổi trả</h3>
                    <p>Sản phẩm bị lỗi kỹ thuật do nhà sản xuất trong vòng 7 ngày kể từ ngày nhận hàng sẽ được đổi máy mới cùng loại.</p>
                    <p>Sản phẩm đổi trả phải còn nguyên vẹn, không bị trầy xước, rơi vỡ, vào nước và còn đầy đủ phụ kiện, hộp, tem bảo hành.</p>
                    <h3>2. Trường hợp KHÔNG được đổi trả</h3>
                    <ul>
                        <li>Sản phẩm hết thời hạn đổi trả (quá 7 ngày).</li>
                        <li>Sản phẩm hư hỏng do lỗi người dùng (rơi vỡ, vào nước, tự ý tháo lắp...).</li>
                        <li>Không có lỗi kỹ thuật từ nhà sản xuất.</li>
                    </ul>
                    <h3>3. Quy trình đổi trả</h3>
                    <p>Khách hàng mang sản phẩm hoặc gửi qua đường bưu điện đến cửa hàng gần nhất để kỹ thuật viên kiểm tra. Nếu đủ điều kiện, chúng tôi sẽ tiến hành đổi trả ngay cho quý khách.</p>
                ";
                break;

            default:
                header("Location: index.php?action=trangchu");
                exit();
        }

        require_once __DIR__ . '/../../views/client/layouts/header_client.php';
        require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
        require_once __DIR__ . '/../../views/client/chinhsach/chinh_sach.php';
        require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }
}
?>
