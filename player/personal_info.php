<?php
session_start();

// 1. Database Connection (Manual fallback removed)
$db_path = file_exists('includes/db.php') ? 'includes/db.php' : '../includes/db.php';
if (file_exists($db_path)) {
    require $db_path;
}

// 2. Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// --- HANDLE POST REQUESTS (Data Save) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $check = $conn->query("SELECT user_id FROM player_profiles WHERE user_id=$user_id");
    if($check->num_rows == 0){
        $conn->query("INSERT INTO player_profiles (user_id) VALUES ($user_id)");
    }

    if (isset($_POST['action'])) {
        $val = $conn->real_escape_string($_POST['value']);
        
        if ($_POST['action'] == 'full_name') {
            $conn->query("UPDATE player_profiles SET kyc_real_name='$val' WHERE user_id=$user_id");
        } 
        elseif ($_POST['action'] == 'birthday') {
            $conn->query("UPDATE player_profiles SET kyc_dob='$val' WHERE user_id=$user_id");
        }
        elseif ($_POST['action'] == 'email') {
            $conn->query("UPDATE users SET email='$val', email_verified=0 WHERE id=$user_id");
        }
        header("Location: personal_info.php");
        exit();
    }

    if (isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != "") {
        $upload_dir = "assets/uploads/avatars/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $new_filename = "avatar_" . $user_id . "." . $ext;
            foreach($allowed as $del_ext) {
                if(file_exists($upload_dir . "avatar_" . $user_id . "." . $del_ext)) {
                    unlink($upload_dir . "avatar_" . $user_id . "." . $del_ext);
                }
            }
            move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename);
        }
        header("Location: personal_info.php");
        exit();
    }

    if (isset($_POST['send_otp'])) {
        $email = $_POST['email_to_verify'];
        $otp = rand(100000, 999999);
        $_SESSION['email_otp'] = $otp;
        
        require '../includes/PHPMailer/src/Exception.php';
        require '../includes/PHPMailer/src/PHPMailer.php';
        require '../includes/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'mail.ruposhi88.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'support@ruposhi88.com'; 
            $mail->Password   = 'SuPPoRtRuPoshi88';    
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;

            $mail->setFrom('support@ruposhi88.com', 'SHA75 Support');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Email Verification OTP';
            $mail->Body    = "Your verification code is: <b>$otp</b>";

            $mail->send();
            $msg = "OTP sent to $email!";
            $msg_type = "success";
        } catch (Exception $e) {
            $msg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            $msg_type = "error";
        }
    }

    if (isset($_POST['verify_otp'])) {
        $user_otp = $_POST['otp_code'];
        if (isset($_SESSION['email_otp']) && $user_otp == $_SESSION['email_otp']) {
            $conn->query("UPDATE users SET email_verified=1 WHERE id=$user_id");
            unset($_SESSION['email_otp']);
            $msg = "Email verified successfully!";
            $msg_type = "success";
        } else {
            $msg = "Invalid OTP!";
            $msg_type = "error";
        }
    }
}

// --- FETCH DATA ---
$sql = "SELECT u.username, u.phone, u.email, u.email_verified, u.created_at, 
                p.kyc_real_name, p.kyc_dob, p.kyc_status 
        FROM users u 
        LEFT JOIN player_profiles p ON u.id = p.user_id 
        WHERE u.id = $user_id";
$user = $conn->query($sql)->fetch_assoc();

// Avatar
$avatar_url = "";
$possible_exts = ['jpg', 'jpeg', 'png', 'webp'];
foreach ($possible_exts as $ext) {
    if (file_exists("assets/uploads/avatars/avatar_" . $user_id . "." . $ext)) {
        $avatar_url = "assets/uploads/avatars/avatar_" . $user_id . "." . $ext;
        break;
    }
}

$missing_personal = [];
if (empty($user['kyc_real_name'])) $missing_personal[] = "Full Name";
if (empty($user['kyc_dob']) || $user['kyc_dob'] == '0000-00-00') $missing_personal[] = "Birthday";

$missing_contact = [];
if ($user['kyc_status'] != 'verified') $missing_contact[] = "Phone Number"; 
if (empty($user['email'])) $missing_contact[] = "Email";

$show_info_box = !empty($missing_personal) || !empty($missing_contact);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Personal Info | SHA75</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color: #f0f2f5;
            --header-color: #154b77;
            --accent-color: #43a047;
            --card-bg: #ffffff;
            --text-main: #333333;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --blue-light: #e0f2fe;
        }

        /* Prevent zooming & horizontal scroll */
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
            touch-action: manipulation;
        }

        body { 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            font-family: 'Roboto', sans-serif; 
            padding-bottom: 90px; 
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }
        input, textarea { user-select: auto; }
        
        .header-bg { 
            background: linear-gradient(135deg, #0f395c 0%, #1a5c92 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Profile Card */
        .profile-card {
            background: var(--card-bg);
            border-radius: 12px; overflow: hidden; text-align: center;
            border: 1px solid var(--border-color); position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 16px;
        }
        .profile-bg {
            height: 90px;
            background: linear-gradient(135deg, #154b77 0%, #0d2a45 100%);
            border-bottom: 3px solid var(--accent-color);
        }
        
        /* Avatar - Centered Perfectly */
        .avatar-container {
            width: 76px; height: 76px;
            margin: -38px auto 10px auto;
            position: relative; z-index: 10;
        }
        .avatar-img-box {
            width: 100%; height: 100%;
            border-radius: 50%;
            background-color: #ffffff;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .avatar-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-edit-btn {
            position: absolute; bottom: 0; right: -4px;
            background: var(--header-color); color: white;
            width: 26px; height: 26px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            font-size: 11px; cursor: pointer;
        }

        /* Info Container */
        .info-container {
            background-color: var(--blue-light);
            border: 1px solid #bae6fd;
            border-left: 4px solid var(--header-color);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            <?php echo (!$show_info_box) ? 'display: none;' : ''; ?>
        }

        /* List Items - NEW LAYOUT */
        .list-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            padding: 0 16px;
        }

        .list-row {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 16px 0; border-bottom: 1px solid var(--border-color);
        }
        .list-row:last-child { border-bottom: none; }
        
        .row-icon { 
            color: var(--header-color); font-size: 16px; width: 36px; height: 36px; 
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            background: #f8fafc; border-radius: 8px; border: 1px solid var(--border-color);
            margin-top: 2px;
        }
        
        .row-content { flex: 1; min-width: 0; } /* Prevents text overflow */
        .row-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;}
        .row-title { color: var(--header-color); font-size: 12.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .row-sub { font-size: 13px; color: var(--text-muted); font-weight: 600; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .row-sub.highlight { color: #154b77; font-weight: 700; }

        /* Action Buttons */
        .btn-action {
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            padding: 5px 10px; border-radius: 4px; transition: 0.2s; white-space: nowrap;
            letter-spacing: 0.5px; flex-shrink: 0; cursor: pointer;
        }
        .btn-add { background: var(--blue-light); color: var(--header-color); border: 1px solid #bae6fd; }
        .btn-add:active { background: #bae6fd; transform: scale(0.95); }
        
        .btn-verify { background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; }
        .btn-verify:active { background: #fecaca; transform: scale(0.95); }
        
        .btn-verified { background: #dcfce7; color: #10b981; border: 1px solid #bbf7d0; cursor: default; }
        
        .btn-edit-icon {
            background: #f8fafc; border: 1px solid var(--border-color); color: var(--header-color);
            width: 24px; height: 24px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center;
            font-size: 10px; cursor: pointer; transition: 0.2s;
        }
        .btn-edit-icon:active { background: var(--blue-light); border-color: #bae6fd; }

        .pill { 
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 9px; font-weight: 800; padding: 3px 8px; border-radius: 4px; 
            margin-top: 6px; margin-right: 4px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        /* Modal Styles */
        .modal-input {
            width: 100%; background: #f8fafc; border: 1.5px solid var(--border-color); 
            color: var(--header-color); border-radius: 8px; padding: 14px 15px; 
            font-size: 14px; font-weight: 600; outline: none; transition: 0.2s;
        }
        .modal-input:focus { border-color: var(--header-color); box-shadow: 0 0 0 3px rgba(21, 75, 119, 0.1); }
        
        .modal-btn-save {
            background: linear-gradient(to bottom, #4caf50 0%, #388e3c 100%);
            color: white; font-weight: 800; font-size: 14px; width: 100%;
            padding: 14px; border-radius: 8px; border: none; text-transform: uppercase; letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(67, 160, 71, 0.2); transition: 0.2s;
        }
        .modal-btn-save:active { transform: translateY(2px); box-shadow: 0 2px 4px rgba(67, 160, 71, 0.2); }
    </style>
</head>
<body>

    <div class="header-bg p-4 flex justify-between items-center sticky top-0 z-50">
        <a href="index.php" class="text-white text-xl p-1"><i class="fas fa-home"></i></a>
        <h1 class="text-white font-bold text-[15px] uppercase tracking-wider">Personal Info</h1>
        <button onclick="window.history.back()" class="text-white text-xl p-1"><i class="fas fa-times"></i></button>
    </div>

    <div class="px-4 mt-5">
        
        <?php if($msg): ?>
            <div class="<?php echo $msg_type=='success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border-l-4 p-3 rounded shadow-sm mb-4 flex items-center gap-2 text-[12px] font-bold">
                <i class="fas <?php echo $msg_type=='success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?>"></i>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-bg"></div>
            
            <form id="imgForm" method="POST" enctype="multipart/form-data" class="hidden">
                <input type="file" name="avatar" id="avatarInput" accept="image/*" onchange="document.getElementById('imgForm').submit()">
            </form>

            <div class="avatar-container" onclick="document.getElementById('avatarInput').click()">
                <div class="avatar-img-box">
                    <?php if($avatar_url): ?>
                        <img src="<?php echo $avatar_url; ?>?t=<?php echo time(); ?>">
                    <?php else: ?>
                        <i class="fas fa-camera text-gray-300 text-3xl"></i>
                    <?php endif; ?>
                </div>
                <div class="avatar-edit-btn"><i class="fas fa-pen"></i></div>
            </div>

            <div class="pb-4">
                <h2 class="text-[#154b77] font-black text-[22px] flex justify-center items-center gap-2 tracking-tight">
                    <?php echo $user['username']; ?>
                </h2>
                <div class="inline-block bg-[#f0f9ff] text-[#0284c7] text-[10px] font-bold px-2.5 py-1 rounded-full border border-[#bae6fd] mt-1.5 uppercase tracking-wide">Standard Member</div>
                <p class="text-[11px] text-gray-400 mt-2 font-medium">Joined : <?php echo date('d M, Y', strtotime($user['created_at'])); ?></p>
            </div>

            <div class="bg-[#f8fafc] border-t border-gray-100 p-4 flex justify-between items-center text-[12px]">
                <span class="text-gray-500 font-bold">VIP Points (VP) <span class="text-[#43a047] ml-1 font-black text-[15px]">0</span></span>
                <a href="vip.php" class="text-[#154b77] font-bold flex items-center gap-1 hover:underline bg-blue-50 px-2.5 py-1.5 rounded-md border border-blue-100">
                    My VIP <i class="fas fa-angle-double-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <?php if($show_info_box): ?>
        <div class="info-container shadow-sm">
            <div class="flex items-start gap-3 mb-3">
                <i class="fas fa-info-circle text-[#154b77] mt-0.5 text-lg shrink-0"></i>
                <p class="text-[#154b77] text-[11.5px] font-semibold leading-relaxed">
                    Please complete the verification below before you proceed with withdrawal requests.
                </p>
            </div>

            <div class="border-b border-[#bae6fd] mb-3 opacity-60"></div>

            <div class="space-y-3">
                <?php if(!empty($missing_personal)): ?>
                <div>
                    <div class="text-[11px] text-[#154b77] font-black uppercase tracking-wider mb-1">Personal Info</div>
                    <div class="flex flex-wrap">
                        <?php foreach($missing_personal as $mp): ?>
                            <span class="pill bg-red-50 text-red-600 border-red-200"><i class="fas fa-circle text-[5px]"></i> <?php echo $mp; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($missing_contact)): ?>
                <div class="mt-2">
                    <div class="text-[11px] text-[#154b77] font-black uppercase tracking-wider mb-1">Contact Info</div>
                    <div class="flex flex-wrap">
                        <?php foreach($missing_contact as $mc): ?>
                            <span class="pill bg-yellow-50 text-yellow-600 border-yellow-200"><i class="fas fa-circle text-[5px]"></i> <?php echo $mc; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="list-card mt-4">
            
            <div class="list-row">
                <div class="row-icon"><i class="far fa-id-card"></i></div>
                <div class="row-content">
                    <div class="row-header">
                        <span class="row-title">Full Name</span>
                        <?php if(empty($user['kyc_real_name'])): ?>
                            <button onclick="openModal('Full Name', 'full_name', 'text')" class="btn-action btn-add"><i class="fas fa-plus mr-1"></i> Add</button>
                        <?php endif; ?>
                    </div>
                    <?php if(!empty($user['kyc_real_name'])): ?>
                        <span class="row-sub highlight"><?php echo $user['kyc_real_name']; ?></span>
                    <?php else: ?>
                        <span class="row-sub">Not Set</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="list-row">
                <div class="row-icon"><i class="fas fa-birthday-cake"></i></div>
                <div class="row-content">
                    <div class="row-header">
                        <span class="row-title">Birthday</span>
                        <?php if(empty($user['kyc_dob']) || $user['kyc_dob'] == '0000-00-00'): ?>
                            <button onclick="openModal('Birthday', 'birthday', 'date')" class="btn-action btn-add"><i class="fas fa-plus mr-1"></i> Add</button>
                        <?php endif; ?>
                    </div>
                    <?php if(!empty($user['kyc_dob']) && $user['kyc_dob'] != '0000-00-00'): ?>
                        <span class="row-sub highlight"><?php echo date('d M, Y', strtotime($user['kyc_dob'])); ?></span>
                    <?php else: ?>
                        <span class="row-sub">Not Set</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="list-row">
                <div class="row-icon"><i class="fas fa-mobile-alt"></i></div>
                <div class="row-content">
                    <div class="row-header">
                        <span class="row-title">Phone Number</span>
                        <?php if($user['kyc_status'] == 'verified'): ?>
                            <span class="btn-action btn-verified"><i class="fas fa-check-circle mr-1"></i> Verified</span>
                        <?php else: ?>
                            <button class="btn-action btn-verify"><i class="fas fa-times-circle mr-1"></i> Unverified</button>
                        <?php endif; ?>
                    </div>
                    <span class="row-sub highlight"><?php echo $user['phone']; ?></span>
                </div>
            </div>

            <div class="list-row">
                <div class="row-icon"><i class="far fa-envelope"></i></div>
                <div class="row-content">
                    <div class="row-header">
                        <span class="row-title">Email Address</span>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <?php if(empty($user['email'])): ?>
                                <button onclick="openModal('Email', 'email', 'email')" class="btn-action btn-add"><i class="fas fa-plus mr-1"></i> Add</button>
                            <?php elseif($user['email_verified'] == 1): ?>
                                <span class="btn-action btn-verified"><i class="fas fa-check-circle mr-1"></i> Verified</span>
                            <?php else: ?>
                                <button onclick="openModal('Email', 'email', 'email')" class="btn-edit-icon" title="Edit Email"><i class="fas fa-pen"></i></button>
                                <button onclick="openVerifyModal('<?php echo $user['email']; ?>')" class="btn-action btn-verify">Verify</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if(!empty($user['email'])): ?>
                        <span class="row-sub highlight"><?php echo $user['email']; ?></span>
                    <?php else: ?>
                        <span class="row-sub">Not Set</span>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="mt-6 flex gap-3 text-[11px] text-gray-500 items-start px-2 bg-blue-50/50 p-4 rounded-xl border border-blue-100 shadow-sm">
            <div class="w-8 h-8 rounded-full bg-[#e0f2fe] flex items-center justify-center flex-shrink-0 border border-[#bae6fd]">
                <i class="fas fa-lock text-[#154b77] text-[13px]"></i>
            </div>
            <p class="leading-relaxed font-medium mt-0.5">For privacy and security, Information cannot be modified after confirmation. Please <a href="support_chat.php" class="text-[#154b77] font-bold hover:underline">contact customer service</a> for help.</p>
        </div>

    </div>

    <div id="editModal" class="fixed inset-0 z-[100] hidden bg-gray-900/60 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-xs rounded-xl p-6 border border-gray-200 relative shadow-2xl">
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 bg-gray-100 w-8 h-8 rounded-full flex items-center justify-center transition"><i class="fas fa-times"></i></button>
            <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mb-4 border border-blue-100">
                <i class="fas fa-edit text-xl text-[#154b77]"></i>
            </div>
            <h3 class="text-[#154b77] text-lg font-black mb-5 uppercase tracking-wide" id="modalTitle">Add Info</h3>
            
            <form method="POST">
                <input type="hidden" name="action" id="modalAction">
                <div class="mb-5">
                    <input type="text" name="value" id="modalInput" class="modal-input" required>
                </div>
                <button type="submit" class="modal-btn-save">Save Details</button>
            </form>
        </div>
    </div>

    <div id="verifyModal" class="fixed inset-0 z-[100] hidden bg-gray-900/60 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-xs rounded-xl p-6 border border-gray-200 relative shadow-2xl">
            <button onclick="document.getElementById('verifyModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 bg-gray-100 w-8 h-8 rounded-full flex items-center justify-center transition"><i class="fas fa-times"></i></button>
            
            <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mb-4 border border-blue-100">
                <i class="fas fa-shield-alt text-xl text-[#154b77]"></i>
            </div>
            <h3 class="text-[#154b77] text-lg font-black mb-2 uppercase tracking-wide">Email Verification</h3>
            <p class="text-[12px] text-gray-500 mb-5 leading-relaxed font-medium">Click 'Send Code' to receive an OTP at <br><span id="verifyEmailText" class="text-[#154b77] font-bold"></span></p>
            
            <form method="POST" id="sendOtpForm">
                <input type="hidden" name="send_otp" value="1">
                <input type="hidden" name="email_to_verify" id="emailToVerifyInput">
                <button type="submit" class="w-full bg-[#154b77] text-white py-3.5 rounded-lg text-[13px] font-bold mb-5 hover:bg-[#0f395c] transition uppercase tracking-wide shadow-md">Send OTP Code</button>
            </form>

            <div class="relative flex py-2 items-center mb-4">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink-0 mx-4 text-gray-400 text-[10px] font-bold uppercase tracking-wider">Or Enter Code</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>

            <form method="POST">
                <input type="hidden" name="verify_otp" value="1">
                <div class="mb-5">
                    <input type="text" name="otp_code" placeholder="Enter 6-digit OTP" class="modal-input text-center tracking-[0.4em] font-black text-xl" required maxlength="6">
                </div>
                <button type="submit" class="modal-btn-save">Verify Email</button>
            </form>
        </div>
    </div>

    <?php include 'bottom_nav.php'; ?>

    <script>
        function openModal(title, action, type) {
            const modal = document.getElementById('editModal');
            document.getElementById('modalTitle').innerText = 'Add ' + title;
            document.getElementById('modalAction').value = action;
            
            const input = document.getElementById('modalInput');
            input.type = type;
            input.value = '';
            input.placeholder = 'Enter ' + title;
            
            modal.classList.remove('hidden');
        }

        function openVerifyModal(email) {
            document.getElementById('verifyEmailText').innerText = email;
            document.getElementById('emailToVerifyInput').value = email;
            document.getElementById('verifyModal').classList.remove('hidden');
        }
    </script>

</body>
</html>