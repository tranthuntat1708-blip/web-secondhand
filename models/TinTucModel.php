<?php
class TinTucModel {
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
    }

    public function layTatCaTinTuc() {
        $sql = "SELECT * FROM tin_tuc ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function layChiTietTinTuc($id) {
        // Tăng lượt xem
        $sql_view = "UPDATE tin_tuc SET luot_xem = luot_xem + 1 WHERE id = :id";
        $stmt_view = $this->db->prepare($sql_view);
        $stmt_view->execute(['id' => $id]);

        $sql = "SELECT * FROM tin_tuc WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function themTinTuc($tieu_de, $hinh_anh, $noi_dung_ngan, $noi_dung) {
        $sql = "INSERT INTO tin_tuc (tieu_de, hinh_anh, noi_dung_ngan, noi_dung) VALUES (:td, :ha, :ndn, :nd)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'td' => $tieu_de,
            'ha' => $hinh_anh,
            'ndn' => $noi_dung_ngan,
            'nd' => $noi_dung
        ]);
    }

    public function suaTinTuc($id, $tieu_de, $hinh_anh, $noi_dung_ngan, $noi_dung) {
        if ($hinh_anh) {
            $sql = "UPDATE tin_tuc SET tieu_de=:td, hinh_anh=:ha, noi_dung_ngan=:ndn, noi_dung=:nd WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['td'=>$tieu_de, 'ha'=>$hinh_anh, 'ndn'=>$noi_dung_ngan, 'nd'=>$noi_dung, 'id'=>$id]);
        } else {
            $sql = "UPDATE tin_tuc SET tieu_de=:td, noi_dung_ngan=:ndn, noi_dung=:nd WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['td'=>$tieu_de, 'ndn'=>$noi_dung_ngan, 'nd'=>$noi_dung, 'id'=>$id]);
        }
    }

    public function xoaTinTuc($id) {
        $sql = "DELETE FROM tin_tuc WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
?>
