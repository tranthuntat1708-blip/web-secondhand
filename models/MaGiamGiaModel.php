<?php
class MaGiamGiaModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function layTatCaMaGiamGia() {
        $sql = "SELECT * FROM ma_giam_gia ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function themMaGiamGia($ma_code, $loai_giam, $muc_giam, $giam_toi_da, $ngay_het_han, $so_luong) {
        $sql = "INSERT INTO ma_giam_gia (ma_code, loai_giam, muc_giam, giam_toi_da, ngay_het_han, so_luong) 
                VALUES (:ma, :loai, :muc, :max, :ngay, :sl)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'ma' => strtoupper($ma_code),
            'loai' => $loai_giam,
            'muc' => $muc_giam,
            'max' => $giam_toi_da ?: null,
            'ngay' => $ngay_het_han,
            'sl' => $so_luong
        ]);
    }

    public function xoaMaGiamGia($id) {
        $sql = "DELETE FROM ma_giam_gia WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // Client function
    public function kiemTraMaGiamGia($ma_code) {
        $sql = "SELECT * FROM ma_giam_gia WHERE ma_code = :ma AND trang_thai = 1 AND ngay_het_han >= CURDATE() AND so_luong > 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ma' => strtoupper($ma_code)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function giamSoLuongMa($ma_code) {
        $sql = "UPDATE ma_giam_gia SET so_luong = so_luong - 1 WHERE ma_code = :ma AND so_luong > 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['ma' => strtoupper($ma_code)]);
    }

    public function hoanLaiSoLuongMa($ma_code) {
        if(empty($ma_code)) return false;
        $sql = "UPDATE ma_giam_gia SET so_luong = so_luong + 1 WHERE ma_code = :ma";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['ma' => strtoupper($ma_code)]);
    }
}
?>
