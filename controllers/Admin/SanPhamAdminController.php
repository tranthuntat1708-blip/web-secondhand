<?php
class SanPhamAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Hàm 1: Hiển thị danh sách sản phẩm (DB thật)
    public function hienThiDanhSach() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu"); exit();
        }

        $stmt = $this->conn->query("SELECT s.*, d.ten_danhmuc FROM sanpham s LEFT JOIN danhmuc d ON s.danhmuc_id = d.id ORDER BY s.id DESC");
        $ds_sp = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/admin/layouts/header_admin.php';
        include __DIR__ . '/../../views/admin/sanpham/danhsach_sanpham.php';
        include __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    // Hàm 2: Thêm sản phẩm (kết nối DB thật)
    public function themSanPham() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu"); exit();
        }

        $errors = [];
        // Lấy danh mục dạng cây
        require_once __DIR__ . '/../../models/DanhMucModel.php';
        $dmModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $dmModel->layCayDanhMuc();

        if (isset($_POST['btn_them'])) {
            $ten_sp = trim($_POST['ten_sanpham']);
            $danhmuc_id = intval($_POST['danhmuc_id']);
            $gia = intval($_POST['gia']);
            $gia_cu = !empty($_POST['gia_cu']) ? intval($_POST['gia_cu']) : null;
            $tinh_trang = trim($_POST['tinh_trang']);
            $mo_ta = trim($_POST['mo_ta']);
            $so_luong = max(0, intval($_POST['so_luong']));

            if (empty($ten_sp)) $errors['ten_sanpham'] = "Tên sản phẩm bắt buộc phải nhập!";
            if ($gia <= 0) $errors['gia'] = "Giá phải lớn hơn 0!";

            $ten_file_anh = 'default.jpg';
            if (isset($_FILES['anh_sp']) && $_FILES['anh_sp']['error'] == 0) {
                $duoi_file = strtolower(pathinfo($_FILES['anh_sp']['name'], PATHINFO_EXTENSION));
                $duoi_hop_le = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($duoi_file, $duoi_hop_le)) {
                    $errors['anh_sp'] = "Chỉ chấp nhận JPG, PNG, WEBP!";
                } else {
                    $ten_file_anh = time() . '_' . $_FILES['anh_sp']['name'];
                    $folder = __DIR__ . '/../../public/uploads/Anh_sanpham/';
                    move_uploaded_file($_FILES['anh_sp']['tmp_name'], $folder . $ten_file_anh);
                }
            }

            if (empty($errors)) {
                $sql = "INSERT INTO sanpham (danhmuc_id, ten_sanpham, gia, gia_cu, tinh_trang, hinh_anh, mo_ta, so_luong) VALUES (:dm, :ten, :gia, :gia_cu, :tt, :hinh, :mo_ta, :sl)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'dm' => $danhmuc_id,
                    'ten' => $ten_sp,
                    'gia' => $gia,
                    'gia_cu' => $gia_cu,
                    'tt' => $tinh_trang,
                    'hinh' => $ten_file_anh,
                    'mo_ta' => $mo_ta,
                    'sl' => $so_luong
                ]);
                header("Location: index.php?action=ds_sanpham&msg=Thêm sản phẩm thành công!");
                exit();
            }
        }

        include __DIR__ . '/../../views/admin/layouts/header_admin.php';
        include __DIR__ . '/../../views/admin/sanpham/them_sanpham.php';
        include __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    // Hàm 3: Sửa sản phẩm
    public function suaSanPham() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu"); exit();
        }

        $id = intval($_GET['id'] ?? 0);
        require_once __DIR__ . '/../../models/DanhMucModel.php';
        $dmModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $dmModel->layCayDanhMuc();
        
        // Lấy thông tin SP hiện tại
        $stmt = $this->conn->prepare("SELECT * FROM sanpham WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $sp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sp) { header("Location: index.php?action=ds_sanpham"); exit(); }

        $errors = [];

        if (isset($_POST['btn_sua'])) {
            $ten_sp = trim($_POST['ten_sanpham']);
            $danhmuc_id = intval($_POST['danhmuc_id']);
            $gia = intval($_POST['gia']);
            $gia_cu = !empty($_POST['gia_cu']) ? intval($_POST['gia_cu']) : null;
            $tinh_trang = trim($_POST['tinh_trang']);
            $mo_ta = trim($_POST['mo_ta']);
            $so_luong = max(0, intval($_POST['so_luong']));

            if (empty($ten_sp)) $errors['ten_sanpham'] = "Tên sản phẩm bắt buộc!";
            if ($gia <= 0) $errors['gia'] = "Giá phải lớn hơn 0!";

            $ten_file_anh = $sp['hinh_anh']; // Giữ ảnh cũ mặc định
            if (isset($_FILES['anh_sp']) && $_FILES['anh_sp']['error'] == 0) {
                $duoi_file = strtolower(pathinfo($_FILES['anh_sp']['name'], PATHINFO_EXTENSION));
                if (in_array($duoi_file, ['jpg','jpeg','png','webp'])) {
                    $ten_file_anh = time() . '_' . $_FILES['anh_sp']['name'];
                    $folder = __DIR__ . '/../../public/uploads/Anh_sanpham/';
                    move_uploaded_file($_FILES['anh_sp']['tmp_name'], $folder . $ten_file_anh);
                }
            }

            if (empty($errors)) {
                $sql = "UPDATE sanpham SET danhmuc_id=:dm, ten_sanpham=:ten, gia=:gia, gia_cu=:gia_cu, tinh_trang=:tt, hinh_anh=:hinh, mo_ta=:mo_ta, so_luong=:sl WHERE id=:id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'dm' => $danhmuc_id, 'ten' => $ten_sp, 'gia' => $gia,
                    'gia_cu' => $gia_cu, 'tt' => $tinh_trang, 'hinh' => $ten_file_anh, 'mo_ta' => $mo_ta, 'sl' => $so_luong, 'id' => $id
                ]);
                header("Location: index.php?action=ds_sanpham&msg=Cập nhật sản phẩm thành công!");
                exit();
            }
            // Cập nhật lại $sp cho view nếu có lỗi
            $sp['ten_sanpham'] = $ten_sp; $sp['danhmuc_id'] = $danhmuc_id;
            $sp['gia'] = $gia; $sp['gia_cu'] = $gia_cu; $sp['tinh_trang'] = $tinh_trang; $sp['mo_ta'] = $mo_ta;
        }

        include __DIR__ . '/../../views/admin/layouts/header_admin.php';
        include __DIR__ . '/../../views/admin/sanpham/sua_sanpham.php';
        include __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    // Hàm 4: Xóa sản phẩm (DB thật)
    public function xoaSanPham() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
            header("Location: index.php?action=trangchu"); exit();
        }
        if (isset($_GET['id']) && $_GET['id'] > 0) {
            $id = intval($_GET['id']);
            
            // Kiểm tra xem sản phẩm đã có trong chi tiết đơn hàng chưa
            $stmt_check = $this->conn->prepare("SELECT COUNT(*) as dem FROM chitiet_donhang WHERE sanpham_id = :id");
            $stmt_check->execute(['id' => $id]);
            $row = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($row['dem'] > 0) {
                header("Location: index.php?action=ds_sanpham&msg=" . urlencode("Không thể xóa sản phẩm này vì đã phát sinh đơn hàng!"));
                exit();
            }

            $stmt = $this->conn->prepare("DELETE FROM sanpham WHERE id = :id");
            $stmt->execute(['id' => $id]);
        }
        header("Location: index.php?action=ds_sanpham&msg=Đã xóa sản phẩm!");
        exit();
    }
}
?>