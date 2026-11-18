<?php
session_start();
require '../includes/auth_session.php';
require '../includes/db.php';
require '../includes/functions.php';

$msg = "";
$error = "";

// ---------------------------------------------------
// HANDLE FORM SUBMISSIONS
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- 1. ADD NEW PROMOTION ---
    if (isset($_POST['add_promo'])) {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        
        // লজিক্যাল ভ্যালু
        $bonus_percent = intval($_POST['bonus_percent']);
        $wager_multiplier = floatval($_POST['wager_multiplier']);
        
        // ডিসপ্লে টেক্সট (পুরানো লজিক ঠিক রাখার জন্য)
        $bonus_amount_str = $bonus_percent . "% Bonus"; 

        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];
        
        // Image Upload
        $image_path = "";
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../uploads/promotions/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $image_path = "uploads/promotions/" . time() . "_" . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], "../" . $image_path);
        }

        $sql = "INSERT INTO promotions (title, description, bonus_amount, bonus_percent, wager_multiplier, start_date, end_date, status, image) 
                VALUES ('$title', '$description', '$bonus_amount_str', $bonus_percent, $wager_multiplier, '$start_date', '$end_date', '$status', '$image_path')";
        
        if ($conn->query($sql)) {
            $msg = "New Promotion Created Successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }

    // --- 2. EDIT PROMOTION ---
    if (isset($_POST['edit_promo'])) {
        $id = intval($_POST['promo_id']);
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        
        $bonus_percent = intval($_POST['bonus_percent']);
        $wager_multiplier = floatval($_POST['wager_multiplier']);
        $bonus_amount_str = $bonus_percent . "% Bonus";

        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];

        $sql_extra = "";
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../uploads/promotions/";
            $image_path = "uploads/promotions/" . time() . "_" . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], "../" . $image_path);
            
            $sql_extra = ", image_path='$image_path'";
        }

        $sql = "UPDATE promotions SET 
                title='$title', 
                description='$description', 
                bonus_amount='$bonus_amount_str',
                bonus_percent=$bonus_percent,
                wager_multiplier=$wager_multiplier,
                start_date='$start_date', 
                end_date='$end_date', 
                status='$status' 
                $sql_extra 
                WHERE id=$id";
        
        if ($conn->query($sql)) {
            $msg = "Promotion Updated Successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }

    // --- 3. DELETE PROMOTION ---
    if (isset($_POST['delete_promo'])) {
        $id = intval($_POST['promo_id']);
        $conn->query("DELETE FROM promotions WHERE id=$id");
        $msg = "Promotion Deleted!";
    }
}

// Fetch All Promotions
$promotions = $conn->query("SELECT * FROM promotions ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Promotions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-slate-800">

    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="lg:ml-64 p-6 min-h-screen">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Promotions Manager</h1>
            <button onclick="openModal('addModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition">
                <i class="fas fa-plus mr-1"></i> Add New Promo
            </button>
        </div>

        <?php if($msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Image</th>
                        <th class="px-6 py-4">Title & Description</th>
                        <th class="px-6 py-4 text-center">Config</th>
                        <th class="px-6 py-4 text-center">Validity</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while($row = $promotions->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <?php if(!empty($row['image'])): ?>
                                <img src="../<?php echo $row['image']; ?>" class="w-16 h-10 object-cover rounded border">
                            <?php else: ?>
                                <div class="w-16 h-10 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-500">No Img</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="text-xs text-gray-500 truncate w-48"><?php echo htmlspecialchars($row['description']); ?></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-col gap-1 items-center">
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-bold">
                                    +<?php echo $row['bonus_percent']; ?>% Bonus
                                </span>
                                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-bold">
                                    <?php echo $row['wager_multiplier']; ?>x Wager
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center text-xs text-gray-500">
                            <div><?php echo $row['start_date']; ?></div>
                            <div class="text-gray-300">to</div>
                            <div><?php echo $row['end_date']; ?></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-bold 
                                <?php echo $row['status']=='active'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500'; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button onclick='editPromo(<?php echo json_encode($row); ?>)' class="text-blue-600 hover:bg-blue-50 p-2 rounded"><i class="fas fa-edit"></i></button>
                                <form method="POST" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="promo_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_promo" class="text-red-600 hover:bg-red-50 p-2 rounded"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                <h3 class="font-bold text-lg" id="modalTitle">Add New Promotion</h3>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="promo_id" id="edit_id">
                
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Promotion Title</label>
                    <input type="text" name="title" id="p_title" required class="w-full border rounded p-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Bonus Percent (%)</label>
                        <input type="number" name="bonus_percent" id="p_percent" placeholder="e.g. 10" required class="w-full border rounded p-2 text-sm">
                        <p class="text-[10px] text-gray-400 mt-1">Extra balance percentage</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Wager Requirement (x)</label>
                        <input type="number" step="0.1" name="wager_multiplier" id="p_wager" placeholder="e.g. 5" required class="w-full border rounded p-2 text-sm">
                        <p class="text-[10px] text-gray-400 mt-1">Multiplier for turnover</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Description</label>
                    <textarea name="description" id="p_desc" rows="2" class="w-full border rounded p-2 text-sm"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="p_start" required class="w-full border rounded p-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">End Date</label>
                        <input type="date" name="end_date" id="p_end" required class="w-full border rounded p-2 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Status</label>
                        <select name="status" id="p_status" class="w-full border rounded p-2 text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Banner Image</label>
                        <input type="file" name="image" class="w-full text-xs text-gray-500">
                    </div>
                </div>

                <div class="pt-4 border-t flex justify-end gap-2">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 text-gray-600 font-bold text-sm hover:bg-gray-100 rounded">Cancel</button>
                    <button type="submit" name="add_promo" id="submitBtn" class="px-6 py-2 bg-indigo-600 text-white font-bold text-sm hover:bg-indigo-700 rounded shadow">Create Promo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            // Reset for Add
            document.getElementById('edit_id').value = '';
            document.getElementById('modalTitle').innerText = 'Add New Promotion';
            document.getElementById('submitBtn').name = 'add_promo';
            document.getElementById('submitBtn').innerText = 'Create Promo';
            document.querySelector('form').reset();
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function editPromo(data) {
            openModal('addModal');
            document.getElementById('modalTitle').innerText = 'Edit Promotion';
            document.getElementById('submitBtn').name = 'edit_promo';
            document.getElementById('submitBtn').innerText = 'Update Promo';

            document.getElementById('edit_id').value = data.id;
            document.getElementById('p_title').value = data.title;
            document.getElementById('p_percent').value = data.bonus_percent;
            document.getElementById('p_wager').value = data.wager_multiplier;
            document.getElementById('p_desc').value = data.description;
            document.getElementById('p_start').value = data.start_date;
            document.getElementById('p_end').value = data.end_date;
            document.getElementById('p_status').value = data.status;
        }
    </script>
</body>
</html>