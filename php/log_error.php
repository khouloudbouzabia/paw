<?php
// php/log_error.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $_POST['message'] ?? '',
        'source' => $_POST['source'] ?? '',
        'line' => $_POST['line'] ?? '',
        'column' => $_POST['column'] ?? '',
        'error' => $_POST['error'] ?? '',
        'url' => $_POST['url'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    // تسجيل الخطأ في ملف (في بيئة الإنتاج، استخدم نظام تسجيل مناسب)
    file_put_contents('error_log.txt', json_encode($logData) . PHP_EOL, FILE_APPEND);
    
    echo json_encode(['success' => true]);
}
?>