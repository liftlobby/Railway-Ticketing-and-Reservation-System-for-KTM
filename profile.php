<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = null;
$activities = [];
$stats = [
    'total_bookings' => 0,
    'active_bookings' => 0,
    'completed_trips' => 0,
    'cancelled_bookings' => 0
];

try {
    // Fetch user data
    $sql = "SELECT username, email, no_phone, created_at, last_login FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $user = $result->fetch_assoc();

    // Fetch recent activities
    $activity_sql = "SELECT action, description, created_at 
                    FROM activity_logs 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5";
    $activity_stmt = $conn->prepare($activity_sql);
    $activity_stmt->bind_param("i", $user_id);
    $activity_stmt->execute();
    $activities = $activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch booking statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_bookings,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
                  FROM tickets 
                  WHERE user_id = ?";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("i", $user_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: index.php");
    exit();
}

// If we get here, we should have valid user data
if (!$user) {
    $_SESSION['error_message'] = "Unable to load user profile";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - KTM Railway System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .profile-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #0056b3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            font-weight: bold;
        }
        .profile-info {
            flex: 1;
        }
        .profile-info h1 {
            margin: 0 0 10px 0;
            color: #0056b3;
        }
        .profile-actions {
            display: flex;
            gap: 15px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .profile-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .profile-card h2 {
            color: #0056b3;
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #0056b3;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0056b3;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            margin-right: 10px;
            color: #0056b3;
        }
        .activity-date {
            color: #6c757d;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #0056b3;
            color: white;
        }
        .btn-primary:hover {
            background-color: #003d82;
            transform: translateY(-2px);
        }
        .info-row {
            margin: 10px 0;
            display: flex;
            align-items: center;
        }
        .info-label {
            width: 120px;
            color: #6c757d;
            font-weight: 500;
        }
        .info-value {
            color: #212529;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            </div>
            <div class="profile-actions">
                <a href="edit_profile.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>

        <div class="profile-grid">
            <div class="profile-card">
                <h2>Personal Information</h2>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['no_phone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login</span>
                    <span class="info-value">
                        <?php echo $user['last_login'] ? date('d M Y, h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                    </span>
                </div>
            </div>

            <div class="profile-card">
                <h2>Booking Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_bookings'] ?? 0; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['active_bookings'] ?? 0; ?></div>
                        <div class="stat-label">Active Bookings</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['completed_trips'] ?? 0; ?></div>
                        <div class="stat-label">Completed Trips</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['cancelled_bookings'] ?? 0; ?></div>
                        <div class="stat-label">Cancelled Bookings</div>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <h2>Recent Activities</h2>
                <?php if (empty($activities)): ?>
                    <p class="text-muted">No recent activities</p>
                <?php else: ?>
                    <ul class="activity-list">
                        <?php foreach ($activities as $activity): ?>
                            <li class="activity-item">
                                <?php
                                $icon = 'fa-circle-info';
                                switch ($activity['action']) {
                                    case 'login':
                                        $icon = 'fa-sign-in-alt';
                                        break;
                                    case 'booking':
                                        $icon = 'fa-ticket';
                                        break;
                                    case 'payment':
                                        $icon = 'fa-credit-card';
                                        break;
                                    case 'profile_update':
                                        $icon = 'fa-user-edit';
                                        break;
                                }
                                ?>
                                <i class="fas <?php echo $icon; ?> activity-icon"></i>
                                <?php echo htmlspecialchars(ucfirst($activity['description'])); ?>
                                <br>
                                <span class="activity-date">
                                    <?php echo date('d M Y, h:i A', strtotime($activity['created_at'])); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>
</body>
</html>
