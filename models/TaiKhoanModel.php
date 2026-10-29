<?php
class TaiKhoanModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function dangKy($ho_ten, $email, $mat_khau, $dien_thoai, $dia_chi) {
        $sql = "INSERT INTO taikhoan (ho_ten, email, mat_khau, dien_thoai, dia_chi) 
                VALUES (:ho_ten, :email, :mat_khau, :dien_thoai, :dia_chi)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'ho_ten' => $ho_ten,
            'email' => $email,
            'mat_khau' => password_hash($mat_khau, PASSWORD_DEFAULT), // Mã hóa bảo mật
            'dien_thoai' => $dien_thoai,
            'dia_chi' => $dia_chi
        ]);
    }

    public function kiemTraDangNhap($email, $mat_khau) {
        $sql = "SELECT * FROM taikhoan WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mat_khau, $user['mat_khau'])) {
            return $user; // Đúng mật khẩu, trả về thông tin user
        }
        return false;
    }

    public function kiemTraEmailTonTai($email) {
        $sql = "SELECT COUNT(*) FROM taikhoan WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    public function kiemTraDienThoaiTonTai($dien_thoai) {
        $sql = "SELECT COUNT(*) FROM taikhoan WHERE dien_thoai = :dien_thoai";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dien_thoai' => $dien_thoai]);
        return $stmt->fetchColumn() > 0;
    }

    // Lấy thông tin user mới nhất từ DB
    public function layThongTinUser($id) {
        $sql = "SELECT * FROM taikhoan WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật thông tin cá nhân
    public function capNhatThongTin($id, $ho_ten, $dien_thoai, $dia_chi) {
        $sql = "UPDATE taikhoan SET ho_ten = :ho_ten, dien_thoai = :dien_thoai, dia_chi = :dia_chi WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'ho_ten' => $ho_ten,
            'dien_thoai' => $dien_thoai,
            'dia_chi' => $dia_chi,
            'id' => $id
        ]);
    }
}
?>