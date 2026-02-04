<style>
/* Modern Topbar Styling */
.navbar.topbar {
  background: #ffffff !important;
  background: var(--topbar-bg, #ffffff) !important;
  border-bottom: 1px solid #e5e7eb;
  border-bottom: 1px solid var(--topbar-border, #e5e7eb);
  height: 64px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  padding: 0 !important;
}

.navbar.topbar.bg-success {
  background: #ffffff !important;
  background: var(--topbar-bg, #ffffff) !important;
}

.topbar .logo {
  margin: auto;
  font-size: 24px;
  padding: 8px 12px;
  border-radius: 0.5rem;
  color: #2563eb;
  color: var(--primary-blue, #2563eb);
  background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
  background: linear-gradient(135deg, var(--primary-light, #dbeafe) 0%, #eff6ff 100%);
}

.topbar .navbar-brand {
  color: #1f2937;
  color: var(--gray-800, #1f2937);
  font-weight: 600;
  font-size: 1.125rem;
  margin-left: 1rem;
}

.topbar-user {
  display: -ms-flexbox; /* IE11 */
  display: flex;
  -ms-flex-align: center; /* IE11 */
  align-items: center;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  transition: background 0.2s;
  color: #1f2937;
  color: var(--gray-800, #1f2937);
  text-decoration: none;
}

.topbar-user:hover {
  background: #f3f4f6;
  background: var(--gray-100, #f3f4f6);
  text-decoration: none;
  color: #1f2937;
  color: var(--gray-800, #1f2937);
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  display: -ms-inline-flexbox; /* IE11 */
  display: inline-flex;
  -ms-flex-align: center; /* IE11 */
  align-items: center;
  -ms-flex-pack: center; /* IE11 */
  justify-content: center;
  color: white;
  font-weight: 600;
  margin-right: 0.75rem;
  font-size: 0.875rem;
}

.topbar-logout {
  margin-left: 1rem;
  color: #6b7280;
  color: var(--gray-500, #6b7280);
  transition: color 0.2s;
}

.topbar-logout:hover {
  color: #ef4444;
  color: var(--danger, #ef4444);
}

.logout-btn-visible {
  background: #dc3545;
  color: white !important;
  padding: 8px 16px;
  border-radius: 6px;
  margin-left: 15px;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.logout-btn-visible:hover {
  background: #c82333;
  color: white !important;
  text-decoration: none;
  transform: translateY(-1px);
}

/* Notification Bell Styles */
.notification-bell {
  position: relative;
  padding: 8px 12px;
  margin-right: 15px;
  cursor: pointer;
  border-radius: 8px;
  transition: background 0.2s;
}

.notification-bell:hover {
  background: #f3f4f6;
}

.notification-bell i {
  font-size: 1.25rem;
  color: #6b7280;
}

.notification-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  background: #ef4444;
  color: white;
  font-size: 0.7rem;
  font-weight: 600;
  min-width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
}

.notification-dropdown {
  width: 350px;
  max-height: 400px;
  overflow-y: auto;
  padding: 0;
}

.notification-header {
  padding: 12px 15px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f9fafb;
}

.notification-header h6 {
  margin: 0;
  font-weight: 600;
}

.notification-item {
  padding: 12px 15px;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  gap: 12px;
  transition: background 0.2s;
  text-decoration: none;
  color: inherit;
}

.notification-item:hover {
  background: #f9fafb;
  text-decoration: none;
  color: inherit;
}

.notification-item.unread {
  background: #eff6ff;
}

.notification-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-content strong {
  display: block;
  font-size: 0.875rem;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notification-content p {
  margin: 0;
  font-size: 0.8rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notification-time {
  font-size: 0.7rem;
  color: #9ca3af;
  margin-top: 3px;
}

.notification-empty {
  padding: 30px;
  text-align: center;
  color: #9ca3af;
}

.notification-footer {
  padding: 10px 15px;
  text-align: center;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.notification-footer a {
  font-size: 0.875rem;
  color: #2563eb;
  text-decoration: none;
}
</style>

<?php
// Load notification functions
if (file_exists('includes/notifications.php')) {
    require_once 'includes/notifications.php';
}

// Get pending loan applications count (quick count for badge)
$pending_loans_count = 0;
$notification_count = 0;
$notifications = [];

if (isset($conn)) {
    // Count pending loan applications
    $pending_result = $conn->query("SELECT COUNT(*) as count FROM loan_list WHERE status = 0");
    if ($pending_result) {
        $pending_loans_count = $pending_result->fetch_assoc()['count'];
    }

    // Get admin notifications if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
    if ($table_check && $table_check->num_rows > 0) {
        $notification_count = get_admin_notification_count($conn);
        $notifications = get_admin_notifications($conn, 5);
    }
}

// Total badge count
$total_badge = $pending_loans_count + $notification_count;
?>

<nav class="navbar navbar-light fixed-top bg-success topbar" style="padding:0;">
  <div class="container-fluid" style="padding: 0.5rem 1rem;">
  	<div class="d-flex align-items-center">
  		<div class="logo">
  			<span class="fa fa-money-check-alt"></span>
  		</div>
      <div class="navbar-brand mb-0">
        <b>Brian Investments</b>
      </div>
    </div>

	  <div class="d-flex align-items-center">
	  	<!-- Notification Bell -->
	  	<div class="dropdown">
	  		<div class="notification-bell" data-toggle="dropdown">
	  			<i class="fa fa-bell"></i>
	  			<?php if ($total_badge > 0): ?>
	  			<span class="notification-badge"><?php echo $total_badge > 99 ? '99+' : $total_badge; ?></span>
	  			<?php endif; ?>
	  		</div>
	  		<div class="dropdown-menu dropdown-menu-right notification-dropdown">
	  			<div class="notification-header">
	  				<h6><i class="fa fa-bell mr-2"></i>Notifications</h6>
	  				<?php if ($notification_count > 0): ?>
	  				<a href="ajax.php?action=mark_all_notifications_read" class="text-primary" style="font-size: 0.8rem;">Mark all read</a>
	  				<?php endif; ?>
	  			</div>

	  			<?php if ($pending_loans_count > 0): ?>
	  			<a href="admin.php?page=loan_applications_review" class="notification-item unread">
	  				<div class="notification-icon" style="background: #fef3c7;">
	  					<i class="fa fa-file-alt text-warning"></i>
	  				</div>
	  				<div class="notification-content">
	  					<strong>Pending Loan Applications</strong>
	  					<p><?php echo $pending_loans_count; ?> application<?php echo $pending_loans_count > 1 ? 's' : ''; ?> awaiting review</p>
	  				</div>
	  			</a>
	  			<?php endif; ?>

	  			<?php if (!empty($notifications)): ?>
	  				<?php foreach ($notifications as $notif): ?>
	  				<a href="<?php echo $notif['reference_type'] == 'loan' ? 'admin.php?page=loan_applications_review' : '#'; ?>"
	  				   class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
	  					<div class="notification-icon">
	  						<i class="fa <?php echo get_notification_icon($notif['type']); ?>"></i>
	  					</div>
	  					<div class="notification-content">
	  						<strong><?php echo htmlspecialchars($notif['title']); ?></strong>
	  						<p><?php echo htmlspecialchars($notif['message']); ?></p>
	  						<div class="notification-time"><?php echo time_ago($notif['created_at']); ?></div>
	  					</div>
	  				</a>
	  				<?php endforeach; ?>
	  			<?php endif; ?>

	  			<?php if ($total_badge == 0 && empty($notifications)): ?>
	  			<div class="notification-empty">
	  				<i class="fa fa-check-circle" style="font-size: 2rem; color: #d1d5db;"></i>
	  				<p class="mt-2 mb-0">All caught up!</p>
	  			</div>
	  			<?php endif; ?>

	  			<div class="notification-footer">
	  				<a href="admin.php?page=loan_applications_review">View all applications</a>
	  			</div>
	  		</div>
	  	</div>

	  	<!-- User Dropdown -->
	  	<div class="dropdown">
	  		<?php
	  		$user_name = isset($_SESSION['login_name']) ? $_SESSION['login_name'] : 'Guest';
	  		$initials = strtoupper(substr($user_name, 0, 1));
	  		?>
	  		<div class="topbar-user dropdown-toggle" data-toggle="dropdown" style="cursor: pointer;">
	  			<div class="user-avatar"><?php echo $initials ?></div>
	  			<span style="font-weight: 500;"><?php echo $user_name ?></span>
	  		</div>
	  		<div class="dropdown-menu dropdown-menu-right" style="margin-top: 10px;">
	  			<a class="dropdown-item" href="admin.php?page=admin_profile">
	  				<i class="fa fa-user-cog mr-2"></i> My Profile
	  			</a>
	  			<div class="dropdown-divider"></div>
	  			<a class="dropdown-item text-danger" href="ajax.php?action=logout">
	  				<i class="fa fa-power-off mr-2"></i> Logout
	  			</a>
	  		</div>
	  	</div>
	  	<a href="ajax.php?action=logout" class="logout-btn-visible">
	  		<i class="fa fa-sign-out-alt"></i> Logout
	  	</a>
	  </div>
  </div>

</nav>