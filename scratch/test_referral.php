<?php
// scratch/test_referral.php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/referral_helper.php';

echo "1. Checking referral schema...\n";
referral_ensure_schema($conn);
echo "OK!\n";

echo "2. Resolving test referral code...\n";
$res = referral_resolve_code($conn, 'REF12345');
print_r($res);

echo "3. Testing referral reward process function existence...\n";
if (function_exists('referral_process_signup_reward')) {
    echo "SUCCESS: referral_process_signup_reward function exists!\n";
} else {
    echo "ERROR: function missing!\n";
}
