<?php
class BannerModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function layTatCaBanner() {
        $sql = "SELECT * FROM banner ORDER BY thu_tu ASC, id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function layBannerHienThi() {
        $sql = "SELECT * FROM banner WHERE trang_thai = 1 ORDER BY thu_tu ASC, id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function capNhatThuTu($id, $thu_tu) {
        $sql = "UPDATE banner SET thu_tu = :thu_tu WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':thu_tu', $thu_tu, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function themBanner($tieu_de, $hinh_anh, $link, $trang_thai) {
        $sql = "INSERT INTO banner (tieu_de, hinh_anh, link, trang_thai) 
                VALUES (:td, :ha, :link, :trang_thai)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'td' => $tieu_de,
            'ha' => $hinh_anh,
            'link' => $link,
            'trang_thai' => $trang_thai
        ]);
    }

    public function xoaBanner($id) {
        $sql = "DELETE FROM banner WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
?>
