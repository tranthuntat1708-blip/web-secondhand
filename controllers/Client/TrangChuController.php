<?php
class TrangChuController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiTrangChu() {
        // Lùi 2 cấp để tìm thư mục models
        require_once __DIR__ . '/../../models/DanhMucModel.php';
        require_once __DIR__ . '/../../models/SanPhamModel.php';

        require_once __DIR__ . '/../../models/BannerModel.php';

        $danhMucModel = new DanhMucModel($this->conn);
        $sanPhamModel = new SanPhamModel($this->conn);
        $bannerModel = new BannerModel($this->conn);

        $ds_danhmuc = $danhMucModel->layCayDanhMuc(); // Thay cho layTatCaDanhMuc()
        $ds_sanpham = $sanPhamModel->laySanPhamMoiNhat();
        $ds_banner = $bannerModel->layBannerHienThi();

        // Lùi 2 cấp để tìm thư mục views hiển thị giao diện trang chủ
        require_once __DIR__ . '/../../views/client/layouts/header_client.php';
        require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
        require_once __DIR__ . '/../../views/client/home/giao_dien_trang_chu.php';
        require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }
}
?>