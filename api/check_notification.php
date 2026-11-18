<?php

header('Content-Type: application/json');


require '../includes/db.php'; 


$sql = "SELECT t.id, t.amount, t.method, t.type, u.username 
        FROM transactions_fake t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.status = 'pending' AND t.is_notified = 0 
        ORDER BY t.created_at DESC LIMIT 5";

$result = $conn->query($sql);
$notifications = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $typeBangla = ($row['type'] == 'deposit') ? 'ডিপোজিট' : 'উইথড্র';
        
        $notifications[] = [
            'title' => "নতুন " . $typeBangla . " রিকোয়েস্ট! 🔔",
            'body'  => "নাম: " . $row['username'] . "\nপরিমাণ: ৳" . $row['amount'] . "\nমেথড: " . ucfirst($row['method']),
            'url'   => "requests.php" 
        ];

        // আপডেট
        $update_id = $row['id'];
        $conn->query("UPDATE transactions_fake SET is_notified = 1 WHERE id = $update_id");
    }
}

echo json_encode($notifications);
?>