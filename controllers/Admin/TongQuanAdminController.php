<?php
class TongQuanAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function xemTongQuan() {
        // Kiểm tra quyền admin
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu");
            exit();
        }

        // 1. Doanh thu tháng này (chỉ tính đơn "Hoàn thành")
        $thang = date('m');
        $nam = date('Y');
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(tong_tien), 0) FROM donhang WHERE trang_thai = 'Hoàn thành' AND MONTH(ngay_dat) = :thang AND YEAR(ngay_dat) = :nam");
        $stmt->execute(['thang' => $thang, 'nam' => $nam]);
        $doanh_thu_thang = $stmt->fetchColumn();

        // 2. Tổng đơn hàng
        $tong_don_hang = $this->conn->query("SELECT COUNT(*) FROM donhang")->fetchColumn();

        // 3. Tổng sản phẩm
        $tong_san_pham = $this->conn->query("SELECT COUNT(*) FROM sanpham")->fetchColumn();

        // 4. Tổng khách hàng
        $tong_khach_hang = $this->conn->query("SELECT COUNT(*) FROM taikhoan WHERE vai_tro = 'client'")->fetchColumn();

        // 5. Đơn chờ xử lý
        $don_cho_xu_ly = $this->conn->query("SELECT COUNT(*) FROM donhang WHERE trang_thai IN ('Chờ xử lý', 'Chờ xác nhận thanh toán')")->fetchColumn();

        // 6. Doanh thu tháng trước (so sánh)
        $thang_truoc = date('m', strtotime('-1 month'));
        $nam_truoc = date('Y', strtotime('-1 month'));
        $stmt2 = $this->conn->prepare("SELECT COALESCE(SUM(tong_tien), 0) FROM donhang WHERE trang_thai = 'Hoàn thành' AND MONTH(ngay_dat) = :thang AND YEAR(ngay_dat) = :nam");
        $stmt2->execute(['thang' => $thang_truoc, 'nam' => $nam_truoc]);
        $doanh_thu_thang_truoc = $stmt2->fetchColumn();

        // 7. 5 đơn hàng mới nhất
        $stmt3 = $this->conn->query("SELECT d.*, t.ho_ten FROM donhang d LEFT JOIN taikhoan t ON d.taikhoan_id = t.id ORDER BY d.id DESC LIMIT 5");
        $don_hang_moi = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        // 8. Dữ liệu biểu đồ doanh thu 7 ngày gần nhất
        $chart_labels = [];
        $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('d/m', strtotime($date));
            
            $stmt4 = $this->conn->prepare("SELECT COALESCE(SUM(tong_tien), 0) FROM donhang WHERE trang_thai = 'Hoàn thành' AND DATE(ngay_dat) = :dt");
            $stmt4->execute(['dt' => $date]);
            $chart_data[] = $stmt4->fetchColumn();
        }

        include __DIR__ . '/../../views/admin/layouts/header_admin.php';
        include __DIR__ . '/../../views/admin/trang_tongquan.php';
        include __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }
}
?>