<?php
/**
 * Manage Users Page (manage_users.php)
 * Allows Admins and Super Admins to manage user accounts, including adding, updating roles, and deleting.
 */
session_start();
include "db.php";

// --- SECURITY CHECK (Allow Admin and Super Admin) ---
$allowedRoles = ['admin', 'super_admin'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: login.php");
    exit;
}

// Load user data
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = htmlspecialchars($_SESSION['user_role']);
$message = '';
$message_type = ''; // success or error

// --- SECURITY CHECK: Prevent admin from deleting themselves ---
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'];


// =========================================
// ✅ Handle Delete User (SECURE)
// =========================================
if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);

    if ($id_to_delete == $current_user_id) {
        $message = "Error: You cannot delete your own account!";
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_to_delete);
        
        if ($stmt->execute()) {
            $message = "User ID **$id_to_delete** deleted successfully.";
            $message_type = 'success';
        } else {
            $message = "Error deleting user: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
    // Remove delete parameter from URL after action
    header("Location: manage_users.php?msg=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit;
}

// =========================================
// ✅ Handle Add User (SECURE)
// =========================================
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Raw password
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password)) {
        $message = "Error: Name, email, and password are required.";
        $message_type = 'error';
    } else {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            $message = "New **$role** user '{$name}' added successfully.";
            $message_type = 'success';
        } else {
            $message = "Error adding user (Check if email is already used): " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
    header("Location: manage_users.php?msg=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit;
}

// =========================================
// ✅ Handle Update User (SECURE)
// =========================================
if (isset($_POST['update_user'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $role, $id);

    if ($stmt->execute()) {
        $message = "User ID **$id** (E-mail: {$email}) updated successfully to role '{$role}'.";
        $message_type = 'success';
    } else {
        $message = "Error updating user: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
    header("Location: manage_users.php?msg=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit;
}

// =========================================
// ✅ Initial Fetch and Message Display
// =========================================
$users = $conn->query("SELECT id, name, email, role FROM users ORDER BY role DESC, name ASC");

if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* * =========================================
 * 🎨 Harmonized CSS from admin.php
 * =========================================
 */
:root {
    --primary: #4e54c8;      
    --secondary: #8f94fb;    
    --accent: #ff6b6b;       
    --bg-color: #f4f7fc;     
    --text-dark: #2d3436;
    --text-light: #636e72;
    --white: #ffffff;
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg-color);
    margin: 0;
    padding: 0;
    color: var(--text-dark);
}

header { 
    background: var(--primary); 
    color: white; 
    padding: 15px 40px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
header h1{margin:0; font-size: 20px;}
header div { font-size: 14px; font-weight: 500; opacity: 0.9; }

/* Main Container for all content */
.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

/* User Add Form Styling (Card) */
#addUserForm {
    max-width: 900px;
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
#addUserForm h3 {
    grid-column: 1 / -1;
    color: var(--primary);
    margin-top: 0;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 10px;
}
#addUserForm input, #addUserForm select {
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    width: 100%;
    box-sizing: border-box;
    font-family: inherit;
}
#addUserForm #addBtn {
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    color: white;
    padding: 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: 0.3s;
}
#addUserForm #addBtn:hover {
    opacity: 0.9;
}

/* Table Styling */
table {
    width: 100%;
    margin: 30px 0;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--white);
    box-shadow: var(--shadow-soft);
    border-radius: 16px;
    overflow: hidden;
}
th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}
th {
    background: #e7e9ff;
    color: var(--primary);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
}
tr:last-child td {
    border-bottom: none;
}
/* Style for Admin rows */
tr:has(select[name="role"] option[value="admin"]:checked) {
    background: #fffbe6; /* Light yellow for admins */
}

/* Inline Form in Table */
table form {
    margin: 0;
    padding: 0;
    width: 100%;
    display: contents; /* Allows form contents to span table cells */
}
table input[type="text"], table input[type="email"], table select {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 90%;
    font-size: 14px;
}
/* Actions Column */
.action-cell {
    display: flex;
    gap: 10px;
    align-items: center;
}
.updateBtn {
    background: var(--primary);
    color: white;
    padding: 8px 12px;
    font-size: 12px;
}
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
    max-width: 900px;
    border-radius: 8px;
    font-weight: 500;
}
.message.success {
    background: #e6ffed;
    border: 1px solid #3ac47d;
    color: #2d6a4f;
}
.message.error {
    background: #ffe6e6;
    border: 1px solid var(--accent);
    color: #a04444;
}

/* Back Link */
.back-link {
    display: block;
    width: fit-content;
    margin: 20px auto 40px auto;
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

</style>
</head>
<body>

<header>
    <h1>Manage Users & Roles</h1>
    <div>**<?= $userName ?>** (<?= strtoupper($userRole) ?>)</div>
</header>

<div class="container">

    <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="addUserForm">
        <h3>➕ Add New User</h3>
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <select name="role" id="roleSelect" required>
            <option value="member">Member</option>
            <?php if ($userRole === 'super_admin'): ?>
                <option value="admin">Admin</option>
            <?php endif; ?>
        </select>

        <button type="submit" name="add_user" id="addBtn">Add Member</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($u = $users->fetch_assoc()): ?>
            <tr>
                <form method="POST">
                    <td><?= $u['id'] ?></td>
                    <td><input type="text" name="name" value="<?= htmlspecialchars($u['name']) ?>" required></td>
                    <td><input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required></td>
                    <td>
                        <select name="role" class="roleSelectUpdate">
                            <option value="member" <?= $u['role']=='member' ? 'selected' : '' ?>>Member</option>
                            <?php 
                            // Only allow Super Admins to select or change to 'admin' role
                            if ($userRole === 'super_admin' || $u['role'] === 'admin'): 
                            ?>
                                <option value="admin" <?= $u['role']=='admin' ? 'selected' : '' ?>>Admin</option>
                            <?php endif; ?>
                            </select>
                    </td>
                    <td class="action-cell">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" name="update_user" class="updateBtn">
                            Update
                        </button>
                        
                        <?php if ($u['id'] != $current_user_id): // Cannot delete self ?>
                            <a href="?delete=<?= $u['id'] ?>" class="delete-link" onclick="return confirm('WARNING: Are you sure you want to permanently delete user ID <?= $u['id'] ?>? This cannot be undone.')">Delete</a>
                        <?php else: ?>
                            <span style="color: <?= var_dump($u['role'] === 'super_admin') ? '#c0392b' : '#636e72'; ?>; font-size: 12px; font-weight: 600;">(You)</span>
                        <?php endif; ?>
                    </td>
                </form>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    
    <a href="admin.php" class="back-link">⬅ Back to Admin Dashboard</a>
</div>

<script>
    const roleSelect = document.getElementById('roleSelect');
    const addBtn = document.getElementById('addBtn');

    if (roleSelect && addBtn) {
        // Initial setup
        addBtn.textContent = roleSelect.value === 'admin' ? 'Add Admin' : 'Add Member';
        
        // Event listener
        roleSelect.addEventListener('change', () => {
            addBtn.textContent = roleSelect.value === 'admin' ? 'Add Admin' : 'Add Member';
        });
    }
</script>

</body>
</html>