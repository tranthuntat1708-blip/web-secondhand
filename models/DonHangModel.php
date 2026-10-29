<?php
class DonHangModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function taoDonHang($taikhoan_id, $ten_nhan, $sdt_nhan, $diachi_nhan, $tong_tien, $phuong_thuc_tt, $gio_hang, $ma_giam_gia = null) {
        // Bước 1: Tạo đơn hàng chính
        $sql = "INSERT INTO donhang (taikhoan_id, ten_nguoi_nhan, dien_thoai_nhan, dia_chi_nhan, tong_tien, ma_giam_gia, phuong_thuc_tt, trang_thai) 
                VALUES (:tk_id, :ten, :sdt, :dc, :tong, :mg, :pt_tt, :tt)";
        $stmt = $this->db->prepare($sql);
        
        // Xác định trạng thái dựa trên phương thức thanh toán
        $trang_thai = ($phuong_thuc_tt === 'COD') ? 'Chờ xử lý' : 'Chờ xác nhận thanh toán';
        
        $stmt->execute([
            'tk_id' => $taikhoan_id,
            'ten' => $ten_nhan,
            'sdt' => $sdt_nhan,
            'dc' => $diachi_nhan,
            'tong' => $tong_tien,
            'mg' => $ma_giam_gia,
            'pt_tt' => $phuong_thuc_tt,
            'tt' => $trang_thai
        ]);

        // Bước 2: Lấy ID đơn hàng vừa tạo
        $donhang_id = $this->db->lastInsertId();

        // Bước 3: Lưu chi tiết từng sản phẩm trong đơn
        foreach ($gio_hang as $sp_id => $item) {
            $sql_ct = "INSERT INTO chitiet_donhang (donhang_id, sanpham_id, ten_sanpham, gia, so_luong) 
                       VALUES (:dh_id, :sp_id, :ten_sp, :gia, :sl)";
            $stmt_ct = $this->db->prepare($sql_ct);
            $stmt_ct->execute([
                'dh_id' => $donhang_id,
                'sp_id' => $sp_id,
                'ten_sp' => $item['ten_sanpham'],
                'gia' => $item['gia'],
                'sl' => $item['so_luong']
            ]);
        }
        return $donhang_id;
    }

    // Lấy danh sách đơn hàng theo user (cho khách hàng xem lịch sử)
    public function layDonHangTheoUser($user_id) {
        $sql = "SELECT * FROM donhang WHERE taikhoan_id = :uid ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả đơn hàng (cho admin)
    public function layTatCaDonHang() {
        $sql = "SELECT d.*, t.ho_ten, t.email, t.dien_thoai 
                FROM donhang d 
                LEFT JOIN taikhoan t ON d.taikhoan_id = t.id 
                ORDER BY d.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết đơn hàng (danh sách sản phẩm trong đơn)
    public function layChiTietDonHang($donhang_id) {
        $sql = "SELECT ct.*, s.hinh_anh FROM chitiet_donhang ct 
                LEFT JOIN sanpham s ON ct.sanpham_id = s.id 
                WHERE ct.donhang_id = :dh_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dh_id' => $donhang_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cập nhật trạng thái đơn hàng (admin dùng)
    public function capNhatTrangThai($donhang_id, $trang_thai) {
        $sql = "UPDATE donhang SET trang_thai = :tt WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['tt' => $trang_thai, 'id' => $donhang_id]);
    }

    public function capNhatGhiChu($id, $ghi_chu) {
        $sql = "UPDATE donhang SET ghi_chu = :ghi_chu WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['ghi_chu' => $ghi_chu, 'id' => $id]);
    }

    public function capNhatThongTinHoanTien($id, $thongtin_nganhang, $minhchung_file, $ly_do) {
        $sql = "UPDATE donhang SET thongtin_nganhang = :nganhang, minhchung_trahang = :minhchung, ly_do_trahang = :lydo WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nganhang'  => $thongtin_nganhang,
            'minhchung' => $minhchung_file,
            'lydo'      => $ly_do,
            'id'        => $id
        ]);
    }

    // Kiểm tra số lượng sản phẩm user đã mua trong tháng hiện tại
    public function kiemTraSoLuongDaMuaTrongThang($taikhoan_id, $sanpham_id) {
        $sql = "SELECT SUM(ct.so_luong) as tong_da_mua 
                FROM chitiet_donhang ct 
                JOIN donhang dh ON ct.donhang_id = dh.id 
                WHERE dh.taikhoan_id = :uid 
                  AND ct.sanpham_id = :sp_id 
                  AND MONTH(dh.ngay_dat) = MONTH(CURRENT_DATE()) 
                  AND YEAR(dh.ngay_dat) = YEAR(CURRENT_DATE()) 
                  AND dh.trang_thai != 'Đã hủy'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uid' => $taikhoan_id,
            'sp_id' => $sanpham_id
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['tong_da_mua'] ? intval($result['tong_da_mua']) : 0;
    }

    public function huyDonHang($donhang_id, $trang_thai_moi = 'Đã hủy') {
        // Lấy thông tin đơn hàng hiện tại
        $sql = "SELECT trang_thai, ma_giam_gia FROM donhang WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $donhang_id]);
        $donhang = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donhang) return false;
        
        // Không xử lý nếu đã ở các trạng thái hoàn/hủy rồi
        if (in_array($donhang['trang_thai'], ['Đã hủy', 'Hủy', 'Khách không nhận', 'Hoàn trả'])) {
            // Nếu chỉ đổi tên qua lại giữa các trạng thái hoàn/hủy thì chỉ update text, không cộng dồn lại kho
            $this->capNhatTrangThai($donhang_id, $trang_thai_moi);
            return true;
        }

        // Đổi trạng thái
        $this->capNhatTrangThai($donhang_id, $trang_thai_moi);

        // Hoàn mã giảm giá nếu có
        if (!empty($donhang['ma_giam_gia'])) {
            require_once __DIR__ . '/MaGiamGiaModel.php';
            $mgModel = new MaGiamGiaModel($this->db);
            $mgModel->hoanLaiSoLuongMa($donhang['ma_giam_gia']);
        }

        // Hoàn tồn kho sản phẩm
        $chi_tiet = $this->layChiTietDonHang($donhang_id);
        foreach ($chi_tiet as $item) {
            $sql_upd = "UPDATE sanpham SET so_luong = so_luong + :sl WHERE id = :sp_id";
            $stmt_upd = $this->db->prepare($sql_upd);
            $stmt_upd->execute(['sl' => $item['so_luong'], 'sp_id' => $item['sanpham_id']]);
        }
        
        return true;
    }
}
?>