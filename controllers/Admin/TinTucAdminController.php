<?php
class TinTucAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiDanhSach() {
        require_once __DIR__ . '/../../models/TinTucModel.php';
        $tinTucModel = new TinTucModel($this->conn);
        
        $ds_tintuc = $tinTucModel->layTatCaTinTuc();

        require_once __DIR__ . '/../../views/admin/layouts/header_admin.php';
        require_once __DIR__ . '/../../views/admin/tintuc/danhsach.php';
        require_once __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    public function themTinTuc() {
        require_once __DIR__ . '/../../models/TinTucModel.php';
        $tinTucModel = new TinTucModel($this->conn);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tieu_de = trim($_POST['tieu_de']);
            $noi_dung_ngan = trim($_POST['noi_dung_ngan']);
            $noi_dung = trim($_POST['noi_dung']);
            $hinh_anh = trim($_POST['hinh_anh_url']); // Đơn giản hóa, dùng link ảnh

            if (!empty($tieu_de) && !empty($noi_dung)) {
                $tinTucModel->themTinTuc($tieu_de, $hinh_anh, $noi_dung_ngan, $noi_dung);
                header("Location: index.php?action=admin_tintuc&msg=them_thanhcong");
                exit();
            } else {
                $error = "Vui lòng nhập đủ Tiêu đề và Nội dung!";
            }
        }

        require_once __DIR__ . '/../../views/admin/layouts/header_admin.php';
        require_once __DIR__ . '/../../views/admin/tintuc/them.php';
        require_once __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    public function suaTinTuc() {
        require_once __DIR__ . '/../../models/TinTucModel.php';
        $tinTucModel = new TinTucModel($this->conn);
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $tt = $tinTucModel->layChiTietTinTuc($id);
        
        if (!$tt) {
            header("Location: index.php?action=admin_tintuc");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tieu_de = trim($_POST['tieu_de']);
            $noi_dung_ngan = trim($_POST['noi_dung_ngan']);
            $noi_dung = trim($_POST['noi_dung']);
            $hinh_anh = trim($_POST['hinh_anh_url']); 

            if (!empty($tieu_de) && !empty($noi_dung)) {
                $tinTucModel->suaTinTuc($id, $tieu_de, $hinh_anh, $noi_dung_ngan, $noi_dung);
                header("Location: index.php?action=admin_tintuc&msg=sua_thanhcong");
                exit();
            } else {
                $error = "Vui lòng nhập đủ Tiêu đề và Nội dung!";
            }
        }

        require_once __DIR__ . '/../../views/admin/layouts/header_admin.php';
        require_once __DIR__ . '/../../views/admin/tintuc/sua.php';
        require_once __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    public function xoaTinTuc() {
        require_once __DIR__ . '/../../models/TinTucModel.php';
        $tinTucModel = new TinTucModel($this->conn);

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $tinTucModel->xoaTinTuc($id);
            header("Location: index.php?action=admin_tintuc&msg=xoa_thanhcong");
            exit();
        }
    }
}
?>
