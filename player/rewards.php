<?php
session_start();

/* =========================================================
   DATABASE CONNECTION
   ========================================================= */

$db_path = file_exists('includes/db.php')
    ? 'includes/db.php'
    : (file_exists('../includes/db.php') ? '../includes/db.php' : __DIR__ . '/../includes/db.php');

if (file_exists($db_path)) {
    require_once $db_path;
}

/* =========================================================
   LOGIN CHECK
   ========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$message = "";
$message_type = "success";


/* =========================================================
   1. HANDLE BONUS CLAIM
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    $b_id = isset($_POST['bonus_id'])
        ? intval($_POST['bonus_id'])
        : 0;


    /* =====================================================
       PERSONAL BONUS
       ===================================================== */

    if ($action === 'claim_personal') {

        try {
            $check = $conn->query("
                SELECT amount
                FROM personal_bonuses
                WHERE id = $b_id
                  AND user_id = $user_id
                  AND status = 'pending'
                LIMIT 1
            ");

            if ($check && $check->num_rows > 0) {

                $bonus = $check->fetch_assoc();

                $amount = (float)$bonus['amount'];


                /* Add balance */

                $update = $conn->query("
                    UPDATE users
                    SET balance = balance + $amount
                    WHERE id = $user_id
                ");


                if ($update) {

                    /* Mark bonus claimed */

                    $conn->query("
                        UPDATE personal_bonuses
                        SET status = 'claimed'
                        WHERE id = $b_id
                          AND user_id = $user_id
                          AND status = 'pending'
                    ");


                    $message =
                        "অভিনন্দন! আপনি ৳" .
                        number_format($amount, 2) .
                        " বোনাস পেয়েছেন।";

                } else {

                    $message = "বোনাস যোগ করা যায়নি।";

                    $message_type = "error";
                }

            } else {

                $message = "এই বোনাসটি আর পাওয়া যাচ্ছে না।";

                $message_type = "error";
            }
        } catch (Throwable $e) {
            $message = "বোনাস প্রসেস করার সময় সমস্যা হয়েছে।";
            $message_type = "error";
        }


    /* =====================================================
       GLOBAL BONUS
       ===================================================== */

    } elseif ($action === 'claim_global') {

        try {
            $check = $conn->query("
                SELECT amount, reward_amount
                FROM global_bonuses
                WHERE id = $b_id
                  AND is_active = 1
                LIMIT 1
            ");


            $already_claimed = $conn->query("
                SELECT id
                FROM bonus_claims
                WHERE user_id = $user_id
                  AND bonus_id = $b_id
                LIMIT 1
            ");


            if (
                $check &&
                $check->num_rows > 0 &&
                $already_claimed &&
                $already_claimed->num_rows == 0
            ) {

                $bonus = $check->fetch_assoc();

                $amount = (float)($bonus['amount'] ?? $bonus['reward_amount'] ?? 0);


                /* Add global bonus */

                $update = $conn->query("
                    UPDATE users
                    SET balance = balance + $amount
                    WHERE id = $user_id
                ");


                if ($update) {

                    $conn->query("
                        INSERT INTO bonus_claims
                        (
                            user_id,
                            bonus_id
                        )
                        VALUES
                        (
                            $user_id,
                            $b_id
                        )
                    ");


                    $message =
                        "অভিনন্দন! আপনি ৳" .
                        number_format($amount, 2) .
                        " বোনাস পেয়েছেন।";

                } else {

                    $message = "বোনাস যোগ করা যায়নি।";

                    $message_type = "error";
                }

            } else {

                $message =
                    "এই বোনাসটি আর পাওয়া যাচ্ছে না অথবা ইতিমধ্যে নেওয়া হয়েছে।";

                $message_type = "error";
            }
        } catch (Throwable $e) {
            $message = "বোনাস প্রসেস করার সময় সমস্যা হয়েছে।";
            $message_type = "error";
        }
    }
}


/* =========================================================
   2. FETCH USER
   ========================================================= */

try {
    $user_query = $conn->query("
        SELECT *
        FROM users
        WHERE id = $user_id
        LIMIT 1
    ");

    $user = $user_query
        ? $user_query->fetch_assoc()
        : null;
} catch (Throwable $e) {
    $user = null;
}


if (!$user) {

    die("ব্যবহারকারী পাওয়া যায়নি।");
}


/* =========================================================
   3. FETCH PERSONAL BONUSES
   ========================================================= */

try {
    $personal_claim = $conn->query("
        SELECT *
        FROM personal_bonuses
        WHERE user_id = $user_id
          AND status = 'pending'
          AND category IN ('claim', 'recovery')
        ORDER BY id DESC
    ");


    $cnt_claim = $personal_claim
        ? $personal_claim->num_rows
        : 0;
} catch (Throwable $e) {
    $personal_claim = false;
    $cnt_claim = 0;
}


/* =========================================================
   4. LUCKY TICKETS
   ========================================================= */

$cnt_temu = 0;

try {
    $ticket_query = $conn->query("
        SELECT tickets
        FROM lucky_tickets
        WHERE user_id = $user_id
        LIMIT 1
    ");


    if ($ticket_query && $ticket_query->num_rows > 0) {

        $ticket_row = $ticket_query->fetch_assoc();

        $cnt_temu = (int)$ticket_row['tickets'];
    }
} catch (Throwable $e) {
    $cnt_temu = 0;
}

?>


<!DOCTYPE html>

<html lang="bn">

<head>

    <meta charset="UTF-8">


    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    >


    <title>রিওয়ার্ড সেন্টার</title>


    <!-- Tailwind -->

    <script src="https://cdn.tailwindcss.com"></script>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <style>

        body {

            background-color: #0b0b0b;

            color: #ffffff;

            font-family:
                'Segoe UI',
                'Noto Sans Bengali',
                sans-serif;

            padding-bottom: 90px;

        }


        /* =================================================
           HEADER
           ================================================= */

        .reward-header {

            background:
                linear-gradient(
                    180deg,
                    #FF6B4A 0%,
                    #FF9F4A 100%
                );

            height: 220px;

            border-bottom-left-radius: 30px;

            border-bottom-right-radius: 30px;

            position: relative;

        }


        /* =================================================
           PROFILE CARD
           ================================================= */

        .profile-card {

            background:
                linear-gradient(
                    135deg,
                    #ffffff 0%,
                    #e0e0e0 100%
                );

            border-radius: 20px;

            box-shadow:
                0 10px 30px
                rgba(0,0,0,0.3);

            margin-top: -80px;

            color: #333;

        }


        /* =================================================
           REWARD BUTTON
           ================================================= */

        .grid-btn {

            border-radius: 18px;

            padding: 20px;

            color: white;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            height: 120px;

            position: relative;

            overflow: hidden;

            transition:
                all 0.3s ease;

            cursor: pointer;

            border:
                1px solid
                rgba(255,255,255,0.1);

        }


        .grid-btn:active {

            transform: scale(0.96);

        }


        /* =================================================
           GRADIENT
           ================================================= */

        .bg-green-grad {

            background:
                linear-gradient(
                    135deg,
                    #10B981 0%,
                    #059669 100%
                );

        }


        .bg-blue-grad {

            background:
                linear-gradient(
                    135deg,
                    #3B82F6 0%,
                    #2563EB 100%
                );

        }


        .bg-pink-grad {

            background:
                linear-gradient(
                    135deg,
                    #EC4899 0%,
                    #DB2777 100%
                );

        }


        .bg-orange-grad {

            background:
                linear-gradient(
                    135deg,
                    #F97316 0%,
                    #EA580C 100%
                );

        }


        /* =================================================
           ICON
           ================================================= */

        .btn-icon {

            width: 48px;

            height: 48px;

            background:
                rgba(255,255,255,0.2);

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 10px;

            font-size: 22px;

        }


        /* =================================================
           BADGE
           ================================================= */

        .badge-count {

            position: absolute;

            top: -5px;

            right: -5px;

            background: #EF4444;

            color: white;

            width: 22px;

            height: 22px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 11px;

            font-weight: bold;

            border: 2px solid #fff;

        }


        /* =================================================
           MODAL
           ================================================= */

        .modal-box {

            animation:
                modalShow
                0.2s
                ease;

        }


        @keyframes modalShow {

            from {

                transform:
                    scale(0.9);

                opacity: 0;

            }

            to {

                transform:
                    scale(1);

                opacity: 1;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     MESSAGE
     ===================================================== -->

<?php if ($message): ?>

    <div
        onclick="this.remove()"
        class="<?php echo $message_type === 'error'
            ? 'bg-red-600'
            : 'bg-green-600'; ?>
            fixed top-4 left-4 right-4
            text-white p-4 rounded-xl
            shadow-2xl z-[100]
            text-center text-sm font-bold"
    >

        <?php echo htmlspecialchars($message); ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     HEADER
     ===================================================== -->

<div class="reward-header px-4 pt-6">

    <div
        class="flex items-center text-white mb-6 relative z-10"
    >

        <button
            onclick="window.history.back()"
            class="text-xl absolute left-0"
        >

            <i class="fas fa-chevron-left"></i>

        </button>


        <h1
            class="text-lg font-bold w-full text-center"
        >

            রিওয়ার্ড সেন্টার

        </h1>

    </div>


    <h1
        class="
            text-6xl
            font-black
            text-white
            opacity-10
            text-center
            tracking-widest
            mt-4
        "
    >

        REWARDS

    </h1>

</div>


<!-- =====================================================
     PROFILE CARD
     ===================================================== -->

<div class="px-4">

    <div
        class="profile-card p-5 relative"
    >


        <!-- SIGN IN -->

        <a
            href="rewardsignin.php"
            class="
                absolute
                top-0
                right-0
                bg-red-600
                text-white
                text-[10px]
                font-bold
                px-4
                py-1.5
                rounded-bl-2xl
                rounded-tr-2xl
                flex
                items-center
                gap-1
                shadow-md
            "
        >

            <i class="far fa-calendar-check"></i>

            দৈনিক উপস্থিতি

            <i
                class="fas fa-chevron-right text-[8px]"
            ></i>

        </a>


        <div class="flex items-center gap-4">


            <!-- AVATAR -->

            <div
                class="
                    w-16
                    h-16
                    rounded-full
                    border-2
                    border-white
                    shadow-md
                    overflow-hidden
                "
            >

                <img
                    src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=ffd700&color=333&bold=true"
                    class="w-full h-full object-cover"
                    alt="প্রোফাইল"
                >

            </div>


            <!-- USER -->

            <div class="flex-1">

                <div class="flex items-center gap-2">

                    <h2
                        class="
                            font-black
                            text-gray-800
                            text-lg
                        "
                    >

                        <?php

                        echo htmlspecialchars(
                            $user['username']
                        );

                        ?>

                    </h2>


                    <i
                        onclick="copyText('<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')"
                        class="
                            far
                            fa-copy
                            text-gray-400
                            text-xs
                            cursor-pointer
                        "
                    ></i>

                </div>


                <p
                    class="
                        text-gray-500
                        text-xs
                        font-semibold
                    "
                >

                    ডাকনাম:

                    <?php

                    echo htmlspecialchars(
                        $user['username']
                    );

                    ?>

                </p>


                <!-- BALANCE -->

                <div
                    class="
                        flex
                        items-center
                        gap-1
                        text-gray-900
                        font-black
                        mt-1
                    "
                >

                    <span class="text-lg">

                        ৳

                        <?php

                        echo number_format(
                            (float)$user['balance'],
                            2
                        );

                        ?>

                    </span>


                    <i
                        class="
                            fas
                            fa-sync-alt
                            text-gray-400
                            text-xs
                            ml-2
                            cursor-pointer
                        "
                        onclick="location.reload()"
                    ></i>

                </div>

            </div>

        </div>


        <!-- VIP -->

        <div class="mt-5">

            <div
                class="
                    flex
                    justify-between
                    items-center
                    text-[11px]
                    text-gray-600
                    mb-1.5
                    font-bold
                "
            >

                <span
                    class="
                        flex
                        items-center
                        gap-1
                        text-orange-600
                    "
                >

                    <i class="fas fa-crown"></i>

                    VIP0 সদস্য

                </span>


                <a
                    href="vip.php"
                    class="text-blue-600"
                >

                    সুবিধা

                    <i
                        class="
                            fas
                            fa-chevron-right
                            text-[8px]
                        "
                    ></i>

                </a>

            </div>


            <div
                class="
                    w-full
                    bg-gray-200
                    rounded-full
                    h-2
                    shadow-inner
                "
            >

                <div
                    class="
                        bg-gradient-to-r
                        from-orange-400
                        to-orange-600
                        h-2
                        rounded-full
                    "
                    style="width: 15%"
                ></div>

            </div>


            <p
                class="
                    text-right
                    text-[10px]
                    text-gray-500
                    mt-1
                    font-bold
                "
            >

                অগ্রগতি: ০ / ২

            </p>

        </div>

    </div>

</div>


<!-- =====================================================
     REWARD BUTTONS
     ===================================================== -->

<div
    class="
        px-4
        mt-8
        grid
        grid-cols-2
        gap-4
    "
>


    <!-- CLAIM BONUS -->

    <div
        onclick="openModal('modal-claim')"
        class="
            grid-btn
            bg-green-grad
            shadow-lg
        "
    >

        <div class="btn-icon relative">

            <i class="fas fa-gift"></i>


            <?php if ($cnt_claim > 0): ?>

                <div class="badge-count">

                    <?php echo $cnt_claim; ?>

                </div>

            <?php endif; ?>

        </div>


        <span
            class="
                text-sm
                font-black
                tracking-tighter
            "
        >

            বোনাস নিন

        </span>

    </div>


    <!-- DAILY CHECK-IN -->

    <a
        href="rewardsignin.php"
        class="
            grid-btn
            bg-blue-grad
            shadow-lg
        "
    >

        <div class="btn-icon">

            <i
                class="far fa-calendar-check"
            ></i>

        </div>


        <span
            class="
                text-sm
                font-black
                tracking-tighter
            "
        >

            দৈনিক উপস্থিতি

        </span>

    </a>


    <!-- INVITE -->

    <a
        href="share.php"
        class="
            grid-btn
            bg-pink-grad
            shadow-lg
        "
    >

        <div class="btn-icon">

            <i
                class="fas fa-user-plus"
            ></i>

        </div>


        <span
            class="
                text-sm
                font-black
                tracking-tighter
                text-center
            "
        >

            বন্ধু আমন্ত্রণ

        </span>

    </a>


    <!-- LUCKY TICKETS -->

    <div
        onclick="openModal('modal-temu')"
        class="
            grid-btn
            bg-orange-grad
            shadow-lg
        "
    >

        <div class="btn-icon relative">

            <i
                class="fas fa-ticket-alt"
            ></i>


            <?php if ($cnt_temu > 0): ?>

                <div class="badge-count">

                    <?php echo $cnt_temu; ?>

                </div>

            <?php endif; ?>

        </div>


        <span
            class="
                text-sm
                font-black
                tracking-tighter
            "
        >

            লাকি টিকিট

        </span>

    </div>

</div>


<!-- =====================================================
     PERSONAL BONUS MODAL
     ===================================================== -->

<div
    id="modal-claim"
    class="
        fixed
        inset-0
        bg-black/90
        z-[10001]
        hidden
        flex
        items-center
        justify-center
        p-4
    "
>

    <div
        class="
            modal-box
            bg-white
            rounded-2xl
            w-full
            max-w-sm
            p-6
            relative
        "
    >


        <!-- CLOSE -->

        <button
            onclick="closeModal('modal-claim')"
            class="
                absolute
                top-3
                right-4
                text-gray-400
                text-2xl
            "
        >

            &times;

        </button>


        <h3
            class="
                text-xl
                font-black
                text-center
                text-green-600
                mb-6
            "
        >

            পাওয়া বোনাস

        </h3>


        <div
            class="
                space-y-4
                max-h-80
                overflow-y-auto
            "
        >


            <?php if ($cnt_claim > 0): ?>


                <?php while (
                    $row =
                    $personal_claim->fetch_assoc()
                ): ?>


                    <form
                        method="POST"
                        class="
                            bg-gray-50
                            p-4
                            rounded-xl
                            border
                            border-gray-100
                            flex
                            justify-between
                            items-center
                            shadow-sm
                        "
                    >


                        <div>

                            <p
                                class="
                                    font-bold
                                    text-gray-800
                                    text-sm
                                "
                            >

                                <?php

                                echo htmlspecialchars(
                                    $row['description']
                                );

                                ?>

                            </p>


                            <p
                                class="
                                    text-green-600
                                    font-black
                                    text-lg
                                "
                            >

                                ৳

                                <?php

                                echo number_format(
                                    (float)$row['amount'],
                                    2
                                );

                                ?>

                            </p>

                        </div>


                        <input
                            type="hidden"
                            name="action"
                            value="claim_personal"
                        >


                        <input
                            type="hidden"
                            name="bonus_id"
                            value="<?php
                                echo (int)$row['id'];
                            ?>"
                        >


                        <button
                            type="submit"
                            class="
                                bg-green-600
                                text-white
                                px-6
                                py-2
                                rounded-full
                                text-xs
                                font-black
                                shadow-lg
                            "
                        >

                            নিন

                        </button>

                    </form>


                <?php endwhile; ?>


            <?php else: ?>


                <div
                    class="
                        text-center
                        py-8
                    "
                >

                    <i
                        class="
                            fas
                            fa-box-open
                            text-gray-200
                            text-5xl
                            mb-3
                        "
                    ></i>


                    <p
                        class="
                            text-gray-400
                            font-bold
                            text-xs
                        "
                    >

                        বর্তমানে কোনো বোনাস নেই

                    </p>

                </div>


            <?php endif; ?>

        </div>

    </div>

</div>


<!-- =====================================================
     LUCKY TICKET MODAL
     ===================================================== -->

<div
    id="modal-temu"
    class="
        fixed
        inset-0
        bg-black/90
        z-[10001]
        hidden
        flex
        items-center
        justify-center
        p-4
    "
>

    <div
        class="
            modal-box
            bg-white
            rounded-2xl
            w-full
            max-w-sm
            p-8
            text-center
            relative
        "
    >


        <!-- CLOSE -->

        <button
            onclick="closeModal('modal-temu')"
            class="
                absolute
                top-3
                right-4
                text-gray-400
                text-2xl
            "
        >

            &times;

        </button>


        <!-- ICON -->

        <i
            class="
                fas
                fa-ticket-alt
                text-orange-300
                text-6xl
                mb-4
            "
        ></i>


        <!-- TITLE -->

        <h3
            class="
                text-xl
                font-black
                text-gray-800
                mb-2
            "
        >

            লাকি টিকিট

        </h3>


        <!-- COUNT -->

        <p
            class="
                text-gray-500
                text-sm
                font-bold
                mb-5
            "
        >

            আপনার কাছে

            <span
                class="
                    text-orange-600
                    font-black
                "
            >

                <?php echo $cnt_temu; ?>

            </span>

            টি লাকি টিকিট আছে

        </p>


        <?php if ($cnt_temu > 0): ?>


            <!-- PLAY -->
<a
    href="../lucky_spin.php"
    class="
        inline-flex
        items-center
        justify-center
        gap-2
        bg-orange-500
        hover:bg-orange-600
        text-white
        px-8
        py-3
        rounded-full
        text-sm
        font-black
        shadow-lg
    "
>
    <i class="fas fa-gift"></i>
    রিওয়ার্ড খেলুন
</a>


        <?php else: ?>


            <!-- NO TICKET -->

            <div
                class="
                    bg-gray-100
                    rounded-xl
                    p-4
                "
            >

                <i
                    class="
                        fas
                        fa-ticket-alt
                        text-gray-300
                        text-3xl
                        mb-2
                    "
                ></i>


                <p
                    class="
                        text-gray-400
                        text-xs
                        font-bold
                    "
                >

                    আপনার কাছে কোনো লাকি টিকিট নেই

                </p>

            </div>


        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     BOTTOM NAVIGATION
     ===================================================== -->

<?php include 'bottom_nav.php'; ?>


<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script>


/* =====================================================
   OPEN MODAL
   ===================================================== */

function openModal(id) {

    const modal =
        document.getElementById(id);

    if (modal) {

        modal.classList.remove('hidden');

    }

}


/* =====================================================
   CLOSE MODAL
   ===================================================== */

function closeModal(id) {

    const modal =
        document.getElementById(id);

    if (modal) {

        modal.classList.add('hidden');

    }

}


/* =====================================================
   COPY USERNAME
   ===================================================== */

function copyText(text) {

    navigator.clipboard
        .writeText(text)
        .then(function() {

            alert("কপি হয়েছে!");

        })
        .catch(function() {

            alert("কপি করা যায়নি।");

        });

}


/* =====================================================
   CLOSE MODAL OUTSIDE
   ===================================================== */

document.addEventListener(
    'click',
    function(event) {

        if (
            event.target.id === 'modal-claim'
        ) {

            closeModal('modal-claim');

        }


        if (
            event.target.id === 'modal-temu'
        ) {

            closeModal('modal-temu');

        }

    }
);


</script>


</body>

</html>