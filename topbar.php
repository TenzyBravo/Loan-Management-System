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
</style>

<nav class="navbar navbar-light fixed-top bg-success topbar" style="padding:0;">
  <div class="container-fluid" style="padding: 0.5rem 1rem;">
  	<div class="d-flex align-items-center">
  		<div class="logo">
  			<span class="fa fa-money-check-alt"></span>
  		</div>
      <div class="navbar-brand mb-0">
        <b>Loan Management System</b>
      </div>
    </div>

	  <div class="topbar-user">
	  	<?php
	  	$user_name = isset($_SESSION['login_name']) ? $_SESSION['login_name'] : 'Guest';
	  	$initials = strtoupper(substr($user_name, 0, 1));
	  	?>
	  	<div class="user-avatar"><?php echo $initials ?></div>
	  	<span style="font-weight: 500;"><?php echo $user_name ?></span>
	  	<a href="ajax.php?action=logout" class="topbar-logout">
	  		<i class="fa fa-power-off"></i>
	  	</a>
	  </div>
  </div>

</nav>