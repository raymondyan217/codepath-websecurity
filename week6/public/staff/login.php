<?php
require_once('../../private/initialize.php');

// Until we learn about encryption, we will use an unencrypted
// master password as a stand-in. It should go without saying
// that this should *never* be done in real production code.
$master_password = 'secret';

// Set default values for all variables the page needs.
$errors = array();
$username = '';
$password = '';

if(is_post_request() && request_is_same_domain()) {
  ensure_csrf_token_valid();

  // Confirm that values are present before accessing them.
  if(isset($_POST['username'])) { $username = $_POST['username']; }
  if(isset($_POST['password'])) { $password = $_POST['password']; }

  // Validations
  if (is_blank($username)) {
    $errors[] = "Username cannot be blank.";
  }
  if (is_blank($password)) {
    $errors[] = "Password cannot be blank.";
  }

  // If there were no errors, submit data to database
  if (empty($errors)) {

    $users_result = find_users_by_username($username);
    // No loop, only one result
    $user = db_fetch_assoc($users_result);


    if($user) {

      $failed_login_user = find_failed_login($username);

      $failed_login = db_fetch_assoc($failed_login_user);

      $future_date = (strtotime($failed_login['last_attempt']) + (60 * 5));

      $time = time();

      if($future_date < $time) {
        reset_failed_login($username);
      }

      if($failed_login['count'] >= 5) {
        $minutes = ceil(($future_date - $time) / 60);

        $errors[] = "Too many failed logins for this username. You will need to wait ".$minutes." minutes before attempting another login.";
      } else {
        if(password_verify($password, $user['hashed_password'])
          // || $password === 'secret'
          ) {
          // Username found, password matches
          reset_failed_login($username);
          log_in_user($user);
          // Redirect to the staff menu after login
          redirect_to('index.php');
        } else {
          // Username found, but password does not match.
          record_failed_login($username);

          $errors[] = "Log in was unsuccessful.";
        }
      }
    } else {
      // No username found
      $errors[] ="Log in was unsuccessful.";
    }
  }
}

?>
<?php $page_title = 'Log in'; ?>
<?php include(SHARED_PATH . '/header.php'); ?>
<div id="menu">
  <ul>
    <li><a href="../index.php">Public Site</a></li>
  </ul>
</div>

<div id="main-content">
  <h1>Log in</h1>

  <?php echo display_errors($errors); ?>

  <form action="login.php" method="post">
    <?php echo csrf_token_tag(); ?>
    Username:<br />
    <input type="text" name="username" value="<?php echo h($username); ?>" /><br />
    Password:<br />
    <input type="password" name="password" value="" /><br />
    <input type="submit" name="submit" value="Submit"  />
  </form>

</div>

<?php include(SHARED_PATH . '/footer.php'); ?>
