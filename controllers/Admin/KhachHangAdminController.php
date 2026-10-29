<?php
class KhachHangAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function danhSachKhachHang() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu"); exit();
        }

        $stmt = $this->conn->query("
            SELECT t.*, COUNT(d.id) as so_don, COALESCE(SUM(CASE WHEN d.trang_thai = 'Hoàn thành' THEN d.tong_tien ELSE 0 END), 0) as tong_chi
            FROM taikhoan t 
            LEFT JOIN donhang d ON t.id = d.taikhoan_id
            WHERE t.vai_tro = 'client'
            GROUP BY t.id
            ORDER BY t.id DESC
        ");
        $ds_khachhang = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/admin/layouts/header_admin.php';
        include __DIR__ . '/../../views/admin/trang_khachhang.php';
        include __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }
}
?>
