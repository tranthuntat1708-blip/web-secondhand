<?php
class DoanhThuAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function thongKeDoanhThu() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu"); exit();
        }

        // 1. Doanh thu theo 12 tháng gần nhất
        $stmt = $this->conn->query("
            SELECT 
                DATE_FORMAT(ngay_dat, '%m/%Y') as thang,
                COUNT(*) as so_don,
                SUM(CASE WHEN trang_thai = 'Hoàn thành' THEN tong_tien ELSE 0 END) as doanh_thu,
                COUNT(CASE WHEN trang_thai = 'Hoàn thành' THEN 1 END) as don_thanh_cong,
                COUNT(CASE WHEN trang_thai = 'Đã hủy' THEN 1 END) as don_huy
            FROM donhang
            WHERE ngay_dat >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(ngay_dat, '%Y-%m')
            ORDER BY DATE_FORMAT(ngay_dat, '%Y-%m') DESC
        ");
        $doanh_thu_thang = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Top 10 sản phẩm bán chạy
        $stmt2 = $this->conn->query("
            SELECT ct.ten_sanpham, ct.gia, SUM(ct.so_luong) as tong_ban, SUM(ct.gia * ct.so_luong) as tong_tien, s.hinh_anh
            FROM chitiet_donhang ct
            LEFT JOIN donhang d ON ct.donhang_id = d.id
            LEFT JOIN sanpham s ON ct.sanpham_id = s.id
            WHERE d.trang_thai = 'Hoàn thành'
            GROUP BY ct.sanpham_id, ct.ten_sanpham, ct.gia
            ORDER BY tong_ban DESC
            LIMIT 10
        ");
        $top_sp = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // 3. Tổng doanh thu toàn bộ
        $tong_dt = $this->conn->query("SELECT COALESCE(SUM(tong_tien), 0) FROM donhang WHERE trang_thai = 'Hoàn thành'")->fetchColumn();
        $tong_don = $this->conn->query("SELECT COUNT(*) FROM donhang")->fetchColumn();
        $don_tc = $this->conn->query("SELECT COUNT(*) FROM donhang WHERE trang_thai = 'Hoàn thành'")->fetchColumn();

        include __DIR__ . '/../../views/admin/layouts/header_admin.php';
        include __DIR__ . '/../../views/admin/trang_doanhthu.php';
        include __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }
}
?>
