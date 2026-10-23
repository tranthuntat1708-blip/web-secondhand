<?php
$host = "localhost";
$username = "root"; // Mặc định của XAMPP
$password = "";     // Mặc định của XAMPP là để trống
$dbname = "My_website"; // Đổi thành tên Database bạn đã tạo trong phpMyAdmin

// Lệnh kết nối
$conn = mysqli_connect($host, $username, $password, $dbname);

// Kiểm tra xem kết nối có lỗi không
if (!$conn) {
    die("Kết nối CSDL thất bại: " . mysqli_connect_error());
}

// Tạm thời để dòng này để kiểm tra, tí nữa chạy thử xong sẽ xóa đi sau
echo "Kết nối Cơ sở dữ liệu thành công rồi nha!"; 
?>