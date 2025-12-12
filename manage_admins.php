<?php
/**
 * Manage Administrators Page (manage_admins.php) - FINAL CODE
 * RESTRICTED: Allows ONLY 'super_admin' role to manage 'admin' accounts.
 * Provides functionality to add, update name/email, reset password, and delete admin users.
 */
session_start();
include "db.php"; // Assuming db.php contains $conn

// --- CRITICAL SECURITY CHECK: ONLY SUPER ADMINS ALLOWED ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    // Redirect to login or admin dashboard if access is denied
    header("Location: " . (isset($_SESSION['user_role']) ? 'admin.php' : 'login.php'));
    exit;
}

// Load user data
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = htmlspecialchars($_SESSION['user_role']);
$current_user_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; // success or error

// Function to handle secure redirection after action
function redirect_with_message($msg, $type) {
    header("Location: manage_admins.php?msg=" . urlencode($msg) . "&type=" . urlencode($type));
    exit;
}

// Retrieve URL messages if redirected
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars(urldecode($_GET['msg']));
    $message_type = htmlspecialchars(urldecode($_GET['type']));
}

// =========================================
// ✅ Handle Delete Admin
// =========================================
if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);

    if ($id_to_delete == $current_user_id) {
        redirect_with_message("Error: You cannot delete your own **Super Admin** account.", 'error');
    }

    // Double-check the user role before deletion is attempted
    $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $id_to_delete);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $target_user = $result->fetch_assoc();
    $check_stmt->close();

    if (!$target_user || $target_user['role'] !== 'admin') {
         redirect_with_message("Error deleting admin user: User not found or is a 'Super Admin'.", 'error');
    }

    // Perform Delete: Securely restricts deletion to 'admin' role accounts
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("i", $id_to_delete);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        redirect_with_message("Admin user ID **$id_to_delete** deleted successfully.", 'success');
    } else {
        redirect_with_message("Error deleting admin user: Database failure.", 'error');
    }
}

// =========================================
// ✅ Handle Add Admin
// =========================================
if (isset($_POST['add_admin'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; 
    $role = 'admin'; // Hardcoded role for this page

    if (empty($name) || empty($email) || empty($password)) {
        redirect_with_message("Error: Name, email, and password are required.", 'error');
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        redirect_with_message("New **Admin** user '{$name}' added successfully (ID: {$conn->insert_id}).", 'success');
    } else {
        // Handle unique constraint error (Duplicate email)
        if ($conn->errno == 1062) {
             redirect_with_message("Error adding admin: The email '{$email}' is already registered.", 'error');
        } else {
            redirect_with_message("Error adding admin: " . $stmt->error, 'error');
        }
    }
}

// =========================================
// ✅ Handle Update Name/Email (Only for 'admin' role)
// =========================================
if (isset($_POST['update_admin'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // Prevent updating a Super Admin account via this general button
    if ($id == $current_user_id) {
         redirect_with_message("Error: Use the **My Profile** page to update your own Super Admin details.", 'error');
    }

    // Securely update ONLY users with role='admin'
    $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=? AND role = 'admin'");
    $stmt->bind_param("ssi", $name, $email, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
             redirect_with_message("Admin user ID **$id** updated successfully.", 'success');
        } else {
             redirect_with_message("Error: User ID **$id** not found or is not an editable 'admin' role.", 'error');
        }
    } else {
        redirect_with_message("Error updating admin: " . $stmt->error, 'error');
    }
}

// =========================================
// ✅ Handle Password Reset (Only for 'admin' role)
// =========================================
if (isset($_POST['reset_password'])) {
    $id = intval($_POST['id']);
    $new_password = $_POST['new_password'];
    
    if ($id == $current_user_id) {
        redirect_with_message("Error: You cannot reset your own password here. Use the 'My Profile' page.", 'error');
    }
    
    if (empty($new_password)) {
        redirect_with_message("Error: New password cannot be empty.", 'error');
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Securely reset password ONLY for users with role='admin'
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=? AND role = 'admin'");
    $stmt->bind_param("si", $hashed_password, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            redirect_with_message("Password for Admin user ID **$id** reset successfully.", 'success');
        } else {
            redirect_with_message("Error: Password not reset. User not found or is a 'Super Admin'.", 'error');
        }
    } else {
        redirect_with_message("Error during password reset: " . $stmt->error, 'error');
    }
}


// =========================================
// ✅ Initial Fetch for Display
// =========================================
// Fetch ONLY 'admin' and 'super_admin' users
$admins = $conn->query("SELECT id, name, email, role FROM users WHERE role IN ('admin', 'super_admin') ORDER BY role DESC, name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin: Manage Administrators</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* * =========================================
 * 🎨 CSS Styles for Super Admin Management
 * =========================================
 */
:root {
    --primary: #4e54c8;      /* Deep Blue */
    --secondary: #8f94fb;    /* Soft Purple/Blue */
    --accent: #ff6b6b;      /* Soft Red (Delete) */
    --bg-color: #f4f7fc;    /* Light Blue-Grey Background */
    --gold: #DAA520;        /* Main Gold color */
    --gold-light: #ffeb3b;  /* Lighter Gold */
    --text-dark: #2d3436;
    --text-light: #636e72;
    --white: #ffffff;
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
    --success: #3ac47d;
    --error: #a04444;
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg-color);
    margin: 0;
    padding: 0;
    color: var(--text-dark);
}

header { 
    background: linear-gradient(90deg, var(--gold-light), var(--gold)); 
    color: var(--text-dark); 
    padding: 15px 40px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    filter: brightness(1.1);
}
header h1{margin:0; font-size: 20px;}
header div { font-size: 14px; font-weight: 700; opacity: 0.9; }

.container {
    max-width: 1200px; 
    margin: 40px auto;
    padding: 0 20px;
}

/* --- Form Card (Add Admin) --- */
#addAdminForm {
    max-width: 100%;
    margin: 20px auto 40px auto;
    background: var(--white);
    padding: 30px;
    border-radius: 16px;
    box-shadow: var(--shadow-soft);
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    align-items: end;
}
#addAdminForm h3 {
    grid-column: 1 / -1;
    color: var(--gold); 
    margin-top: 0;
    border-bottom: 2px solid #fff3cd;
    padding-bottom: 10px;
}
#addAdminForm input {
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    width: 100%;
    box-sizing: border-box;
    font-family: inherit;
}
#addAdminForm #addBtn {
    grid-column: 4 / 5;
    background: var(--gold);
    color: white;
    padding: 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: 0.3s;
}
#addAdminForm #addBtn:hover {
    background: #c79510;
}

/* --- Table Styling --- */
table {
    width: 100%;
    margin: 30px 0;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--white);
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    border-radius: 16px;
    overflow: hidden;
}
th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}
th {
    background: var(--gold-light); 
    color: var(--text-dark);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 13px;
    position: sticky;
    top: 0;
    z-index: 5;
}
tr:has(.super-admin-tag) {
    background: #fffae6; 
    font-weight: 600;
}

table input {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 90%;
    font-size: 14px;
    transition: border-color 0.2s;
}

/* Roles */
.role-tag {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.super-admin-tag {
    background: var(--gold-light);
    color: var(--text-dark);
    border: 1px solid var(--gold);
}
.admin-tag {
    background: var(--primary);
    color: var(--white);
    border: 1px solid var(--secondary);
}

/* Actions */
.updateBtn, .resetBtn {
    background: var(--primary);
    color: white;
    padding: 8px 12px;
    font-size: 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    margin-left: 5px; /* Spacing next to input */
}
.updateBtn:hover { background: var(--secondary); }
.resetBtn { background: #3498db; }
.resetBtn:hover { background: #2980b9; }

.delete-link {
    color: var(--accent);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}
.delete-link:hover {
    text-decoration: underline;
}

/* Messages */
.message {
    padding: 15px;
    margin: 20px auto;
    max-width: 100%;
    border-radius: 8px;
    font-weight: 500;
}
.message.success {
    background: #e6ffed;
    border: 1px solid var(--success);
    color: var(--success);
}
.message.error {
    background: #ffe6e6;
    border: 1px solid var(--accent);
    color: var(--error);
}

/* Back Link */
.back-link {
    display: block;
    width: fit-content;
    margin: 20px 0 0 0;
    text-decoration: none;
    color: var(--primary);
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 50px;
    transition: 0.3s;
}
.back-link:hover {
    background: #e7e9ff;
}

@media (max-width: 768px) {
    header { padding: 10px 20px; flex-direction: column; text-align: center; gap: 5px; }
    #addAdminForm { grid-template-columns: 1fr; }
    #addAdminForm #addBtn { grid-column: 1 / -1; }
    table td:nth-child(4), table th:nth-child(4) { display: none; } /* Hide Role column for small screens */
}
</style>
</head>
<body>

<header>
    <h1>Super Admin Management Portal 🔑</h1>
    <div>Logged in as: **<?= $userName ?>** (<?= strtoupper($userRole) ?>)</div>
</header>

<div class="container">

    <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="addAdminForm">
        <h3> Create New Admin Account (Role: Admin)</h3>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Temporary Password" required>
        <button type="submit" name="add_admin" id="addBtn">Add New Admin</button>
    </form>

    <h2>Admin Accounts Overview</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Update Account</th>
                <th>Reset Password</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
        <?php while($u = $admins->fetch_assoc()): 
            $is_self = ($u['id'] == $current_user_id);
            $is_super = ($u['role'] === 'super_admin');
        ?>
            <tr>
                <td><?= $u['id'] ?></td>
                
                <td>
                    <input type="text" name="name_<?= $u['id'] ?>" value="<?= htmlspecialchars($u['name']) ?>" required <?= ($is_super && !$is_self) ? 'readonly' : '' ?>>
                </td>
                
                <td>
                    <input type="email" name="email_<?= $u['id'] ?>" value="<?= htmlspecialchars($u['email']) ?>" required <?= ($is_super && !$is_self) ? 'readonly' : '' ?>>
                </td>
                
                <td>
                    <span class="role-tag <?= $is_super ? 'super-admin-tag' : 'admin-tag' ?>">
                        <?= strtoupper($u['role']) ?>
                    </span>
                    <?php if ($is_self): ?>
                        <span style="font-size: 12px; font-weight: 600;">(You)</span>
                    <?php endif; ?>
                </td>
                
                <td>
                    <?php if (!$is_super): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($u['name']) ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($u['email']) ?>">
                            <button type="submit" name="update_admin" class="updateBtn">Update</button>
                        </form>
                    <?php else: ?>
                        <span style="color: var(--text-light); font-size: 12px;">N/A</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if (!$is_super): ?>
                    <form method="POST" style="display:inline;" class="action-cell">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="password" name="new_password" placeholder="New Password" required style="width: 120px;">
                        <button type="submit" name="reset_password" class="resetBtn">Reset</button>
                    </form>
                    <?php else: ?>
                        <span style="color: var(--text-light); font-size: 12px;">(Use Profile Page)</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if (!$is_super && !$is_self): ?>
                        <a href="?delete=<?= $u['id'] ?>" class="delete-link" onclick="return confirm('WARNING: Are you sure you want to delete Admin ID <?= $u['id'] ?>? This cannot be undone.')">
                            🗑️ Delete
                        </a>
                    <?php elseif ($is_self): ?>
                        <span style="color: var(--accent); font-size: 12px; font-weight: 600;">(Self)</span>
                    <?php else: ?>
                        <span style="color: var(--text-light); font-size: 12px;">(Protected)</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
        <a href="admin.php" class="back-link">⬅ Back to Admin Dashboard</a>

</div>

</body>
</html>