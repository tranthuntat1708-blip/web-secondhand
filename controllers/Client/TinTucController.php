<?php
class TinTucController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiDanhSach() {
        require_once __DIR__ . '/../../models/TinTucModel.php';
        require_once __DIR__ . '/../../models/DanhMucModel.php';

        $tinTucModel = new TinTucModel($this->conn);
        $danhMucModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $danhMucModel->layCayDanhMuc();

        $ds_tintuc = $tinTucModel->layTatCaTinTuc();
        
        $tieu_de_trang = "Tin tức công nghệ";

        require_once __DIR__ . '/../../views/client/layouts/header_client.php';
        require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
        require_once __DIR__ . '/../../views/client/tintuc/danhsach_tintuc.php';
        require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }

    public function hienThiChiTiet() {
        require_once __DIR__ . '/../../models/TinTucModel.php';
        require_once __DIR__ . '/../../models/DanhMucModel.php';

        $tinTucModel = new TinTucModel($this->conn);
        $danhMucModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $danhMucModel->layCayDanhMuc();

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $tintuc = $tinTucModel->layChiTietTinTuc($id);
            
            if ($tintuc) {
                require_once __DIR__ . '/../../views/client/layouts/header_client.php';
                require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
                require_once __DIR__ . '/../../views/client/tintuc/chitiet_tintuc.php';
                require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
            } else {
                echo "<h2 style='text-align:center; margin-top:50px;'>Bài viết không tồn tại!</h2>";
            }
        }
    }
}
?>
