<?php
session_start();

// 1. Nhúng file kết nối CSDL
require_once __DIR__ . '/config/ketnoi_database.php';

// 2. Nhúng các Controller của Client
require_once __DIR__ . '/controllers/Client/TrangChuController.php';
require_once __DIR__ . '/controllers/Client/SanPhamController.php';
require_once __DIR__ . '/controllers/Client/GiohangController.php';
require_once __DIR__ . '/controllers/Client/TaiKhoanController.php';
require_once __DIR__ . '/controllers/Client/ChinhSachController.php';

// 3. Nhúng các Controller của Admin
require_once __DIR__ . '/controllers/Admin/TongQuanAdminController.php';
require_once __DIR__ . '/controllers/Admin/SanPhamAdminController.php';
require_once __DIR__ . '/controllers/Admin/DonHangAdminController.php';
require_once __DIR__ . '/controllers/Admin/KhachHangAdminController.php';
require_once __DIR__ . '/controllers/Admin/DoanhThuAdminController.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'trangchu';

switch ($action) {
    // ========================================================
    // PHẦN 1: ROUTER CHO CLIENT (KHÁCH HÀNG)
    // ========================================================
    case 'chinhsach':
        $csCtrl = new ChinhSachController($conn);
        $loai = isset($_GET['loai']) ? $_GET['loai'] : 'bao-mat';
        $csCtrl->hienThiChinhSach($loai);
        break;

    case 'trangchu':
        $trangChu = new TrangChuController($conn);
        $trangChu->hienThiTrangChu();
        break;

    case 'chitiet':
        $sanPhamCtrl = new SanPhamController($conn);
        $sanPhamCtrl->hienThiChiTiet();
        break;

    case 'danhgia_sp':
        $sanPhamCtrl = new SanPhamController($conn);
        $sanPhamCtrl->xuLyDanhGia();
        break;

    case 'sanpham':
        $sanPhamCtrl = new SanPhamController($conn);
        $sanPhamCtrl->hienThiDanhSach();
        break;

    case 'tintuc':
        require_once __DIR__ . '/controllers/Client/TinTucController.php';
        $ttCtrl = new TinTucController($conn);
        $ttCtrl->hienThiDanhSach();
        break;

    case 'chitiet_tintuc':
        require_once __DIR__ . '/controllers/Client/TinTucController.php';
        $ttCtrl = new TinTucController($conn);
        $ttCtrl->hienThiChiTiet();
        break;
    
    case 'giohang':
        $gioHangCtrl = new GioHangController($conn);
        if (isset($_GET['add_id'])) {
            $gioHangCtrl->themVaoGio();
        } elseif (isset($_GET['remove_id']) || isset($_GET['remove_ids'])) {
            $gioHangCtrl->xoaSpham();
        } else {
            $gioHangCtrl->hienThiGioHang();
        }
        break;

    case 'capnhat_giohang':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->capNhatSoLuong();
        break;

    case 'apdung_magiamgia':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->apDungMaGiamGia();
        break;

    case 'huydung_magiamgia':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->huyMaGiamGia();
        break;
    
    case 'taikhoan':
        $tkCtrl = new TaiKhoanController($conn);
        $tkCtrl->hienThiDangNhapKy();
        break;

    case 'xuly_dangky':
        $tkCtrl = new TaiKhoanController($conn);
        $tkCtrl->xuLyDangKy();
        break;

    case 'xuly_dangnhap':
        $tkCtrl = new TaiKhoanController($conn);
        $tkCtrl->xuLyDangNhap();
        break;

    case 'dangxuat':
        $tkCtrl = new TaiKhoanController($conn);
        $tkCtrl->dangXuat();
        break;

    case 'thong_tin':
        $tkCtrl = new TaiKhoanController($conn);
        $tkCtrl->hienThiThongTin();
        break;

    case 'capnhat_thongtin':
        $tkCtrl = new TaiKhoanController($conn);
        $tkCtrl->capNhatThongTin();
        break;

    case 'thanhtoan':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->hienThiThanhToan();
        break;

    case 'xuly_thanhtoan':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->xulyThanhToan();
        break;

    case 'dat_hang_thanh_cong':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->datHangThanhCong();
        break;

    case 'donhang':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->lichSuDonHang();
        break;

    case 'huy_don_hang':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->huyDonHang();
        break;

    case 'yeu_cau_tra_hang':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->yeuCauTraHang();
        break;

    case 'da_nhan_hang':
        $gioHangCtrl = new GioHangController($conn);
        $gioHangCtrl->daNhanHang();
        break;



    // ========================================================
    // PHẦN 2: ROUTER CHO ADMIN (QUẢN TRỊ VIÊN)
    // ========================================================
    
    // Trang tổng quan (Dashboard)
    case 'admin_dashboard':
        $tongQuanCtrl = new TongQuanAdminController($conn);
        $tongQuanCtrl->xemTongQuan(); 
        break;

    // Quản lý danh sách sản phẩm
    case 'ds_sanpham':
        $spAdminCtrl = new SanPhamAdminController($conn);
        $spAdminCtrl->hienThiDanhSach(); 
        break;

    // Giao diện Thêm và Xử lý Thêm Sản phẩm
    case 'them_sp':
    case 'them_sp_xuly':
        $spAdminCtrl = new SanPhamAdminController($conn);
        $spAdminCtrl->themSanPham(); 
        break;

    // Sửa sản phẩm
    case 'sua_sp':
        $spAdminCtrl = new SanPhamAdminController($conn);
        $spAdminCtrl->suaSanPham();
        break;

    // Xử lý Xóa Sản phẩm
    case 'xoa_sp':
        $spAdminCtrl = new SanPhamAdminController($conn);
        $spAdminCtrl->xoaSanPham(); 
        break;

    // Quản lý Đơn hàng (Admin)
    case 'admin_donhang':
        $dhAdminCtrl = new DonHangAdminController($conn);
        $dhAdminCtrl->danhSachDonHang();
        break;

    case 'admin_capnhat_dh':
        $dhAdminCtrl = new DonHangAdminController($conn);
        $dhAdminCtrl->capNhatTrangThai();
        break;

    // Quản lý Khách hàng (Admin)
    case 'admin_khachhang':
        $khCtrl = new KhachHangAdminController($conn);
        $khCtrl->danhSachKhachHang();
        break;

    // Quản lý Danh Mục Sản Phẩm (Admin)
    case 'admin_danhmuc':
        require_once __DIR__ . '/controllers/Admin/DanhMucAdminController.php';
        $dmAdminCtrl = new DanhMucAdminController($conn);
        $dmAdminCtrl->hienThiDanhSach();
        break;

    case 'admin_danhmuc_them':
        require_once __DIR__ . '/controllers/Admin/DanhMucAdminController.php';
        $dmAdminCtrl = new DanhMucAdminController($conn);
        $dmAdminCtrl->themDanhMuc();
        break;

    case 'admin_danhmuc_sua':
        require_once __DIR__ . '/controllers/Admin/DanhMucAdminController.php';
        $dmAdminCtrl = new DanhMucAdminController($conn);
        $dmAdminCtrl->suaDanhMuc();
        break;

    case 'admin_danhmuc_xoa':
        require_once __DIR__ . '/controllers/Admin/DanhMucAdminController.php';
        $dmAdminCtrl = new DanhMucAdminController($conn);
        $dmAdminCtrl->xoaDanhMuc();
        break;

    // Quản lý Mã Giảm Giá (Admin)
    case 'admin_magiamgia':
        require_once __DIR__ . '/controllers/Admin/MaGiamGiaAdminController.php';
        $mgAdminCtrl = new MaGiamGiaAdminController($conn);
        $mgAdminCtrl->hienThiDanhSach();
        break;

    case 'admin_magiamgia_them':
        require_once __DIR__ . '/controllers/Admin/MaGiamGiaAdminController.php';
        $mgAdminCtrl = new MaGiamGiaAdminController($conn);
        $mgAdminCtrl->themMaGiamGia();
        break;

    case 'admin_magiamgia_xoa':
        require_once __DIR__ . '/controllers/Admin/MaGiamGiaAdminController.php';
        $mgAdminCtrl = new MaGiamGiaAdminController($conn);
        $mgAdminCtrl->xoaMaGiamGia();
        break;

    // Quản lý Banner (Admin)
    case 'admin_banner':
        require_once __DIR__ . '/controllers/Admin/BannerAdminController.php';
        $bnAdminCtrl = new BannerAdminController($conn);
        $bnAdminCtrl->hienThiDanhSach();
        break;

    case 'admin_banner_them':
        require_once __DIR__ . '/controllers/Admin/BannerAdminController.php';
        $bnAdminCtrl = new BannerAdminController($conn);
        $bnAdminCtrl->themBanner();
        break;

    case 'admin_banner_xoa':
        require_once __DIR__ . '/controllers/Admin/BannerAdminController.php';
        $bnAdminCtrl = new BannerAdminController($conn);
        $bnAdminCtrl->xoaBanner();
        break;

    case 'admin_banner_thutu':
        require_once __DIR__ . '/controllers/Admin/BannerAdminController.php';
        $bnAdminCtrl = new BannerAdminController($conn);
        $bnAdminCtrl->capNhatThuTu();
        break;

    // Quản lý Tin Tức / Blog (Admin)
    case 'admin_tintuc':
        require_once __DIR__ . '/controllers/Admin/TinTucAdminController.php';
        $ttAdminCtrl = new TinTucAdminController($conn);
        $ttAdminCtrl->hienThiDanhSach();
        break;

    case 'admin_tintuc_them':
        require_once __DIR__ . '/controllers/Admin/TinTucAdminController.php';
        $ttAdminCtrl = new TinTucAdminController($conn);
        $ttAdminCtrl->themTinTuc();
        break;

    case 'admin_tintuc_sua':
        require_once __DIR__ . '/controllers/Admin/TinTucAdminController.php';
        $ttAdminCtrl = new TinTucAdminController($conn);
        $ttAdminCtrl->suaTinTuc();
        break;

    case 'admin_tintuc_xoa':
        require_once __DIR__ . '/controllers/Admin/TinTucAdminController.php';
        $ttAdminCtrl = new TinTucAdminController($conn);
        $ttAdminCtrl->xoaTinTuc();
        break;

    // Thống kê Doanh thu (Admin)
    case 'admin_doanhthu':
        $dtCtrl = new DoanhThuAdminController($conn);
        $dtCtrl->thongKeDoanhThu();
        break;


    default:
        echo "<h1 style='color:red; text-align:center; padding-top:50px;'>404 - Đường dẫn không tồn tại!</h1>";
        break;
}
?>