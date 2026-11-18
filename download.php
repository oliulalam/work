<?php
// Maintenance Mode public access guard
$__maintenance_db = __DIR__ . '/includes/db.php';
if (file_exists($__maintenance_db)) { require_once $__maintenance_db; }


$file = 'kaki66.apk';

if (file_exists($file)) {
   
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    
   
    readfile($file);


    echo '<meta http-equiv="refresh" content="0;url=index.php">';
    exit;
} else {
    
    header("Location: index.php");
    exit;
}
?>