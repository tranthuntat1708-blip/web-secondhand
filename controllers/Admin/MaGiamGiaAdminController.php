<?php
class MaGiamGiaAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiDanhSach() {
        require_once __DIR__ . '/../../models/MaGiamGiaModel.php';
        $mgModel = new MaGiamGiaModel($this->conn);
        $ds_magiamgia = $mgModel->layTatCaMaGiamGia();

        require_once __DIR__ . '/../../views/admin/layouts/header_admin.php';
        require_once __DIR__ . '/../../views/admin/magiamgia/danhsach.php';
        require_once __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    public function themMaGiamGia() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../models/MaGiamGiaModel.php';
            $mgModel = new MaGiamGiaModel($this->conn);
            
            $ma_code = trim($_POST['ma_code']);
            $loai_giam = $_POST['loai_giam'];
            $muc_giam = intval($_POST['muc_giam']);
            $giam_toi_da = !empty($_POST['giam_toi_da']) ? intval($_POST['giam_toi_da']) : 0;
            $ngay_het_han = $_POST['ngay_het_han'];
            $so_luong = intval($_POST['so_luong']);

            if ($so_luong < 1) $so_luong = 1;
            if ($so_luong > 100) $so_luong = 100;

            if (!empty($ma_code) && $muc_giam > 0) {
                $mgModel->themMaGiamGia($ma_code, $loai_giam, $muc_giam, $giam_toi_da, $ngay_het_han, $so_luong);
            }
            
            header("Location: index.php?action=admin_magiamgia&msg=them_thanhcong");
            exit();
        }
    }

    public function xoaMaGiamGia() {
        if (isset($_GET['id'])) {
            require_once __DIR__ . '/../../models/MaGiamGiaModel.php';
            $mgModel = new MaGiamGiaModel($this->conn);
            $mgModel->xoaMaGiamGia(intval($_GET['id']));
            header("Location: index.php?action=admin_magiamgia&msg=xoa_thanhcong");
            exit();
        }
    }
}
?>
