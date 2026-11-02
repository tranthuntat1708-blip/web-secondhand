<?php
class TaiKhoanController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiDangNhapKy() {
        // Lấy thông báo lỗi hoặc thông báo thành công
        $msg = isset($_GET['msg']) ? $_GET['msg'] : '';
        $mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

        // Gọi View độc lập (Không dùng chung Header/Footer)
        if ($mode === 'register') {
            require_once __DIR__ . '/../../views/client/taikhoan/dangky.php';
        } else {
            require_once __DIR__ . '/../../views/client/taikhoan/dangnhap.php';
        }
    }

    public function xuLyDangKy() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_once __DIR__ . '/../../models/TaiKhoanModel.php';
            $model = new TaiKhoanModel($this->conn);

            // Gán rỗng cho địa chỉ vì form mới không có trường này
            $dia_chi = '';
            
            // Xử lý logic check password match nếu truyền từ UI (tuy nhiên validation chính nằm ở JS)
            $mat_khau = $_POST['mat_khau'];
            $xac_nhan = isset($_POST['xac_nhan_mat_khau']) ? $_POST['xac_nhan_mat_khau'] : '';
            if ($xac_nhan && $mat_khau !== $xac_nhan) {
                header("Location: index.php?action=taikhoan&mode=register&msg=Mật khẩu xác nhận không khớp!");
                exit();
            }

            // Kiểm tra email tồn tại trước khi đăng ký
            if ($model->kiemTraEmailTonTai($_POST['email'])) {
                header("Location: index.php?action=taikhoan&mode=register&msg=Email này đã được đăng ký rồi!");
                exit();
            }

            // Kiểm tra số điện thoại tồn tại trước khi đăng ký
            if ($model->kiemTraDienThoaiTonTai($_POST['dien_thoai'])) {
                header("Location: index.php?action=taikhoan&mode=register&msg=Số điện thoại này đã được đăng ký!");
                exit();
            }

            try {
                $id = $model->dangKy($_POST['ho_ten'], $_POST['email'], $mat_khau, $_POST['dien_thoai'], $dia_chi);
                if ($id) {
                    header("Location: index.php?action=taikhoan&mode=login&msg=Đăng ký thành công! Mời bạn đăng nhập.");
                } else {
                    header("Location: index.php?action=taikhoan&mode=register&msg=Đăng ký thất bại!");
                }
            } catch (PDOException $e) {
                header("Location: index.php?action=taikhoan&mode=register&msg=Lỗi: Email hoặc số điện thoại đã tồn tại!");
            }
            exit();
        }
    }

    public function xuLyDangNhap() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_once __DIR__ . '/../../models/TaiKhoanModel.php';
            $model = new TaiKhoanModel($this->conn);

            $user = $model->kiemTraDangNhap($_POST['email'], $_POST['mat_khau']);
            if ($user) {
                $_SESSION['user'] = $user; // Lưu thông tin đăng nhập thành công vào hệ thống
                
                // Nếu khách đang kẹt ở giỏ hàng, quay lại giỏ để thanh toán tiếp, ngược lại về trang chủ
                if (!empty($_SESSION['giohang'])) {
                    header("Location: index.php?action=thanhtoan");
                } else {
                    header("Location: index.php?action=trangchu");
                }
            } else {
                header("Location: index.php?action=taikhoan&msg=Sai tài khoản hoặc mật khẩu rồi bạn ơi!");
            }
            exit();
        }
    }

    public function dangXuat() {
        unset($_SESSION['user']);
        header("Location: index.php?action=trangchu");
        exit();
    }

    // Hiển thị trang thông tin cá nhân
    public function hienThiThongTin() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=taikhoan&msg=Vui lòng đăng nhập!");
            exit();
        }

        // Lấy thông tin mới nhất từ DB
        require_once __DIR__ . '/../../models/TaiKhoanModel.php';
        $model = new TaiKhoanModel($this->conn);
        $user = $model->layThongTinUser($_SESSION['user']['id']);
        $_SESSION['user'] = $user; // Cập nhật lại session

        require_once __DIR__ . '/../../models/DanhMucModel.php';
        $danhMucModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $danhMucModel->layCayDanhMuc();

        require_once __DIR__ . '/../../views/client/layouts/header_client.php';
        require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
        require_once __DIR__ . '/../../views/client/taikhoan/thong_tin.php';
        require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }

    // Xử lý cập nhật thông tin cá nhân
    public function capNhatThongTin() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user'])) {
            require_once __DIR__ . '/../../models/TaiKhoanModel.php';
            $model = new TaiKhoanModel($this->conn);

            $model->capNhatThongTin(
                $_SESSION['user']['id'],
                $_POST['ho_ten'],
                $_POST['dien_thoai'],
                $_POST['dia_chi']
            );

            // Cập nhật lại session
            $user = $model->layThongTinUser($_SESSION['user']['id']);
            $_SESSION['user'] = $user;

            header("Location: index.php?action=thong_tin&msg=Cập nhật thông tin thành công!");
            exit();
        }
    }
}
?>