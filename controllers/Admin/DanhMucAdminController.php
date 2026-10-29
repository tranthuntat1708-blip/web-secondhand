<?php
class DanhMucAdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiDanhSach() {
        require_once __DIR__ . '/../../models/DanhMucModel.php';
        $dmModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $dmModel->layTatCaDanhMuc();
        $danhmuc_cha = $dmModel->layDanhMucCha();

        require_once __DIR__ . '/../../views/admin/layouts/header_admin.php';
        require_once __DIR__ . '/../../views/admin/danhmuc/danhsach.php';
        require_once __DIR__ . '/../../views/admin/layouts/footer_admin.php';
    }

    public function themDanhMuc() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../models/DanhMucModel.php';
            $dmModel = new DanhMucModel($this->conn);
            
            $ten_danhmuc = trim($_POST['ten_danhmuc']);
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

            if (!empty($ten_danhmuc)) {
                $dmModel->themDanhMuc($ten_danhmuc, $parent_id);
                header("Location: index.php?action=admin_danhmuc&msg=them_thanhcong");
                exit();
            }
        }
    }

    public function suaDanhMuc() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../models/DanhMucModel.php';
            $dmModel = new DanhMucModel($this->conn);
            
            $id = intval($_POST['id']);
            $ten_danhmuc = trim($_POST['ten_danhmuc']);
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

            if (!empty($ten_danhmuc)) {
                $dmModel->suaDanhMuc($id, $ten_danhmuc, $parent_id);
                header("Location: index.php?action=admin_danhmuc&msg=sua_thanhcong");
                exit();
            }
        }
    }

    public function xoaDanhMuc() {
        if (isset($_GET['id'])) {
            require_once __DIR__ . '/../../models/DanhMucModel.php';
            $dmModel = new DanhMucModel($this->conn);
            
            try {
                $dmModel->xoaDanhMuc(intval($_GET['id']));
                header("Location: index.php?action=admin_danhmuc&msg=xoa_thanhcong");
            } catch (\Exception $e) {
                // Lỗi từ kiểm tra an toàn trong model (có danh mục con hoặc sản phẩm)
                $lyDo = $e->getMessage();
                if ($lyDo === 'danh_muc_con') {
                    header("Location: index.php?action=admin_danhmuc&msg=loi_xoa_con");
                } elseif ($lyDo === 'co_san_pham') {
                    header("Location: index.php?action=admin_danhmuc&msg=loi_xoa_sp");
                } else {
                    // Lỗi khóa ngoại DB hoặc lỗi khác
                    header("Location: index.php?action=admin_danhmuc&msg=loi_xoa");
                }
            }
            exit();
        }
    }
}
?>
