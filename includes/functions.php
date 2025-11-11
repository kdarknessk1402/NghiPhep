<?php
// includes/functions.php - Các hàm tiện ích

// Làm sạch input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Mã hóa mật khẩu
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Kiểm tra mật khẩu
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Tạo mã đơn nghỉ phép ngẫu nhiên
function generateLeaveCode($prefix = 'DN') {
    return $prefix . date('Ymd') . rand(1000, 9999);
}

// Format ngày tháng
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

// Format datetime
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    return date($format, $timestamp);
}

// Tính số ngày giữa 2 ngày
function calculateDays($startDate, $endDate, $includeWeekend = true) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day'); // Bao gồm cả ngày cuối
    
    $interval = $start->diff($end);
    $days = $interval->days;
    
    if (!$includeWeekend) {
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        $workDays = 0;
        foreach ($period as $date) {
            $dayOfWeek = $date->format('N');
            if ($dayOfWeek < 6) { // 1-5 là thứ 2 đến thứ 6
                $workDays++;
            }
        }
        return $workDays;
    }
    
    return $days;
}

// Validate ngày (dd/mm/yyyy hoặc yyyy-mm-dd)
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Chuyển đổi định dạng ngày từ dd/mm/yyyy sang yyyy-mm-dd
function convertDateFormat($date, $fromFormat = 'd/m/Y', $toFormat = 'Y-m-d') {
    $d = DateTime::createFromFormat($fromFormat, $date);
    return $d ? $d->format($toFormat) : false;
}

// Lấy trạng thái đơn theo màu Bootstrap
function getStatusBadge($status) {
    $badges = [
        'WAITING' => '<span class="badge bg-warning">Chờ duyệt</span>',
        'ACCEPT' => '<span class="badge bg-success">Đã duyệt</span>',
        'DENY' => '<span class="badge bg-danger">Từ chối</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Không xác định</span>';
}

// Lấy màu vai trò
function getRoleBadge($role) {
    $badges = [
        'ADMIN' => '<span class="badge bg-danger">Admin</span>',
        'MANAGER' => '<span class="badge bg-primary">Quản lý</span>',
        'USER' => '<span class="badge bg-info">Nhân viên</span>'
    ];
    
    return $badges[$role] ?? '<span class="badge bg-secondary">' . htmlspecialchars($role) . '</span>';
}

// Redirect với message
function redirectWithMessage($url, $type, $message) {
    setFlashMessage($type, $message);
    header('Location: ' . $url);
    exit;
}

// Kiểm tra quyền sửa/xóa đơn
function canEditLeaveRequest($leaveRequest, $userId, $userRole) {
    // Admin có thể sửa mọi đơn
    if ($userRole === 'ADMIN') {
        return true;
    }
    
    // User chỉ có thể sửa đơn của mình và đơn đang chờ duyệt
    if ($userRole === 'USER') {
        return $leaveRequest['MaNguoiDung'] === $userId && 
               $leaveRequest['TrangThai'] === 'WAITING';
    }
    
    // Manager có thể sửa đơn đang chờ duyệt
    if ($userRole === 'MANAGER') {
        return $leaveRequest['TrangThai'] === 'WAITING';
    }
    
    return false;
}

// Upload file (nếu cần trong tương lai)
function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
    $uploadDir = __DIR__ . '/../uploads/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    if ($fileError !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Lỗi upload file'];
    }
    
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => 'File quá lớn (max 5MB)'];
    }
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Định dạng file không hợp lệ'];
    }
    
    $newFileName = uniqid('file_', true) . '.' . $fileExt;
    $filePath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($fileTmpName, $filePath)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $filePath];
    }
    
    return ['success' => false, 'message' => 'Không thể upload file'];
}

// Xuất dữ liệu ra CSV
function exportToCSV($data, $filename = 'export.csv', $headers = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM cho UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Phân trang
function paginate($totalItems, $itemsPerPage = 10, $currentPage = 1) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Tạo HTML phân trang
function renderPagination($pagination, $url) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav><ul class="pagination">';
    
    // Previous
    if ($pagination['has_previous']) {
        $prevPage = $pagination['current_page'] - 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $prevPage . '">‹</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">‹</span></li>';
    }
    
    // Pages
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next
    if ($pagination['has_next']) {
        $nextPage = $pagination['current_page'] + 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $nextPage . '">›</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">›</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

// Log hoạt động
function logActivity($userId, $action, $description) {
    $logFile = __DIR__ . '/../logs/activity.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] User: $userId | Action: $action | Description: $description\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>