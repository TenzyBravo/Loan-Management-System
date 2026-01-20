<style>
/* Modern Sidebar Styling */
nav#sidebar {
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
  width: 260px;
  box-shadow: 2px 0 10px rgba(0,0,0,0.1);
  height: 100%;
  position: fixed;
  z-index: 99;
  left: 0;
}

/* Override Bootstrap bg-warning */
nav#sidebar.bg-warning {
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
}

.sidebar-list {
  padding: 1rem 0;
}

/* Modern Navigation Items */
.nav-item {
  padding: 0.75rem 1.25rem;
  margin: 0.25rem 0.75rem;
  border-radius: 0.5rem;
  color: #cbd5e1;
  transition: all 0.2s ease;
  display: -ms-flexbox; /* IE11 */
  display: flex;
  -ms-flex-align: center; /* IE11 */
  align-items: center;
  text-decoration: none;
  border: none;
  background: transparent;
  position: relative;
}

.nav-item:hover {
  background: rgba(51, 65, 85, 0.7);
  color: #fff;
  transform: translateX(4px);
  text-decoration: none;
}

.nav-item.active {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #fff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 60%;
  background: #fff;
  border-radius: 0 4px 4px 0;
}

.nav-item .icon-field {
  width: 20px;
  display: -ms-inline-flexbox; /* IE11 */
  display: inline-flex;
  -ms-flex-pack: center; /* IE11 */
  justify-content: center;
  margin-right: 0.75rem;
  font-size: 1.1rem;
}

/* Mobile Toggle */
.sidebar-toggle {
  display: none;
  position: fixed;
  top: 70px;
  left: 1rem;
  z-index: 98;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  cursor: pointer;
}

@media (max-width: 991px) {
  nav#sidebar {
    left: -260px;
  }

  nav#sidebar.active {
    left: 0;
  }

  .sidebar-toggle {
    display: block;
  }

  main#view-panel {
    margin-left: 0 !important;
  }
}

/* Sidebar transition for smooth animation */
nav#sidebar {
  transition: left 0.3s ease;
}
</style>

<!-- Mobile Sidebar Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
  <i class="fa fa-bars"></i>
</button>

<nav id="sidebar" class='mx-lt-5 bg-warning' >

		<div class="sidebar-list">

				<a href="admin.php?page=home" class="nav-item nav-home"><span class='icon-field'><i class="fa fa-home"></i></span> Home</a>
				<a href="admin.php?page=loan_applications_review" class="nav-item nav-loan_applications_review"><span class='icon-field'><i class="fa fa-clipboard-check"></i></span> Loan Applications Review</a>
				<a href="admin.php?page=loans" class="nav-item nav-loans"><span class='icon-field'><i class="fa fa-file-invoice-dollar"></i></span> Loans</a>
				<a href="admin.php?page=payments" class="nav-item nav-payments"><span class='icon-field'><i class="fa fa-money-bill"></i></span> Payments</a>
				<a href="admin.php?page=borrowers" class="nav-item nav-borrowers"><span class='icon-field'><i class="fa fa-user-friends"></i></span> Borrowers</a>
				<a href="admin.php?page=customer_documents_admin" class="nav-item nav-customer_documents_admin"><span class='icon-field'><i class="fa fa-folder-open"></i></span> Customer Documents</a>
				<a href="admin.php?page=plan" class="nav-item nav-plan"><span class='icon-field'><i class="fa fa-list-alt"></i></span> Loan Plans</a>
				<a href="admin.php?page=loan_type" class="nav-item nav-loan_type"><span class='icon-field'><i class="fa fa-th-list"></i></span> Loan Types</a>
				<?php if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 1): ?>
				<a href="admin.php?page=users" class="nav-item nav-users"><span class='icon-field'><i class="fa fa-users"></i></span> Users</a>

			<?php endif; ?>
		</div>

</nav>
<script>
	$('.nav-<?php echo isset($_GET['page']) ? $_GET['page'] : '' ?>').addClass('active')

	// Mobile sidebar toggle functionality
	function toggleSidebar() {
		var sidebar = document.getElementById('sidebar');
		sidebar.classList.toggle('active');
	}

	// Close sidebar when clicking outside on mobile
	$(document).ready(function() {
		$(document).on('click', function(e) {
			var sidebar = $('#sidebar');
			var toggle = $('#sidebarToggle');

			// Only apply on mobile/tablet
			if ($(window).width() <= 991) {
				// If click is outside sidebar and toggle button
				if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 &&
				    !toggle.is(e.target) && toggle.has(e.target).length === 0) {
					sidebar.removeClass('active');
				}
			}
		});

		// Close sidebar when clicking a nav item on mobile
		$('.nav-item').on('click', function() {
			if ($(window).width() <= 991) {
				$('#sidebar').removeClass('active');
			}
		});
	});
</script>