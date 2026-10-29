<?php
class DonHangAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function danhSachDonHang() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu"); exit();
        }

        require_once __DIR__ . '/../../models/DonHangModel.php';
        $donHangModel = new DonHangModel($this->conn);
        $ds_donhang = $donHangModel->layTatCaDonHang();

        // Lấy chi tiết cho mỗi đơn hàng
        foreach ($ds_donhang as &$dh) {
            $dh['chi_tiet'] = $donHangModel->layChiTietDonHang($dh['id']);
        }
        unset($dh); // Hủy tham chiếu để tránh lỗi ghi đè dữ liệu ở vòng lặp trong View

        include __DIR__ . '/../../views/admin/layouts/header_admin.php';
        include __DIR__ . '/../../views/admin/trang_donhang.php';
        include __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    public function capNhatTrangThai() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu"); exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_once __DIR__ . '/../../models/DonHangModel.php';
            $donHangModel = new DonHangModel($this->conn);

            $donhang_id = intval($_POST['donhang_id']);
            $trang_thai = $_POST['trang_thai'];

            if (in_array($trang_thai, ['Đã hủy', 'Hủy', 'Khách không nhận', 'Hoàn trả'])) {
                $donHangModel->huyDonHang($donhang_id, $trang_thai);
            } else {
                $donHangModel->capNhatTrangThai($donhang_id, $trang_thai);
            }
            header("Location: index.php?action=admin_donhang&msg=Cập nhật trạng thái thành công!");
            exit();
        }
    }
}
?>
