<?php
require_once '../config/auth.php';
require_once '../config/database.php';
requireLogin();

$conn = getDBConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Location: dashboard.php?success=Ürün başarıyla silindi!');
    } else {
        header('Location: dashboard.php?error=Ürün silinirken bir hata oluştu!');
    }
    $stmt->close();
} else {
    header('Location: dashboard.php');
}
exit;

