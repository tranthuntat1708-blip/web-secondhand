<?php
class DanhMucModel {
    private $db;

    // Hàm khởi tạo kết nối cơ sở dữ liệu
    public function __construct($conn) {
        $this->db = $conn;
    }

    // Hàm lấy tất cả danh mục (giữ lại để tránh lỗi các trang cũ nếu có dùng)
    public function layTatCaDanhMuc() {
        $sql = "SELECT * FROM danhmuc ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy các danh mục gốc (Không có danh mục cha - ví dụ: Điện thoại, MacBook)
    public function layDanhMucCha() {
        $sql = "SELECT * FROM danhmuc WHERE parent_id IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy các danh mục con dựa vào ID của danh mục cha (ví dụ: iPhone, Android)
    public function layDanhMucCon($parent_id) {
        $sql = "SELECT * FROM danhmuc WHERE parent_id = :parent_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['parent_id' => $parent_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thuật toán gom nhóm tự động tạo thành cây danh mục đa cấp Cha -> Con
    public function layCayDanhMuc() {
        $danhmuc_cha = $this->layDanhMucCha();
        foreach ($danhmuc_cha as $key => $cha) {
            $danhmuc_cha[$key]['sub'] = $this->layDanhMucCon($cha['id']);
        }
        return $danhmuc_cha;
    }

    public function themDanhMuc($ten_danhmuc, $parent_id = null) {
        $sql = "INSERT INTO danhmuc (ten_danhmuc, parent_id) VALUES (:ten, :pid)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'ten' => $ten_danhmuc,
            'pid' => $parent_id ? $parent_id : null
        ]);
    }

    public function suaDanhMuc($id, $ten_danhmuc, $parent_id = null) {
        $sql = "UPDATE danhmuc SET ten_danhmuc = :ten, parent_id = :pid WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'ten' => $ten_danhmuc,
            'pid' => $parent_id ? $parent_id : null,
            'id' => $id
        ]);
    }

    /**
     * Kiểm tra danh mục có thể xóa an toàn không.
     * Trả về mảng ['co_the_xoa' => bool, 'ly_do' => string]
     */
    public function kiemTraCoTheXoa($id) {
        // Kiểm tra có danh mục con không
        $stmtCon = $this->db->prepare("SELECT COUNT(*) FROM danhmuc WHERE parent_id = :id");
        $stmtCon->execute(['id' => $id]);
        $soCon = (int)$stmtCon->fetchColumn();
        if ($soCon > 0) {
            return ['co_the_xoa' => false, 'ly_do' => 'danh_muc_con'];
        }

        // Kiểm tra có sản phẩm thuộc danh mục này không
        // Thử cả hai tên cột phổ biến: danhmuc_id và id_danhmuc
        try {
            $stmtSP = $this->db->prepare("SELECT COUNT(*) FROM sanpham WHERE danhmuc_id = :id");
            $stmtSP->execute(['id' => $id]);
            $soSP = (int)$stmtSP->fetchColumn();
        } catch (\PDOException $e) {
            // Thử tên cột khác
            try {
                $stmtSP2 = $this->db->prepare("SELECT COUNT(*) FROM sanpham WHERE id_danhmuc = :id");
                $stmtSP2->execute(['id' => $id]);
                $soSP = (int)$stmtSP2->fetchColumn();
            } catch (\PDOException $e2) {
                // Không xác định được cột, cho phép kiểm tra bằng FK exception
                $soSP = 0;
            }
        }

        if ($soSP > 0) {
            return ['co_the_xoa' => false, 'ly_do' => 'co_san_pham', 'so_luong' => $soSP];
        }

        return ['co_the_xoa' => true, 'ly_do' => ''];
    }

    public function xoaDanhMuc($id) {
        // Kiểm tra an toàn trước khi xóa
        $check = $this->kiemTraCoTheXoa($id);
        if (!$check['co_the_xoa']) {
            // Ném exception để controller bắt và hiển thị thông báo lỗi
            throw new \Exception($check['ly_do']);
        }

        $sql = "DELETE FROM danhmuc WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
?>