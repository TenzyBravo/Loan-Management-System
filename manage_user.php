<?php require_once __DIR__ . '/includes/auth_guard.php'; require_staff(true); ?>
<?php
include('db_connect.php');
require_once 'includes/security.php';

// Generate CSRF token
Security::generateCSRFToken();

if(isset($_GET['id'])){
	$id = $_GET['id'];
	$stmt = $conn->prepare("SELECT * FROM users where id = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$user = $stmt->get_result();
	foreach($user->fetch_array() as $k =>$v){
		$meta[$k] = $v;
	}
	$stmt->close();
}
?>
<div class="container-fluid">

	<form action="" id="manage-user">
		<input type="hidden" name="id" value="<?php echo isset($meta['id']) ? $meta['id']: '' ?>">
		<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
		<div class="form-group">
			<label for="name">Name</label>
			<input type="text" name="name" id="name" class="form-control" value="<?php echo isset($meta['name']) ? $meta['name']: '' ?>" required>
		</div>
		<div class="form-group">
			<label for="username">Username</label>
			<input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" required>
		</div>
		<div class="form-group">
			<label for="password">Password</label>
			<input type="password" name="password" id="password" class="form-control" value="<?php echo isset($meta['password']) ? $meta['password']: '' ?>" required>
		</div>
		<div class="form-group">
			<label for="type">User Type</label>
			<select name="type" id="type" class="custom-select">
				<option value="1" <?php echo isset($meta['type']) && $meta['type'] == 1 ? 'selected': '' ?>>Admin</option>
				<option value="2" <?php echo isset($meta['type']) && $meta['type'] == 2 ? 'selected': '' ?>>Staff</option>
			</select>
		</div>
	</form>
</div>
<script>
	$('#manage-user').submit(function(e){
		e.preventDefault();
		start_load()
		$.ajax({
			url:'ajax.php?action=save_user',
			method:'POST',
			data:$(this).serialize(),
			success:function(resp){
				if(resp == 1){
					alert_toast("Data successfully saved",'success')
					setTimeout(function(){
						location.reload()
					},1500)
				} else {
					end_load()
					// Try to parse JSON error response
					try {
						var error = JSON.parse(resp);
						alert_toast(error.message || "An error occurred", 'error')
					} catch(e) {
						alert_toast("An error occurred: " + resp, 'error')
					}
				}
			},
			error:function(xhr, status, error){
				end_load()
				alert_toast("Error: " + error, 'error')
			}
		})
	})
</script>