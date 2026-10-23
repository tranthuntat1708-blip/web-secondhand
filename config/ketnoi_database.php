<?php
$host = 'sql211.infinityfree.com';     
$dbname = 'if0_42277607_chodocu';  
$username = 'if0_42277607';      
$password = 'B7HvqO5AxzTMly';          

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Lỗi kết nối Chợ Đồ Cũ Database: " . $e->getMessage());
}
?>