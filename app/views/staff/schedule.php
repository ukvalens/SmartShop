<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();\n$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

$message = '';

// Handle schedule creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_schedule'])) {
    $user_id = $_POST['user_id'];
    $shift_date = $_POST['shift_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $shift_type = $_POST['shift_type'];
    
    $stmt = $conn->prepare("INSERT INTO staff_schedules (user_id, shift_date, start_time, end_time, shift_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $shift_date, $start_time, $end_time, $shift_type);
    
    if ($stmt->execute()) {
        $message = 'Schedule created successfully!';
    } else {
        $message = 'Failed to create schedule.';
    }
}

// Get staff members
$staff = $conn->query("SELECT user_id, full_name, role FROM users WHERE role IN ('Manager', 'Cashier') ORDER BY full_name");

// Get current week schedules
$schedules = $conn->query("
    SELECT ss.*, u.full_name 
    FROM staff_schedules ss
    JOIN users u ON ss.user_id = u.user_id
    WHERE ss.shift_date >= CURDATE() AND ss.shift_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY ss.shift_date, ss.start_time
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Schedule - SmartSHOP</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/dashboard.css">
</head>
<body>
    <div class="language-selector">
        <select onchange="changeLanguage(this.value)">
            <option value="en">English</option>
            <option value="rw">Kinyarwanda</option>
        </select>
    </div>

    <div class="dashboard-container">
        <nav class="top-nav">
            <div class="nav-links">
                <a href="../dashboard/index.php"><?php echo Language::get('dashboard', $lang); ?></a>
                <a href="../pos/index.php">POS</a>
                <a href="../inventory/index.php">Inventory</a>
                <a href="../reports/daily.php">Reports</a>
                <a href="../customers/index.php">Customers</a>
            </div>
        </nav>
        
        <header class="header">
            <h1>👨💼 Staff Schedule</h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php" class="profile-link"><?php echo Language::get('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::get('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="schedule-actions">
                <button onclick="openScheduleModal()" class="btn">Create Schedule</button>
            </div>

            <div class="schedule-grid">
                <h2>This Week's Schedule</h2>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo Language::get('date', $lang); ?></th>
                            <th>Staff Member</th>
                            <th><?php echo Language::get('shift_type', $lang); ?></th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th><?php echo Language::get('status', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($schedule = $schedules->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($schedule['shift_date'])); ?></td>
                                <td><?php echo $schedule['full_name']; ?></td>
                                <td><?php echo ucfirst($schedule['shift_type']); ?></td>
                                <td><?php echo date('H:i', strtotime($schedule['start_time'])); ?></td>
                                <td><?php echo date('H:i', strtotime($schedule['end_time'])); ?></td>
                                <td>
                                    <?php 
                                    $today = date('Y-m-d');
                                    $shift_date = $schedule['shift_date'];
                                    if ($shift_date < $today) echo '<span class="status-completed"><?php echo Language::get('completed', $lang); ?></span>';
                                    elseif ($shift_date == $today) echo '<span class="status-active"><?php echo Language::get('active', $lang); ?></span>';
                                    else echo '<span class="status-scheduled">Scheduled</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <!-- Schedule Modal -->
    <div id="schedule-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeScheduleModal()">&times;</span>
            <h2>Create Schedule</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Staff Member</label>
                    <select name="user_id" class="form-input" required>
                        <option value="">Select Staff</option>
                        <?php while ($member = $staff->fetch_assoc()): ?>
                            <option value="<?php echo $member['user_id']; ?>"><?php echo $member['full_name']; ?> (<?php echo $member['role']; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('date', $lang); ?></label>
                    <input type="date" name="shift_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('shift_type', $lang); ?></label>
                    <select name="shift_type" class="form-input" required>
                        <option value="morning">Morning Shift</option>
                        <option value="afternoon">Afternoon Shift</option>
                        <option value="evening">Evening Shift</option>
                        <option value="full_day">Full Day</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-input" required>
                </div>
                <button type="submit" name="create_schedule" class="btn">Create Schedule</button>
            </form>
        </div>
    </div>

    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            left: auto !important;
            z-index: 1000 !important;
        }
    .schedule-actions {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .schedule-grid {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .schedule-grid table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .schedule-grid th,
    .schedule-grid td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .schedule-grid th {
        background: var(--background);
        font-weight: 600;
    }
    
    .status-completed { color: #28a745; font-weight: bold; }
    .status-active { color: #007bff; font-weight: bold; }
    .status-scheduled { color: #6c757d; }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 5vh auto;
        padding: 2rem;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        max-height: 85vh;
        overflow-y: auto;
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: black;
    }
    </style>

    <script>
        function openScheduleModal() {
            document.getElementById('schedule-modal').style.display = 'block';
        }
        
        function closeScheduleModal() {
            document.getElementById('schedule-modal').style.display = 'none';
        }
        
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('schedule-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>