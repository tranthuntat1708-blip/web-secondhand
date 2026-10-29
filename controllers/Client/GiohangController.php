<?php
require_once __DIR__ . '/../../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class GioHangController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        // Nếu trình duyệt chưa có giỏ hàng, tự động tạo một giỏ trống
        if (!isset($_SESSION['giohang'])) {
            $_SESSION['giohang'] = [];
        }
    }

    public function themVaoGio() {
        $id = isset($_GET['add_id']) ? intval($_GET['add_id']) : 0;
        
        if ($id > 0) {
            require_once __DIR__ . '/../../models/SanPhamModel.php';
            $sanPhamModel = new SanPhamModel($this->conn);
            $sp = $sanPhamModel->layChiTietSanPham($id);

            if ($sp) {
                // Kiểm tra số lượng đã mua trong tháng nếu đã đăng nhập
                $da_mua_trong_thang = 0;
                if (isset($_SESSION['user'])) {
                    require_once __DIR__ . '/../../models/DonHangModel.php';
                    $donHangModel = new DonHangModel($this->conn);
                    $da_mua_trong_thang = $donHangModel->kiemTraSoLuongDaMuaTrongThang($_SESSION['user']['id'], $id);
                }

                // Giới hạn = min(Tồn kho, 3 - đã mua)
                $max_allowed = min($sp['so_luong'], 3 - $da_mua_trong_thang);

                if ($max_allowed <= 0) {
                    $msg = ($sp['so_luong'] <= 0) ? 'het_hang' : 'vuot_qua_gioi_han';
                    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php?action=trangchu';
                    $referer = preg_replace('/([&?])msg=[^&]*&?/', '$1', $referer);
                    $referer = rtrim($referer, '?&');
                    $separator = (strpos($referer, '?') !== false) ? '&' : '?';
                    $referer .= $separator . 'msg=' . $msg;
                    header("Location: " . $referer);
                    exit();
                }

                // Nếu máy này đã có trong giỏ, tăng số lượng lên 1 (kiểm tra không vượt max_allowed)
                if (isset($_SESSION['giohang'][$id])) {
                    if ($_SESSION['giohang'][$id]['so_luong'] < $max_allowed) {
                        $_SESSION['giohang'][$id]['so_luong'] += 1;
                    } else {
                        // Vượt giới hạn khi bấm thêm
                        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php?action=trangchu';
                        $referer = preg_replace('/([&?])msg=[^&]*&?/', '$1', $referer);
                        $referer = rtrim($referer, '?&');
                        $separator = (strpos($referer, '?') !== false) ? '&' : '?';
                        $referer .= $separator . 'msg=vuot_qua_gioi_han';
                        header("Location: " . $referer);
                        exit();
                    }
                } else {
                    // Nếu chưa có, thêm mới thông tin máy vào giỏ (kèm hình ảnh)
                    $_SESSION['giohang'][$id] = [
                        'ten_sanpham' => $sp['ten_sanpham'],
                        'gia' => $sp['gia'],
                        'hinh_anh' => $sp['hinh_anh'],
                        'so_luong' => 1,
                        'ton_kho' => $sp['so_luong']
                    ];
                }
            }
        }
        // Thêm xong thì quay lại trang trước đó kèm theo cờ thông báo
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php?action=trangchu';
        
        // Tránh trùng lặp tham số msg
        $referer = preg_replace('/([&?])msg=[^&]*&?/', '$1', $referer);
        $referer = rtrim($referer, '?&');
        
        $separator = (strpos($referer, '?') !== false) ? '&' : '?';
        $referer .= $separator . 'msg=added_to_cart';

        header("Location: " . $referer);
        exit();
    }

   public function hienThiGioHang() {
        // BỔ SUNG: Lấy danh sách danh mục từ Database để truyền vào menu_ngang
        require_once __DIR__ . '/../../models/DanhMucModel.php';
        require_once __DIR__ . '/../../models/DonHangModel.php';
        require_once __DIR__ . '/../../models/SanPhamModel.php';
        $danhMucModel = new DanhMucModel($this->conn);
        $donHangModel = new DonHangModel($this->conn);
        $sanPhamModel = new SanPhamModel($this->conn);
        $ds_danhmuc = $danhMucModel->layCayDanhMuc();

        // Chuẩn bị max_allowed cho view
        if (isset($_SESSION['giohang'])) {
            foreach ($_SESSION['giohang'] as $id => $item) {
                $sp = $sanPhamModel->layChiTietSanPham($id);
                $ton_kho = $sp ? $sp['so_luong'] : 0;
                $da_mua_trong_thang = 0;
                if (isset($_SESSION['user'])) {
                    $da_mua_trong_thang = $donHangModel->kiemTraSoLuongDaMuaTrongThang($_SESSION['user']['id'], $id);
                }
                $_SESSION['giohang'][$id]['max_allowed'] = min($ton_kho, 3 - $da_mua_trong_thang);
            }
        }

        // Sau khi đã có $ds_danhmuc thì mới gọi các giao diện bên dưới
        require_once __DIR__ . '/../../views/client/layouts/header_client.php';
        require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
        require_once __DIR__ . '/../../views/client/giohang/trang_giohang.php';
        require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }
    
    public function xoaSpham() {
        // Xóa nhiều sản phẩm (bulk delete)
        if (isset($_GET['remove_ids']) && is_array($_GET['remove_ids'])) {
            foreach ($_GET['remove_ids'] as $rid) {
                $rid = intval($rid);
                if ($rid > 0 && isset($_SESSION['giohang'][$rid])) {
                    unset($_SESSION['giohang'][$rid]);
                }
            }
        }
        // Xóa một sản phẩm
        $id = isset($_GET['remove_id']) ? intval($_GET['remove_id']) : 0;
        if ($id > 0 && isset($_SESSION['giohang'][$id])) {
            unset($_SESSION['giohang'][$id]); // Hủy sản phẩm khỏi Session
        }
        header("Location: index.php?action=giohang");
        exit();
    }

    // Cập nhật số lượng sản phẩm trong giỏ hàng
    public function capNhatSoLuong() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantities'])) {
            require_once __DIR__ . '/../../models/SanPhamModel.php';
            require_once __DIR__ . '/../../models/DonHangModel.php';
            $spModel = new SanPhamModel($this->conn);
            $donHangModel = new DonHangModel($this->conn);

            foreach ($_POST['quantities'] as $id => $so_luong) {
                $id = intval($id);
                $so_luong = intval($so_luong);

                if (isset($_SESSION['giohang'][$id])) {
                    // Lấy tồn kho thực tế từ CSDL
                    $sp = $spModel->layChiTietSanPham($id);
                    $ton_kho = $sp ? $sp['so_luong'] : 0;

                    $da_mua_trong_thang = 0;
                    if (isset($_SESSION['user'])) {
                        $da_mua_trong_thang = $donHangModel->kiemTraSoLuongDaMuaTrongThang($_SESSION['user']['id'], $id);
                    }

                    $max_allowed = min($ton_kho, 3 - $da_mua_trong_thang);

                    if ($so_luong <= 0) {
                        unset($_SESSION['giohang'][$id]);
                    } elseif ($so_luong > $max_allowed) {
                        $_SESSION['giohang'][$id]['so_luong'] = max(1, $max_allowed);
                        if ($ton_kho < 3 - $da_mua_trong_thang) {
                            $_SESSION['cart_qty_msg'] = 'Số lượng máy không đủ! Sản phẩm "' . $_SESSION['giohang'][$id]['ten_sanpham'] . '" chỉ còn ' . $ton_kho . ' máy.';
                        } else {
                            $_SESSION['cart_qty_msg'] = 'Bạn chỉ được mua tối đa 3 sản phẩm "' . $_SESSION['giohang'][$id]['ten_sanpham'] . '" trong 1 tháng. Bạn đã mua ' . $da_mua_trong_thang . ' máy.';
                        }
                    } else {
                        $_SESSION['giohang'][$id]['so_luong'] = $so_luong;
                    }
                }
            }
        }
        
        if (isset($_POST['btn_checkout'])) {
            header("Location: index.php?action=thanhtoan");
        } else {
            header("Location: index.php?action=giohang");
        }
        exit();
    }

    public function hienThiThanhToan() {
        // Nếu chưa đăng nhập, chặn lại bắt đăng nhập/đăng ký
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=taikhoan&msg=⚠️ Vui lòng Đăng nhập hoặc Tạo tài khoản mới để tiến hành đặt hàng!");
            exit();
        }

        if (empty($_SESSION['giohang'])) {
            header("Location: index.php?action=trangchu");
            exit();
        }

        // ===== Tính lại tổng tiền chính xác từ giỏ hàng hiện tại =====
        $tong_tien_goc = 0;
        foreach ($_SESSION['giohang'] as $item) {
            $tong_tien_goc += $item['gia'] * $item['so_luong'];
        }

        $giam_gia = 0;
        if (isset($_SESSION['coupon'])) {
            // Kiểm tra lại coupon còn hợp lệ không trước khi áp dụng
            require_once __DIR__ . '/../../models/MaGiamGiaModel.php';
            $mgModel = new MaGiamGiaModel($this->conn);
            $couponCheck = $mgModel->kiemTraMaGiamGia($_SESSION['coupon']['ma_code']);
            if (!$couponCheck) {
                // Coupon hết hạn/hết lượt: xóa khỏi session
                unset($_SESSION['coupon']);
            } else {
                $c = $_SESSION['coupon'];
                if ($c['loai_giam'] === 'phan_tram') {
                    $giam_gia = ($tong_tien_goc * $c['muc_giam']) / 100;
                    if ($c['giam_toi_da'] > 0 && $giam_gia > $c['giam_toi_da']) {
                        $giam_gia = $c['giam_toi_da'];
                    }
                } else {
                    $giam_gia = $c['muc_giam'];
                }
                if ($giam_gia > $tong_tien_goc) $giam_gia = $tong_tien_goc;
            }
        }

        $_SESSION['cart_discount'] = $giam_gia;
        $_SESSION['cart_total']    = $tong_tien_goc - $giam_gia;
        // ===== End tính lại tổng =====

        // Lấy thông tin user để pre-fill form
        $user = $_SESSION['user'];

        require_once __DIR__ . '/../../models/DanhMucModel.php';
        $danhMucModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $danhMucModel->layCayDanhMuc();

        require_once __DIR__ . '/../../views/client/layouts/header_client.php';
        require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
        require_once __DIR__ . '/../../views/client/giohang/thanhtoan.php';
        require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }

    public function xulyThanhToan() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user'])) {
            // Chặn Admin tạo đơn hàng ở backend
            if ($_SESSION['user']['vai_tro'] === 'admin' || $_SESSION['user']['vai_tro'] == 1) {
                header("Location: index.php?action=trangchu&msg=admin_khong_mua_hang");
                exit();
            }
            require_once __DIR__ . '/../../models/DonHangModel.php';
            require_once __DIR__ . '/../../models/SanPhamModel.php';
            $donHangModel = new DonHangModel($this->conn);
            $sanPhamModel = new SanPhamModel($this->conn);

            // Kiểm tra Race Condition: Tồn kho và Giới hạn 3 cái/tháng
            foreach ($_SESSION['giohang'] as $sp_id => $item) {
                // Kiểm tra tồn kho mới nhất
                $sp = $sanPhamModel->layChiTietSanPham($sp_id);
                $ton_kho_thuc_te = $sp ? $sp['so_luong'] : 0;

                // Kiểm tra đã mua trong tháng
                $da_mua_trong_thang = $donHangModel->kiemTraSoLuongDaMuaTrongThang($_SESSION['user']['id'], $sp_id);
                $max_allowed = 3 - $da_mua_trong_thang;

                if ($ton_kho_thuc_te < $item['so_luong']) {
                    $_SESSION['cart_qty_msg'] = 'Sản phẩm "' . $item['ten_sanpham'] . '" đã được người khác mua hết hoặc không đủ số lượng!';
                    header("Location: index.php?action=giohang");
                    exit();
                }

                if ($item['so_luong'] > $max_allowed) {
                    $_SESSION['cart_qty_msg'] = 'Bạn chỉ được mua tối đa 3 sản phẩm "' . $item['ten_sanpham'] . '" trong 1 tháng. Bạn đã mua ' . $da_mua_trong_thang . ' máy.';
                    header("Location: index.php?action=giohang");
                    exit();
                }
            }

            $tong_tien_goc = 0;
            foreach ($_SESSION['giohang'] as $item) {
                $tong_tien_goc += $item['gia'] * $item['so_luong'];
            }

            // Tính lại giảm giá chính xác từ giỏ hàng thực tế (không dùng session stale)
            $giam_gia_tt = 0;
            if (isset($_SESSION['coupon'])) {
                $c = $_SESSION['coupon'];
                if ($c['loai_giam'] === 'phan_tram') {
                    $giam_gia_tt = ($tong_tien_goc * $c['muc_giam']) / 100;
                    if ($c['giam_toi_da'] > 0 && $giam_gia_tt > $c['giam_toi_da']) {
                        $giam_gia_tt = $c['giam_toi_da'];
                    }
                } else {
                    $giam_gia_tt = $c['muc_giam'];
                }
                if ($giam_gia_tt > $tong_tien_goc) $giam_gia_tt = $tong_tien_goc;
            }
            $tong_tien = $tong_tien_goc - $giam_gia_tt;

            try {
                $ma_giam_gia_su_dung = isset($_SESSION['coupon']) ? $_SESSION['coupon']['ma_code'] : null;

                $donhang_id = $donHangModel->taoDonHang(
                    $_SESSION['user']['id'],
                    $_POST['ten_nguoi_nhan'],
                    $_POST['dien_thoai_nhan'],
                    $_POST['dia_chi_nhan'],
                    $tong_tien,
                    $_POST['phuong_thuc_tt'],
                    $_SESSION['giohang'],
                    $ma_giam_gia_su_dung
                );

                if ($donhang_id) {
                    // Trừ số lượng mã giảm giá nếu có
                    if(isset($_SESSION['coupon'])) {
                        require_once __DIR__ . '/../../models/MaGiamGiaModel.php';
                        $mgModel = new MaGiamGiaModel($this->conn);
                        $mgModel->giamSoLuongMa($_SESSION['coupon']['ma_code']);
                    }

                    // Trừ số lượng tồn kho sản phẩm
                    foreach ($_SESSION['giohang'] as $sp_id => $item) {
                        $stmt_stock = $this->conn->prepare("UPDATE sanpham SET so_luong = so_luong - :sl WHERE id = :id AND so_luong >= :sl");
                        $stmt_stock->execute(['sl' => $item['so_luong'], 'id' => $sp_id]);
                    }

                    // Gửi email hóa đơn
                    $this->guiEmailHoaDon(
                        $_SESSION['user']['email'],
                        $donhang_id,
                        $_POST['ten_nguoi_nhan'],
                        $_POST['dien_thoai_nhan'],
                        $_POST['dia_chi_nhan'],
                        $tong_tien,
                        $_POST['phuong_thuc_tt'],
                        $_SESSION['giohang']
                    );

                    unset($_SESSION['giohang']); // Xóa sạch giỏ hàng sau khi mua thành công
                    unset($_SESSION['coupon']);
                    unset($_SESSION['cart_total']);
                    unset($_SESSION['cart_discount']);
                    
                    header("Location: index.php?action=dat_hang_thanh_cong&dh_id=" . $donhang_id);
                    exit();
                }
            } catch (PDOException $e) {
                header("Location: index.php?action=thanhtoan&msg=Đã xảy ra lỗi khi đặt hàng!");
                exit();
            }
        }
    }

    // Gửi email hóa đơn đặt hàng
    private function guiEmailHoaDon($email, $donhang_id, $ten_nhan, $sdt_nhan, $diachi_nhan, $tong_tien, $phuong_thuc, $gio_hang) {
        $ma_dh = '#DH' . str_pad($donhang_id, 6, '0', STR_PAD_LEFT);
        
        // Tên phương thức
        $pt_text = 'COD - Thanh toán khi nhận hàng';
        if ($phuong_thuc === 'ChuyenKhoan') $pt_text = 'Chuyển khoản ngân hàng';
        if ($phuong_thuc === 'MoMo') $pt_text = 'Ví điện tử MoMo';

        // Tạo danh sách sản phẩm HTML
        $sp_html = '';
        foreach ($gio_hang as $item) {
            $thanh_tien = number_format($item['gia'] * $item['so_luong'], 0, ',', '.');
            $don_gia = number_format($item['gia'], 0, ',', '.');
            $sp_html .= '
            <tr>
                <td style="padding: 12px; border-bottom: 1px solid #f1f5f9;">' . htmlspecialchars($item['ten_sanpham']) . '</td>
                <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: center;">' . $item['so_luong'] . '</td>
                <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: right;">' . $don_gia . 'đ</td>
                <td style="padding: 12px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 700;">' . $thanh_tien . 'đ</td>
            </tr>';
        }

        $tong_format = number_format($tong_tien, 0, ',', '.');
        $ngay = date('d/m/Y H:i');

        $subject = "Chợ Đồ Cũ - Xác nhận đơn hàng $ma_dh";
        
        $body = '
        <html>
        <body style="font-family: Arial, sans-serif; background: #f8fafc; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                
                <div style="background: #0f172a; padding: 30px; text-align: center;">
                    <h1 style="color: #ffffff; margin: 0; font-size: 1.5rem;">CHỢ ĐỒ CŨ</h1>
                    <p style="color: #94a3b8; margin: 8px 0 0; font-size: 0.9rem;">Xác nhận đơn hàng thành công</p>
                </div>

                <div style="padding: 30px;">
                    <div style="background: #ecfdf5; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px;">
                        <p style="color: #059669; font-weight: 700; font-size: 1.1rem; margin: 0;">✅ Đặt hàng thành công!</p>
                        <p style="color: #064e3b; font-size: 1.3rem; font-weight: 800; margin: 8px 0 0;">' . $ma_dh . '</p>
                        <p style="color: #64748b; font-size: 0.85rem; margin: 4px 0 0;">Ngày đặt: ' . $ngay . '</p>
                    </div>

                    <h3 style="color: #0f172a; margin-bottom: 12px; font-size: 0.95rem;">📋 Thông tin giao hàng</h3>
                    <table style="width: 100%; font-size: 0.9rem; color: #334155; margin-bottom: 24px;">
                        <tr><td style="padding: 6px 0; color: #64748b;">Người nhận:</td><td style="padding: 6px 0; font-weight: 700;">' . htmlspecialchars($ten_nhan) . '</td></tr>
                        <tr><td style="padding: 6px 0; color: #64748b;">Số điện thoại:</td><td style="padding: 6px 0; font-weight: 700;">' . htmlspecialchars($sdt_nhan) . '</td></tr>
                        <tr><td style="padding: 6px 0; color: #64748b;">Địa chỉ:</td><td style="padding: 6px 0; font-weight: 700;">' . htmlspecialchars($diachi_nhan) . '</td></tr>
                        <tr><td style="padding: 6px 0; color: #64748b;">Thanh toán:</td><td style="padding: 6px 0; font-weight: 700;">' . $pt_text . '</td></tr>
                    </table>

                    <h3 style="color: #0f172a; margin-bottom: 12px; font-size: 0.95rem;">📦 Chi tiết đơn hàng</h3>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 10px 12px; text-align: left; color: #64748b; font-weight: 700;">Sản phẩm</th>
                                <th style="padding: 10px 12px; text-align: center; color: #64748b; font-weight: 700;">SL</th>
                                <th style="padding: 10px 12px; text-align: right; color: #64748b; font-weight: 700;">Đơn giá</th>
                                <th style="padding: 10px 12px; text-align: right; color: #64748b; font-weight: 700;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>' . $sp_html . '</tbody>
                    </table>

                    <div style="margin-top: 16px; padding-top: 16px; border-top: 2px solid #f1f5f9; text-align: right;">
                        <span style="font-size: 1rem; color: #64748b; font-weight: 600;">Tổng thanh toán: </span>
                        <span style="font-size: 1.4rem; color: #dc2626; font-weight: 800;">' . $tong_format . 'đ</span>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;">
                    <p style="color: #94a3b8; font-size: 0.8rem; margin: 0;">Cảm ơn bạn đã mua sắm tại Chợ Đồ Cũ!</p>
                    <p style="color: #cbd5e1; font-size: 0.75rem; margin: 4px 0 0;">Email này được gửi tự động, vui lòng không trả lời.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail = new PHPMailer(true);
        try {
            // Cấu hình SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            // TODO: Bạn cần nhập Email và App Password của bạn vào đây
            $mail->Username   = 'tbaotoan0@gmail.com'; 
            $mail->Password   = 'dksh btsj sgbk yhby'; // Mật khẩu ứng dụng (16 ký tự)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->CharSet = 'UTF-8';
            $mail->setFrom('tbaotoan0@gmail.com', 'Chợ Đồ Cũ');
            $mail->addReplyTo('tbaotoan0@gmail.com', 'Chợ Đồ Cũ');
            $mail->addAddress($email, $ten_nhan);

            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
        } catch (Exception $e) {
            // Có thể ghi log lỗi gửi mail nếu cần: $mail->ErrorInfo
        }
    }

    // Trang thông báo đặt hàng thành công
    public function datHangThanhCong() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=trangchu");
            exit();
        }

        $donhang_id = isset($_GET['dh_id']) ? intval($_GET['dh_id']) : 0;

        require_once __DIR__ . '/../../models/DanhMucModel.php';
        $danhMucModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $danhMucModel->layCayDanhMuc();

        require_once __DIR__ . '/../../views/client/layouts/header_client.php';
        require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
        require_once __DIR__ . '/../../views/client/giohang/thanh_cong.php';
        require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }

    // Trang lịch sử đơn hàng cho khách hàng
    public function lichSuDonHang() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=taikhoan&msg=Vui lòng đăng nhập để xem đơn hàng!");
            exit();
        }

        require_once __DIR__ . '/../../models/DonHangModel.php';
        $donHangModel = new DonHangModel($this->conn);
        $ds_donhang_all = $donHangModel->layDonHangTheoUser($_SESSION['user']['id']);
        
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'tat_ca';
        $ds_donhang = [];
        
        foreach($ds_donhang_all as $dh) {
            $tt = $dh['trang_thai'];
            if($tab == 'tat_ca') {
                $ds_donhang[] = $dh;
            } elseif($tab == 'dang_giao' && (strpos($tt, 'giao') !== false || strpos($tt, 'Đang giao') !== false)) {
                $ds_donhang[] = $dh;
            } elseif($tab == 'da_giao' && strpos($tt, 'Hoàn thành') !== false) {
                $ds_donhang[] = $dh;
            } elseif($tab == 'tra_hang' && (strpos($tt, 'Yêu cầu trả hàng') !== false || strpos($tt, 'Hoàn trả') !== false)) {
                $ds_donhang[] = $dh;
            } elseif($tab == 'cho_xu_ly' && (strpos($tt, 'Chờ xử lý') !== false || strpos($tt, 'xác nhận') !== false)) {
                $ds_donhang[] = $dh;
            } elseif($tab == 'da_huy' && (strpos($tt, 'Hủy') !== false || strpos($tt, 'hủy') !== false || strpos($tt, 'không nhận') !== false)) {
                $ds_donhang[] = $dh;
            }
        }
        
        // Bổ sung chi tiết sản phẩm cho từng đơn hàng
        if (!empty($ds_donhang)) {
            foreach ($ds_donhang as &$dh) {
                $dh['chi_tiet'] = $donHangModel->layChiTietDonHang($dh['id']);
            }
            unset($dh); // Tránh lỗi reference cho vòng lặp ở View
        }

        require_once __DIR__ . '/../../models/DanhMucModel.php';
        $danhMucModel = new DanhMucModel($this->conn);
        $ds_danhmuc = $danhMucModel->layCayDanhMuc();

        require_once __DIR__ . '/../../views/client/layouts/header_client.php';
        require_once __DIR__ . '/../../views/client/layouts/menu_ngang.php';
        require_once __DIR__ . '/../../views/client/giohang/donhang_user.php';
        require_once __DIR__ . '/../../views/client/layouts/footer_client.php';
    }

    public function apDungMaGiamGia() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ma_code = trim($_POST['ma_code']);
            require_once __DIR__ . '/../../models/MaGiamGiaModel.php';
            $mgModel = new MaGiamGiaModel($this->conn);
            $coupon = $mgModel->kiemTraMaGiamGia($ma_code);

            if ($coupon) {
                $_SESSION['coupon'] = $coupon;
                $_SESSION['coupon_msg'] = ['type' => 'success', 'text' => 'Áp dụng mã giảm giá thành công!'];
            } else {
                // Xóa coupon cũ nếu mã nhập mới không hợp lệ
                unset($_SESSION['coupon']);
                unset($_SESSION['cart_total']);
                unset($_SESSION['cart_discount']);
                $_SESSION['coupon_msg'] = ['type' => 'error', 'text' => 'Mã giảm giá không hợp lệ, đã hết hạn hoặc hết lượt sử dụng!'];
            }
            header("Location: index.php?action=giohang");
            exit();
        }
    }

    public function huyMaGiamGia() {
        if(isset($_SESSION['coupon'])) {
            unset($_SESSION['coupon']);
            unset($_SESSION['cart_total']);
            unset($_SESSION['cart_discount']);
        }
        header("Location: index.php?action=giohang");
        exit();
    }

    public function huyDonHang() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=taikhoan");
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donhang_id'])) {
            $donhang_id = intval($_POST['donhang_id']);
            require_once __DIR__ . '/../../models/DonHangModel.php';
            $donHangModel = new DonHangModel($this->conn);
            
            // Lấy danh sách đơn hàng của user để đảm bảo họ chỉ hủy đơn của mình
            $ds_donhang = $donHangModel->layDonHangTheoUser($_SESSION['user']['id']);
            $hop_le = false;
            foreach($ds_donhang as $dh) {
                if($dh['id'] == $donhang_id) {
                    $hop_le = true;
                    // Kiểm tra trạng thái và thời gian 24h
                    $trang_thai = $dh['trang_thai'];
                    $ngay_dat = strtotime($dh['ngay_dat']);
                    $hien_tai = time();
                    $gio_da_qua = ($hien_tai - $ngay_dat) / 3600;
                    
                    if ((strpos($trang_thai, 'Chờ xử lý') !== false || strpos($trang_thai, 'Chờ xác nhận') !== false) && $gio_da_qua <= 24) {
                        $donHangModel->huyDonHang($donhang_id);
                        $_SESSION['thong_bao_donhang'] = "Đã hủy đơn hàng thành công!";
                    } else {
                        $_SESSION['thong_bao_donhang'] = "Đơn hàng không đủ điều kiện để hủy (quá 24h hoặc đang được giao).";
                    }
                    break;
                }
            }
            if(!$hop_le) {
                $_SESSION['thong_bao_donhang'] = "Đơn hàng không tồn tại hoặc không thuộc về bạn.";
            }
            header("Location: index.php?action=donhang");
            exit();
        }
    }

    public function yeuCauTraHang() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=taikhoan");
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donhang_id'])) {
            $donhang_id = intval($_POST['donhang_id']);
            require_once __DIR__ . '/../../models/DonHangModel.php';
            $donHangModel = new DonHangModel($this->conn);
            
            $ds_donhang = $donHangModel->layDonHangTheoUser($_SESSION['user']['id']);
            $hop_le = false;
            foreach($ds_donhang as $dh) {
                if($dh['id'] == $donhang_id) {
                    $hop_le = true;
                    $ngay_dat = strtotime($dh['ngay_dat']);
                    $hien_tai = time();
                    $ngay_da_qua = ($hien_tai - $ngay_dat) / (24 * 3600);

                    if (strpos($dh['trang_thai'], 'Hoàn thành') !== false && $ngay_da_qua <= 7) {
                        // --- Lấy thông tin ngân hàng ---
                        $ten_ngan_hang    = trim($_POST['ten_ngan_hang'] ?? '');
                        $so_tai_khoan     = trim($_POST['so_tai_khoan'] ?? '');
                        $ten_chu_tk       = trim($_POST['ten_chu_tk'] ?? '');
                        $sdt_tra_hang     = trim($_POST['sdt_tra_hang'] ?? '');
                        $dia_chi_tra_hang = trim($_POST['dia_chi_tra_hang'] ?? '');
                        $ly_do            = trim($_POST['ly_do_trahang'] ?? '');

                        $thongtin_nganhang = "Ngân hàng: $ten_ngan_hang | STK: $so_tai_khoan | Chủ TK: $ten_chu_tk";
                        $ghi_chu = "Yêu cầu trả hàng. Địa chỉ lấy hàng: $dia_chi_tra_hang - SĐT: $sdt_tra_hang";

                        // --- Xử lý upload NHIỀU file minh chứng ---
                        if (!isset($_FILES['minhchung_files']) || empty($_FILES['minhchung_files']['name'][0])) {
                            $_SESSION['thong_bao_donhang'] = "Lỗi: Bạn phải tải lên ít nhất 1 ảnh hoặc video minh chứng.";
                            header("Location: index.php?action=donhang");
                            exit();
                        }

                        $allowed_exts  = ['jpg', 'jpeg', 'png', 'mp4'];
                        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'video/mp4'];
                        $max_size      = 10 * 1024 * 1024; // 10MB
                        $upload_dir    = __DIR__ . '/../../public/uploads/minhchung/';
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                        $saved_filenames = [];
                        $files = $_FILES['minhchung_files'];
                        $total_files = count($files['name']);

                        for ($i = 0; $i < $total_files; $i++) {
                            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                            $file_ext  = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                            $file_type = $files['type'][$i];
                            $file_size = $files['size'][$i];

                            if (!in_array($file_ext, $allowed_exts) || !in_array($file_type, $allowed_types)) {
                                $_SESSION['thong_bao_donhang'] = "Lỗi: File \"" . $files['name'][$i] . "\" không hợp lệ. Chỉ chấp nhận .jpg, .png, .mp4.";
                                header("Location: index.php?action=donhang");
                                exit();
                            }

                            if ($file_size > $max_size) {
                                $_SESSION['thong_bao_donhang'] = "Lỗi: File \"" . $files['name'][$i] . "\" vượt quá 10MB.";
                                header("Location: index.php?action=donhang");
                                exit();
                            }

                            $new_filename = 'trahang_' . $donhang_id . '_' . time() . '_' . $i . '.' . $file_ext;
                            move_uploaded_file($files['tmp_name'][$i], $upload_dir . $new_filename);
                            $saved_filenames[] = $new_filename;
                        }

                        if (empty($saved_filenames)) {
                            $_SESSION['thong_bao_donhang'] = "Lỗi: Không có file hợp lệ nào được tải lên.";
                            header("Location: index.php?action=donhang");
                            exit();
                        }

                        $minhchung_filename = implode(',', $saved_filenames);

                        // --- Lưu vào database ---
                        $donHangModel->capNhatTrangThai($donhang_id, 'Yêu cầu trả hàng');
                        $donHangModel->capNhatGhiChu($donhang_id, $ghi_chu);
                        $donHangModel->capNhatThongTinHoanTien($donhang_id, $thongtin_nganhang, $minhchung_filename, $ly_do);

                        $_SESSION['thong_bao_donhang'] = "Đã gửi yêu cầu trả hàng thành công! Shop sẽ xem xét và liên hệ bạn trong 1-3 ngày làm việc.";
                    } else {
                        $_SESSION['thong_bao_donhang'] = "Chỉ những đơn hàng đã giao thành công và trong vòng 7 ngày mới có thể yêu cầu trả hàng.";
                    }
                    break;
                }
            }
            if(!$hop_le) {
                $_SESSION['thong_bao_donhang'] = "Đơn hàng không tồn tại hoặc không thuộc về bạn.";
            }
            header("Location: index.php?action=donhang");
            exit();
        }
    }

    // Khách hàng xác nhận đã nhận được hàng
    public function daNhanHang() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=taikhoan");
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donhang_id'])) {
            $donhang_id = intval($_POST['donhang_id']);
            require_once __DIR__ . '/../../models/DonHangModel.php';
            $donHangModel = new DonHangModel($this->conn);

            // Kiểm tra đơn hàng thuộc về user này và đang ở trạng thái "Đang giao"
            $ds_donhang = $donHangModel->layDonHangTheoUser($_SESSION['user']['id']);
            $hop_le = false;
            foreach ($ds_donhang as $dh) {
                if ($dh['id'] == $donhang_id) {
                    $hop_le = true;
                    if (strpos($dh['trang_thai'], 'Đang giao') !== false) {
                        // Cập nhật sang "Hoàn thành"
                        $donHangModel->capNhatTrangThai($donhang_id, 'Hoàn thành');
                        $_SESSION['thong_bao_donhang'] = "Cảm ơn bạn đã xác nhận nhận hàng! Đơn hàng #DH" . str_pad($donhang_id, 6, '0', STR_PAD_LEFT) . " đã hoàn thành. Chúc bạn hài lòng với sản phẩm!";
                    } else {
                        $_SESSION['thong_bao_donhang'] = "Không thể xác nhận: Đơn hàng không ở trạng thái đang giao.";
                    }
                    break;
                }
            }
            if (!$hop_le) {
                $_SESSION['thong_bao_donhang'] = "Đơn hàng không tồn tại hoặc không thuộc về bạn.";
            }
        }
        header("Location: index.php?action=donhang");
        exit();
    }
}
?>