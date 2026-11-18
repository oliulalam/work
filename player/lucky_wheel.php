<?php
session_start();

$db_path = file_exists('includes/db.php')
    ? 'includes/db.php'
    : '../includes/db.php';

if (file_exists($db_path)) {
    require $db_path;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| ইউজারের Lucky Ticket তৈরি/নেওয়া
|--------------------------------------------------------------------------
*/
$ticketRow = $conn->query("
    SELECT * FROM lucky_tickets
    WHERE user_id = $user_id
    LIMIT 1
");

if ($ticketRow && $ticketRow->num_rows > 0) {
    $ticket = $ticketRow->fetch_assoc();
} else {
    $conn->query("
        INSERT INTO lucky_tickets (user_id, tickets)
        VALUES ($user_id, 0)
    ");

    $ticket = [
        'id' => $conn->insert_id,
        'tickets' => 0
    ];
}

$ticket_id = intval($ticket['id']);
$tickets = intval($ticket['tickets']);


/*
|--------------------------------------------------------------------------
| SPIN HANDLE
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['spin'])) {

    // নতুন করে টিকিট চেক
    $ticketCheck = $conn->query("
        SELECT id, tickets
        FROM lucky_tickets
        WHERE user_id = $user_id
        LIMIT 1
    ");

    if (!$ticketCheck || $ticketCheck->num_rows === 0) {

        $error = "আপনার Lucky Ticket পাওয়া যায়নি।";

    } else {

        $ticketData = $ticketCheck->fetch_assoc();
        $ticket_id = intval($ticketData['id']);
        $tickets = intval($ticketData['tickets']);

        if ($tickets <= 0) {

            $error = "আপনার কোনো Lucky Ticket নেই।";

        } else {

            /*
            |--------------------------------------------------------------------------
            | পুরস্কার
            |--------------------------------------------------------------------------
            */
            $prizes = [
                1.77,
                7,
                17,
            ];

            // Random prize
            $prize = $prizes[array_rand($prizes)];

            /*
            |--------------------------------------------------------------------------
            | Ticket কমানো
            |--------------------------------------------------------------------------
            */
            $updateTicket = $conn->query("
                UPDATE lucky_tickets
                SET tickets = tickets - 1
                WHERE id = $ticket_id
                AND user_id = $user_id
                AND tickets > 0
            ");

            if ($updateTicket) {

                /*
                |--------------------------------------------------------------------------
                | Balance বাড়ানো
                |--------------------------------------------------------------------------
                */
                $updateBalance = $conn->query("
                    UPDATE users
                    SET balance = balance + $prize
                    WHERE id = $user_id
                ");

                if ($updateBalance) {

                    /*
                    |--------------------------------------------------------------------------
                    | Spin History
                    |--------------------------------------------------------------------------
                    */
                    $conn->query("
                        INSERT INTO lucky_spins
                        (user_id, ticket_id, prize)
                        VALUES
                        ($user_id, $ticket_id, $prize)
                    ");

                    $tickets--;

                    $message = "অভিনন্দন! আপনি ৳" .
                        number_format($prize, 2) .
                        " পুরস্কার পেয়েছেন।";

                } else {

                    // Balance update না হলে ticket ফেরত
                    $conn->query("
                        UPDATE lucky_tickets
                        SET tickets = tickets + 1
                        WHERE id = $ticket_id
                    ");

                    $error = "পুরস্কার যোগ করা যায়নি। আবার চেষ্টা করুন।";
                }

            } else {

                $error = "Spin করা সম্ভব হয়নি। আবার চেষ্টা করুন।";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| সর্বশেষ Ticket সংখ্যা
|--------------------------------------------------------------------------
*/
$ticketResult = $conn->query("
    SELECT tickets
    FROM lucky_tickets
    WHERE user_id = $user_id
    LIMIT 1
");

if ($ticketResult && $ticketResult->num_rows > 0) {
    $tickets = intval($ticketResult->fetch_assoc()['tickets']);
}


/*
|--------------------------------------------------------------------------
| User Balance
|--------------------------------------------------------------------------
*/
$userResult = $conn->query("
    SELECT username, balance
    FROM users
    WHERE id = $user_id
    LIMIT 1
");

$user = $userResult
    ? $userResult->fetch_assoc()
    : [
        'username' => 'ব্যবহারকারী',
        'balance' => 0
    ];

?>
<!DOCTYPE html>
<html lang="bn">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<title>লাকি হুইল</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    background:#100d20;

    color:white;

    font-family:
    "Noto Sans Bengali",
    "Segoe UI",
    Arial,
    sans-serif;

    min-height:100vh;

}

.page{

    width:100%;

    max-width:430px;

    min-height:100vh;

    margin:auto;

    padding:18px 12px 35px;

    background:
    radial-gradient(
        circle at top,
        #211a3b 0%,
        #100d20 45%,
        #100d20 100%
    );

}


/* Back Button */

.topbar{

    display:flex;

    align-items:center;

    justify-content:center;

    position:relative;

    margin-bottom:18px;

}

.back{

    position:absolute;

    left:8px;

    top:2px;

    width:36px;

    height:36px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    color:white;

    font-size:18px;

    cursor:pointer;

}


/* Heading */

.title{

    text-align:center;

    font-size:25px;

    line-height:1.25;

    font-weight:900;

    margin-top:2px;

}

.subtitle{

    text-align:center;

    color:#bdb6d1;

    font-size:13px;

    margin-top:8px;

    line-height:1.5;

}


/* Ticket */

.ticket{

    width:max-content;

    margin:18px auto 20px;

    padding:10px 22px;

    border-radius:13px;

    background:linear-gradient(
        135deg,
        #ffb300,
        #ff7a00
    );

    box-shadow:
    0 8px 25px rgba(255,130,0,.25);

    font-weight:900;

    font-size:15px;

}

.ticket i{

    margin-right:7px;

}


/* Highlight Cards */

.highlight{

    display:grid;

    grid-template-columns:
    repeat(3,1fr);

    gap:8px;

    margin-top:10px;

    padding-bottom:16px;

    border-bottom:1px solid
    rgba(255,255,255,.15);

}

.reward-card{

    height:88px;

    border-radius:13px;

    border:1px solid
    rgba(255,211,66,.45);

    background:
    linear-gradient(
        145deg,
        #282341,
        #17142b
    );

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    position:relative;

    overflow:hidden;

    box-shadow:
    inset 0 0 20px rgba(255,214,75,.04);

}

.reward-card::before{

    content:"";

    position:absolute;

    width:70px;

    height:70px;

    background:#ffd329;

    opacity:.12;

    filter:blur(25px);

    border-radius:50%;

}

.coin{

    font-size:25px;

    margin-bottom:4px;

}

.reward-card .amount{

    font-size:16px;

    font-weight:900;

    position:relative;

}


/* Selector */

.selector{

    position:relative;

    margin-top:-7px;

    height:12px;

}

.selector::before{

    content:"";

    position:absolute;

    top:-2px;

    left:50%;

    transform:translateX(-50%);

    width:0;

    height:0;

    border-left:8px solid transparent;

    border-right:8px solid transparent;

    border-top:13px solid #ffd83d;

}

.selector::after{

    content:"";

    position:absolute;

    bottom:-4px;

    left:50%;

    transform:translateX(-50%);

    width:0;

    height:0;

    border-left:8px solid transparent;

    border-right:8px solid transparent;

    border-bottom:13px solid #ffd83d;

}


/* Possible Rewards */

.possible-title{

    font-size:17px;

    font-weight:900;

    margin-top:22px;

    margin-bottom:12px;

}

.possible{

    display:flex;

    gap:9px;

    overflow:hidden;

}

.possible-item{

    min-width:67px;

    padding:9px 7px;

    text-align:center;

    border-radius:9px;

    background:#28243d;

    color:#aaa4bd;

    border:1px solid
    rgba(255,255,255,.08);

    font-weight:800;

    font-size:13px;

}

.possible-item.active{

    color:#ffd936;

    border-color:#ffd936;

    box-shadow:
    0 0 12px rgba(255,217,54,.18);

}


/* Spin Button */

.spin-form{

    margin-top:22px;

}

.spin-button{

    width:100%;

    height:52px;

    border:none;

    border-radius:12px;

    background:
    linear-gradient(
        135deg,
        #312b4b,
        #26213c
    );

    color:#ffd82e;

    font-size:16px;

    font-weight:900;

    cursor:pointer;

    transition:.2s;

}

.spin-button:hover{

    transform:translateY(-1px);

}

.spin-button:active{

    transform:scale(.98);

}

.spin-button.disabled{

    opacity:.45;

    cursor:not-allowed;

}


/* Message */

.message{

    margin-top:18px;

    padding:13px;

    border-radius:12px;

    background:#164b30;

    color:#b7ffd2;

    text-align:center;

    font-size:14px;

    font-weight:800;

}

.error{

    margin-top:18px;

    padding:13px;

    border-radius:12px;

    background:#4b2028;

    color:#ffc4cc;

    text-align:center;

    font-size:14px;

    font-weight:800;

}


/* Balance */

.balance{

    margin-top:25px;

    padding:15px;

    border-radius:13px;

    background:rgba(255,255,255,.04);

    border:1px solid rgba(255,255,255,.06);

    text-align:center;

}

.balance-label{

    color:#aaa4bd;

    font-size:12px;

}

.balance-value{

    color:#ffd83d;

    font-size:22px;

    font-weight:900;

    margin-top:4px;

}


/* Bottom Text */

.note{

    text-align:center;

    color:#77718b;

    font-size:11px;

    margin-top:18px;

    line-height:1.6;

}

</style>

</head>


<body>

<div class="page">


    <!-- HEADER -->

    <div class="topbar">

        <button
        onclick="history.back()"
        class="back">

            <i class="fas fa-chevron-left"></i>

        </button>

        <div>

            <div class="title">
                আপনার জন্য স্বাগতম বোনাস!
            </div>

            <div class="subtitle">
                লাকি স্পিন করে সর্বোচ্চ ৳১০০ পর্যন্ত
                পুরস্কার জিতে নিন।
            </div>

        </div>

    </div>


    <!-- TICKET -->

    <div class="ticket">

        <i class="fas fa-ticket-alt"></i>

        লাকি টিকিট:
        <?php echo $tickets; ?>

    </div>


    <!-- HIGHLIGHT REWARDS -->

    <div class="highlight">

        <div class="reward-card">

            <div class="coin">🪙</div>

            <div class="amount">৳১</div>

        </div>


        <div class="reward-card">

            <div class="coin">🪙</div>

            <div class="amount">৳৫</div>

        </div>


        <div class="reward-card">

            <div class="coin">🪙</div>

            <div class="amount">৳২৫</div>

        </div>

    </div>


    <!-- SELECTOR -->

    <div class="selector"></div>


    <!-- POSSIBLE REWARDS -->

    <div class="possible-title">

        সম্ভাব্য পুরস্কার

    </div>


    <div class="possible">

        <div class="possible-item">
            ৳১
        </div>

        <div class="possible-item">
            ৳৫
        </div>

        <div class="possible-item active">
            ৳২৫
        </div>

        <div class="possible-item active">
            ৳১০০
        </div>

    </div>


    <!-- MESSAGE -->

    <?php if($message): ?>

        <div class="message">

            <i class="fas fa-gift"></i>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <?php if($error): ?>

        <div class="error">

            <i class="fas fa-circle-exclamation"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <!-- SPIN -->

    <form method="POST" class="spin-form">

        <button
            type="submit"
            name="spin"
            class="spin-button <?php echo ($tickets <= 0) ? 'disabled' : ''; ?>"
            <?php echo ($tickets <= 0) ? 'disabled' : ''; ?>
        >

            <i class="fas fa-sync-alt"></i>

            &nbsp; স্পিন করুন

        </button>

    </form>


    <!-- BALANCE -->

    <div class="balance">

        <div class="balance-label">
            আপনার বর্তমান ব্যালেন্স
        </div>

        <div class="balance-value">

            ৳ <?php
            echo number_format(
                (float)$user['balance'],
                2
            );
            ?>

        </div>

    </div>


    <div class="note">

        প্রতি স্পিনে ১টি Lucky Ticket ব্যবহার হবে।<br>

        জেতা পুরস্কার সরাসরি আপনার ব্যালেন্সে যোগ হবে।

    </div>


</div>


</body>

</html>