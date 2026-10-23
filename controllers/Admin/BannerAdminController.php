<?php
class BannerAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiDanhSach() {
        require_once __DIR__ . '/../../models/BannerModel.php';
        $bannerModel = new BannerModel($this->conn);
        $ds_banner = $bannerModel->layTatCaBanner();

        require_once __DIR__ . '/../../views/admin/layouts/header_admin.php';
        require_once __DIR__ . '/../../views/admin/banner/danhsach.php';
        require_once __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    public function themBanner() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../models/BannerModel.php';
            $bannerModel = new BannerModel($this->conn);
            
            $tieu_de = trim($_POST['tieu_de']);
            $link = trim($_POST['link']);
            $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;
            
            $hinh_anh_path = '';

            // Xử lý upload ảnh
            if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
                $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);
                $upload_dir = __DIR__ . '/../../public/client_assets/images/';
                
                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $upload_dir . $file_name)) {
                    $hinh_anh_path = './public/client_assets/images/' . $file_name;
                }
            }

            if (!empty($hinh_anh_path)) {
                $bannerModel->themBanner($tieu_de, $hinh_anh_path, $link, $trang_thai);
            }
            
            header("Location: index.php?action=admin_banner&msg=them_thanhcong");
            exit();
        }
    }

    public function xoaBanner() {
        if (isset($_GET['id'])) {
            require_once __DIR__ . '/../../models/BannerModel.php';
            $bannerModel = new BannerModel($this->conn);
            $bannerModel->xoaBanner(intval($_GET['id']));
            
            header("Location: index.php?action=admin_banner&msg=xoa_thanhcong");
            exit();
        }
    }
    public function capNhatThuTu() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['thu_tu'])) {
            require_once __DIR__ . '/../../models/BannerModel.php';
            $bannerModel = new BannerModel($this->conn);
            
            foreach ($_POST['thu_tu'] as $id => $thu_tu_value) {
                $bannerModel->capNhatThuTu(intval($id), intval($thu_tu_value));
            }
            
            header("Location: index.php?action=admin_banner&msg=capnhat_thanhcong");
            exit();
        }
    }
}
?>
