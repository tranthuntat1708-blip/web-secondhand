<?php
class SanPhamModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    // Lấy danh sách sản phẩm mới về
    public function laySanPhamMoiNhat() {
        $sql = "SELECT * FROM sanpham ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả sản phẩm
    public function layTatCaSanPham() {
        $sql = "SELECT * FROM sanpham ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết 1 sản phẩm dựa vào ID (Đã tối ưu lấy kèm cả tên danh mục)
    public function layChiTietSanPham($id) {
        $sql = "SELECT s.*, d.ten_danhmuc, d.id as danhmuc_id, p.ten_danhmuc as ten_danhmuc_cha, p.id as danhmuc_cha_id 
                FROM sanpham s 
                LEFT JOIN danhmuc d ON s.danhmuc_id = d.id 
                LEFT JOIN danhmuc p ON d.parent_id = p.id 
                WHERE s.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Chỉ lấy 1 dòng duy nhất
    }

    // Tìm kiếm sản phẩm theo từ khóa (Tìm kiếm thông minh)
    public function timKiemSanPham($keyword) {
        // Từ điển chuyển đổi viết tắt -> từ khóa đầy đủ
        $synonyms = [
            'ip' => 'iphone',
            'mac' => 'macbook',
            'ss' => 'samsung',
            'lap' => 'laptop',
            'đt' => 'điện thoại',
            'dt' => 'điện thoại'
        ];

        // Xử lý từ khóa: viết thường (dùng mb_strtolower cho tiếng Việt)
        $clean_keyword = mb_strtolower(trim($keyword), 'UTF-8');
        
        // Kiểm tra xem từ khóa có trong từ điển không
        if (array_key_exists($clean_keyword, $synonyms)) {
            $keyword = $synonyms[$clean_keyword];
        }

        // Tìm kiếm thông minh:
        // 1. Khớp tên sản phẩm
        // 2. Khớp tên danh mục trực tiếp của sản phẩm
        // 3. Khớp tên danh mục cha của sản phẩm
        $sql = "SELECT s.* FROM sanpham s 
                LEFT JOIN danhmuc d ON s.danhmuc_id = d.id 
                WHERE s.ten_sanpham LIKE :keyword 
                   OR d.ten_danhmuc LIKE :keyword 
                   OR s.danhmuc_id IN (
                       SELECT id FROM danhmuc WHERE parent_id IN (
                           SELECT id FROM danhmuc WHERE ten_danhmuc LIKE :keyword
                       )
                   )
                ORDER BY s.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy sản phẩm theo tên danh mục (cho menu ngang)
    public function laySanPhamTheoDanhMucTen($ten_danhmuc) {
        $sql = "SELECT s.* FROM sanpham s 
                JOIN danhmuc d ON s.danhmuc_id = d.id 
                WHERE d.ten_danhmuc LIKE :ten_danhmuc ORDER BY s.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ten_danhmuc' => '%' . $ten_danhmuc . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function laySanPhamTheoDanhMucId($danhmuc_id, $limit = 5) {
        $sql = "SELECT id, ten_sanpham FROM sanpham WHERE danhmuc_id = :danhmuc_id ORDER BY id DESC LIMIT " . intval($limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['danhmuc_id' => $danhmuc_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả sản phẩm thuộc 1 danh mục (bao gồm cả danh mục con)
    public function laySanPhamTheoDanhMuc($danhmuc_id) {
        $sql = "SELECT * FROM sanpham WHERE danhmuc_id = :danhmuc_id OR danhmuc_id IN (SELECT id FROM danhmuc WHERE parent_id = :danhmuc_id) ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['danhmuc_id' => $danhmuc_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lọc sản phẩm theo giá và tình trạng (xử lý trên mảng kết quả)
    public function locSanPham($danh_sach, $gia_filters, $tinh_trang_filters) {
        $ket_qua = [];
        foreach ($danh_sach as $sp) {
            $pass_gia = true;
            if (!empty($gia_filters)) {
                $pass_gia = false;
                foreach ($gia_filters as $muc_gia) {
                    if ($muc_gia == 'duoi-10' && $sp['gia'] < 10000000) $pass_gia = true;
                    if ($muc_gia == '10-25' && $sp['gia'] >= 10000000 && $sp['gia'] <= 25000000) $pass_gia = true;
                    if ($muc_gia == 'tren-25' && $sp['gia'] > 25000000) $pass_gia = true;
                }
            }

            $pass_tinh_trang = true;
            if (!empty($tinh_trang_filters)) {
                $pass_tinh_trang = false;
                $tt = strtolower($sp['tinh_trang']);
                foreach ($tinh_trang_filters as $muc_tt) {
                    if ($muc_tt == 'likenew' && strpos($tt, 'like new') !== false) $pass_tinh_trang = true;
                    if ($muc_tt == '99' && strpos($tt, '99') !== false) $pass_tinh_trang = true;
                }
            }

            if ($pass_gia && $pass_tinh_trang) {
                $ket_qua[] = $sp;
            }
        }
        return $ket_qua;
    }

    // --- PHẦN ĐÁNH GIÁ ---
    public function layDanhGiaSanPham($sanpham_id) {
        $sql = 'SELECT d.*, t.ho_ten FROM danh_gia d JOIN taikhoan t ON d.taikhoan_id = t.id WHERE d.sanpham_id = :sp_id ORDER BY d.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sp_id' => $sanpham_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function themDanhGia($sanpham_id, $taikhoan_id, $so_sao, $binh_luan) {
        $sql = 'INSERT INTO danh_gia (sanpham_id, taikhoan_id, so_sao, binh_luan) VALUES (:sp, :tk, :sao, :bl)';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['sp' => $sanpham_id, 'tk' => $taikhoan_id, 'sao' => $so_sao, 'bl' => $binh_luan]);
    }
}
?>