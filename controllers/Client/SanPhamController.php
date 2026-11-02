<?php
class SanPhamController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function hienThiDanhSach() {
    require_once __DIR__ . '/../../models/DanhMucModel.php';
    require_once __DIR__ . '/../../models/SanPhamModel.php';
    
    $danhMucModel = new DanhMucModel($this->conn);
    $sanPhamModel = new SanPhamModel($this->conn);
    
    // Nuôi menu ngang (đổi sang hàm cây danh mục ở Hạng mục 3 bên dưới)
    $ds_danhmuc = $danhMucModel->layCayDanhMuc(); 
    
    // Kiểm tra xem khách có đang thực hiện tìm kiếm hay không
    if (isset($_GET['tukhoa']) && trim($_GET['tukhoa']) !== '') {
        $keyword = trim($_GET['tukhoa']);
        $ds_sanpham = $sanPhamModel->timKiemSanPham($keyword);
        $tieu_de = "Kết quả tìm kiếm cho: '" . htmlspecialchars($keyword) . "'";
    } elseif (isset($_GET['tukhoa']) && trim($_GET['tukhoa']) === '') {
        // Khách hàng bấm tìm kiếm nhưng không nhập gì
        $ds_sanpham = $sanPhamModel->layTatCaSanPham(); 
        $tieu_de = "Vui lòng nhập từ khóa để tìm kiếm!";
    } elseif (isset($_GET['dong'])) {
        $dong = trim($_GET['dong']);
        $mapping = [
            'iphone15' => 'iphone 15', 'iphone14' => 'iphone 14', 'iphone13' => 'iphone 13', 'iphone11' => 'iphone 11',
            's25' => 's25', 'xiaomi16' => 'xiaomi 16', 'fold' => 'fold', 'oppo' => 'oppo',
            'macbookair' => 'macbook air', 'macbookpro' => 'macbook pro', 'macold' => 'macbook',
            'dellxps' => 'dell xps', 'thinkpad' => 'thinkpad', 'asusrog' => 'asus rog',
            'sonywh' => 'sony wh', 'airpodsmax' => 'airpods max', 'marshall' => 'marshall',
            'airpodspro' => 'airpods pro', 'sonywf' => 'sony wf', 'galaxybuds' => 'galaxy buds'
        ];
        $search = isset($mapping[$dong]) ? $mapping[$dong] : $dong;
        $ds_sanpham = $sanPhamModel->timKiemSanPham($search);
        $tieu_de = "Danh sách " . htmlspecialchars(ucwords($search));
        $search_placeholder_value = ucwords($search);
    } elseif (isset($_GET['danhmuc'])) {
        $danhmuc = trim($_GET['danhmuc']);
        $ds_sanpham = [];
        $search = "";
        
        if ($danhmuc == 'dienthoai') {
            $ds1 = $sanPhamModel->laySanPhamTheoDanhMucTen('Điện thoại');
            $ds2 = $sanPhamModel->laySanPhamTheoDanhMucTen('iPhone');
            $ds_sanpham = array_merge($ds1, $ds2);
            $search = "Điện Thoại";
        } elseif ($danhmuc == 'laptop') {
            $ds1 = $sanPhamModel->laySanPhamTheoDanhMucTen('Laptop');
            $ds2 = $sanPhamModel->laySanPhamTheoDanhMucTen('MacBook');
            $ds_sanpham = array_merge($ds1, $ds2);
            $search = "Laptop";
        } elseif ($danhmuc == 'tainghe') {
            $ds_sanpham = $sanPhamModel->laySanPhamTheoDanhMucTen('Tai Nghe');
            $search = "Tai Nghe";
        } else {
            $ds_sanpham = $sanPhamModel->laySanPhamTheoDanhMucTen($danhmuc);
            $search = $danhmuc;
        }

        // Loại bỏ trùng lặp (vì 1 sản phẩm có thể có danh mục chứa cả 2 từ khóa)
        $temp = [];
        foreach($ds_sanpham as $sp) {
            $temp[$sp['id']] = $sp;
        }
        $ds_sanpham = array_values($temp);

        $tieu_de = "Danh mục: " . htmlspecialchars($search);
        $search_placeholder_value = $search;
    } elseif (isset($_GET['danhmuc_id'])) {
        $danhmuc_id = intval($_GET['danhmuc_id']);
        $ds_sanpham = $sanPhamModel->laySanPhamTheoDanhMuc($danhmuc_id);
        
        // Find category name for placeholder
        $dm_name = "";
        foreach ($ds_danhmuc as $dm) {
            if ($dm['id'] == $danhmuc_id) { $dm_name = $dm['ten_danhmuc']; break; }
            if (isset($dm['sub'])) {
                foreach ($dm['sub'] as $child) {
                    if ($child['id'] == $danhmuc_id) { $dm_name = $child['ten_danhmuc']; break 2; }
                }
            }
        }
        $tieu_de = "Danh mục thiết bị"; 
        if ($dm_name) {
            $search_placeholder_value = $dm_name;
        } 
    } else {
        $ds_sanpham = $sanPhamModel->layTatCaSanPham(); 
        $tieu_de = "Bộ Sưu Tập Công Nghệ";
    }

    // Sau khi lấy danh sách sản phẩm, áp dụng bộ lọc giá và tình trạng nếu có
    if (isset($_GET['gia']) || isset($_GET['tinh_trang'])) {
        $gia_filters = isset($_GET['gia']) ? $_GET['gia'] : [];
        $tinh_trang_filters = isset($_GET['tinh_trang']) ? $_GET['tinh_trang'] : [];
        $ds_sanpham = $sanPhamModel->locSanPham($ds_sanpham, $gia_filters, $tinh_trang_filters);
    }

    // --- Sắp xếp (Sorting) ---
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    if ($sort === 'price_asc') {
        usort($ds_sanpham, function($a, $b) { return $a['gia'] <=> $b['gia']; });
    } elseif ($sort === 'price_desc') {
        usort($ds_sanpham, function($a, $b) { return $b['gia'] <=> $a['gia']; });
    }
    // Nếu 'newest', DB đã order by id DESC sẵn rồi

    // --- Phân trang (Pagination) ---
    $total_products = count($ds_sanpham);
    $limit = 12; // 12 sản phẩm mỗi trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $total_pages = ceil($total_products / $limit);
    $offset = ($page - 1) * $limit;

    // Cắt mảng để lấy sản phẩm của trang hiện tại
    $ds_sanpham_paginated = array_slice($ds_sanpham, $offset, $limit);
    
    // Gán lại biến để view sử dụng
    $ds_sanpham = $ds_sanpham_paginated;


    require_once __DIR__ . '/../../views/client/layouts/header_client.php';
    require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
    require_once __DIR__ . '/../../views/client/sanpham/danhsach_sanpham.php';
    require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }

    public function hienThiChiTiet() {
        require_once __DIR__ . '/../../models/DanhMucModel.php';
        require_once __DIR__ . '/../../models/SanPhamModel.php';
        
        $danhMucModel = new DanhMucModel($this->conn);
        $sanPhamModel = new SanPhamModel($this->conn);
        
        // Nuôi menu ngang đa cấp thả xuống
        $ds_danhmuc = $danhMucModel->layCayDanhMuc(); 
        
        // Hứng ID sản phẩm từ đường dẫn URL (index.php?action=chitiet&id=...)
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sanpham = $sanPhamModel->layChiTietSanPham($id);
            
            if ($sanpham) {
                // Fetch reviews
                $ds_danhgia = $sanPhamModel->layDanhGiaSanPham($id);
                // Calculate average rating
                $trung_binh_sao = 0;
                if(count($ds_danhgia) > 0) {
                    $tong_sao = 0;
                    foreach($ds_danhgia as $dg) $tong_sao += $dg['so_sao'];
                    $trung_binh_sao = round($tong_sao / count($ds_danhgia), 1);
                }

                $search_placeholder_value = $sanpham['ten_sanpham'];
                
                // Gọi các giao diện hiển thị cho khách hàng
                require_once __DIR__ . '/../../views/client/layouts/header_client.php';
                require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
                require_once __DIR__ . '/../../views/client/sanpham/chitiet_sanpham.php';
                require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
            } else {
                echo "<h2 style='text-align:center; margin-top:50px;'>Sản phẩm này không tồn tại hoặc đã bị ẩn!</h2>";
            }
        } else {
            echo "<h2 style='text-align:center; margin-top:50px;'>Không tìm thấy mã sản phẩm cần xem!</h2>";
        }
    }

    public function xuLyDanhGia() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
            require_once __DIR__ . '/../../models/SanPhamModel.php';
            $sanPhamModel = new SanPhamModel($this->conn);
            
            $sanpham_id = intval($_POST['sanpham_id']);
            $taikhoan_id = is_array($_SESSION['user']) ? $_SESSION['user']['id'] : 0; // Or whatever holds ID
            if(isset($_SESSION['user']['id'])) $taikhoan_id = $_SESSION['user']['id'];
            
            $so_sao = intval($_POST['so_sao']);
            $binh_luan = trim($_POST['binh_luan']);
            
            if ($sanpham_id > 0 && $taikhoan_id > 0 && $so_sao >= 1 && $so_sao <= 5) {
                $sanPhamModel->themDanhGia($sanpham_id, $taikhoan_id, $so_sao, $binh_luan);
            }
            
            header("Location: index.php?action=chitiet&id=" . $sanpham_id . "&msg=danhgia_thanhcong");
            exit();
        }
    }
}
?>