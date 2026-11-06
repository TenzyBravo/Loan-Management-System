<?php
require_once __DIR__ . '/includes/security.php';
Security::secureSession();

if(!isset($_SESSION['login_id']))
    header('location:login.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Admin Dashboard | Basils Money Lenders</title>

<?php include('./header.php'); ?>

</head>
<style>
	body{
        background: #f9fafb;
        background: var(--gray-50, #f9fafb);
  }

  main#view-panel {
    margin-left: 260px !important;
    width: calc(100% - 260px);
    margin-top: 4.5rem;
    padding: 1.5rem;
  }

  .modal-dialog.large {
    width: 80% !important;
    max-width: unset;
  }
  .modal-dialog.mid-large {
    width: 50% !important;
    max-width: unset;
  }

  /* Modern Modal Styling */
  .modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  }

  .modal-header {
    background: linear-gradient(to right, #f9fafb, white);
    background: linear-gradient(to right, var(--gray-50, #f9fafb), white);
    border-bottom: 1px solid #e5e7eb;
    border-bottom: 1px solid var(--gray-200, #e5e7eb);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    padding: 1.25rem 1.5rem;
  }

  .modal-title {
    font-weight: 600;
    color: #1f2937;
    color: var(--gray-800, #1f2937);
  }

  .modal-footer {
    border-top: 1px solid #e5e7eb;
    border-top: 1px solid var(--gray-200, #e5e7eb);
    padding: 1rem 1.5rem;
    background: #f9fafb;
    background: var(--gray-50, #f9fafb);
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
  }
</style>

<body>
	<?php include 'topbar.php' ?>
	<?php include 'navbar.php' ?>
  <div class="toast" id="alert_toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-body text-white">
    </div>
  </div>
  <main id="view-panel" >
      <?php
      // Whitelist of allowed pages to prevent LFI vulnerability
      $allowed_pages = [
          'home',
          'borrowers',
          'manage_borrower',
          'loans',
          'manage_loan',
          'loan_applications_review',
          'payments',
          'manage_payment',
          'users',
          'manage_user',
          'plan',
          'loan_type',
          'customer_documents_admin',
          'admin_profile',
          'security_audit_log',
          'backup_export'
      ];
      $page = isset($_GET['page']) ? $_GET['page'] : 'home';

      // Validate page against whitelist
      if (!in_array($page, $allowed_pages)) {
          $page = 'home';
      }
      ?>
  	<?php include $page.'.php' ?>


  </main>

  <div id="preloader"></div>
  <a href="#" class="back-to-top"><i class="icofont-simple-up"></i></a>

  <!-- Modern Slide-Over Component -->
  <?php include 'components/slide-over.php'; ?>

<!-- Session Timeout Warning Modal -->
<div class="modal fade" id="session_timeout_modal" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fa fa-clock"></i> Session Expiring</h5>
            </div>
            <div class="modal-body text-center">
                <p>Your session will expire in</p>
                <h2 id="session-countdown" class="text-danger">5:00</h2>
                <p class="text-muted">Click below to continue working</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-block" id="extend-session-btn">
                    <i class="fa fa-refresh"></i> Stay Logged In
                </button>
                <button type="button" class="btn btn-outline-secondary btn-block" onclick="window.location.href='ajax.php?action=logout'">
                    <i class="fa fa-sign-out-alt"></i> Logout Now
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
      </div>
    </div>
  </div>
</body>
<script>
	// Enhanced loading with modern spinner
	window.start_load = function(message = 'Loading...'){
	  var loader = '<div id="preloader2" class="preloader-modern"><div class="spinner"></div><p>' + message + '</p></div>';
	  $('body').prepend(loader);
	}

	window.end_load = function(){
	  $('#preloader2').fadeOut('fast', function() {
	      $(this).remove();
	    })
	}

	window.uni_modal = function($title = '' , $url='',$size=""){
	  start_load('Loading form...')
	  $.ajax({
	      url:$url,
	      error:err=>{
	          console.log()
	          end_load()
	          alert_toast("An error occurred while loading the form", "danger")
	      },
	      success:function(resp){
	          if(resp){
	              $('#uni_modal .modal-title').html($title)
	              $('#uni_modal .modal-body').html(resp)
	              if($size != ''){
	                  $('#uni_modal .modal-dialog').addClass($size)
	              }else{
	                  $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md")
	              }
	              // Add CSRF token to dynamically loaded forms
	              var csrfToken = '<?php echo Security::generateCSRFToken(); ?>';
	              $('#uni_modal form').each(function() {
	                  if ($(this).find('input[name="csrf_token"]').length === 0) {
	                      $(this).append('<input type="hidden" name="csrf_token" value="' + csrfToken + '">');
	                  }
	              });
	              $('#uni_modal').modal('show')
	              end_load()
	          }
	      }
	  })
	}

	window._conf = function($msg='',$func='',$params = []){
	   $('#confirm_modal #confirm').attr('onclick',$func+"("+$params.join(',')+")")
	   $('#confirm_modal .modal-body').html($msg)
	   $('#confirm_modal').modal('show')
	}

	// Modern toast notification with icons
	window.alert_toast = function($msg = 'TEST', $bg = 'success', duration = 3000){
	  var icon = {
	    success: 'fa-check-circle',
	    danger: 'fa-exclamation-circle',
	    warning: 'fa-exclamation-triangle',
	    info: 'fa-info-circle'
	  }[$bg] || 'fa-info-circle';

	  var toast = $('<div class="toast-modern toast-' + $bg + '">' +
	    '<div class="toast-icon"><i class="fa ' + icon + '"></i></div>' +
	    '<div class="toast-content">' + $msg + '</div>' +
	    '<button class="toast-close"><i class="fa fa-times"></i></button>' +
	    '</div>');

	  $('body').append(toast);

	  setTimeout(function(){
	    toast.addClass('show');
	  }, 100);

	  setTimeout(function(){
	    toast.removeClass('show');
	    setTimeout(function(){ toast.remove(); }, 300);
	  }, duration);

	  toast.find('.toast-close').click(function(){
	    toast.removeClass('show');
	    setTimeout(function(){ toast.remove(); }, 300);
	  });
	}
  $(document).ready(function(){
    $('#preloader').fadeOut('fast', function() {
        $(this).remove();
      })

    // Add CSRF token to all forms automatically
    var csrfToken = '<?php echo Security::generateCSRFToken(); ?>';
    $('form').each(function() {
      // Check if form doesn't already have a CSRF token
      if ($(this).find('input[name="csrf_token"]').length === 0) {
        $(this).append('<input type="hidden" name="csrf_token" value="' + csrfToken + '">');
      }
    });
  })
  $('.datetimepicker').datetimepicker({
      format:'Y/m/d H:i',
      startDate: '+3d'
  })
  $('.select2').select2({
    placeholder:"Please select here",
    width: "100%"
  })

  // ==========================================
  // Session Timeout Warning System
  // ==========================================
  (function() {
    var sessionTimeout = <?php echo Security::SESSION_TIMEOUT; ?>;  // 30 minutes
    var warningTime = <?php echo Security::SESSION_WARNING; ?>;     // 5 minutes warning
    var checkInterval = 60000;  // Check every minute
    var countdownInterval = null;
    var warningShown = false;

    // Check session status periodically
    function checkSession() {
      $.ajax({
        url: 'ajax.php?action=check_session',
        method: 'GET',
        dataType: 'json',
        success: function(resp) {
          if (!resp.valid) {
            // Session expired - redirect to login
            alert_toast('Your session has expired. Please login again.', 'warning');
            setTimeout(function() {
              window.location.href = 'login.php?expired=1';
            }, 2000);
            return;
          }

          if (resp.warning && !warningShown) {
            // Show warning modal
            showTimeoutWarning(resp.remaining);
          }
        },
        error: function() {
          // Network error or session expired
        }
      });
    }

    // Show timeout warning modal with countdown
    function showTimeoutWarning(remaining) {
      warningShown = true;
      var countdown = remaining;

      updateCountdownDisplay(countdown);
      $('#session_timeout_modal').modal('show');

      // Start countdown
      countdownInterval = setInterval(function() {
        countdown--;
        updateCountdownDisplay(countdown);

        if (countdown <= 0) {
          clearInterval(countdownInterval);
          $('#session_timeout_modal').modal('hide');
          alert_toast('Your session has expired.', 'danger');
          setTimeout(function() {
            window.location.href = 'login.php?expired=1';
          }, 1500);
        }
      }, 1000);
    }

    // Format and display countdown
    function updateCountdownDisplay(seconds) {
      var mins = Math.floor(seconds / 60);
      var secs = seconds % 60;
      $('#session-countdown').text(mins + ':' + (secs < 10 ? '0' : '') + secs);

      // Change color as time runs out
      if (seconds <= 60) {
        $('#session-countdown').removeClass('text-warning').addClass('text-danger');
      } else {
        $('#session-countdown').removeClass('text-danger').addClass('text-warning');
      }
    }

    // Extend session button click
    $('#extend-session-btn').click(function() {
      var btn = $(this);
      btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Extending...');

      $.ajax({
        url: 'ajax.php?action=extend_session',
        method: 'POST',
        dataType: 'json',
        success: function(resp) {
          if (resp.status === 'ok') {
            // Clear countdown and hide modal
            clearInterval(countdownInterval);
            warningShown = false;
            $('#session_timeout_modal').modal('hide');
            alert_toast('Session extended successfully!', 'success');
          } else {
            // Session already expired
            window.location.href = 'login.php?expired=1';
          }
        },
        error: function() {
          alert_toast('Failed to extend session. Please try again.', 'danger');
        },
        complete: function() {
          btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Stay Logged In');
        }
      });
    });

    // Track user activity to extend session automatically
    var activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
    var lastActivity = Date.now();
    var activityThrottle = 60000;  // Only update once per minute

    function trackActivity() {
      var now = Date.now();
      if (now - lastActivity > activityThrottle) {
        lastActivity = now;
        // Silently extend session on activity
        $.post('ajax.php?action=extend_session');
      }
    }

    activityEvents.forEach(function(event) {
      document.addEventListener(event, trackActivity, { passive: true });
    });

    // Start periodic session checks
    setInterval(checkSession, checkInterval);

    // Initial check after page load
    setTimeout(checkSession, 5000);
  })();
</script>
</html>