<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <link href="../css/style.css" rel="stylesheet">

    <title>Employee Management System</title>
    <style>
    body, html {
        height: 100%;
        margin: 0;
    }

    .bg {
        height: 100%; 
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: blue; /* Light gray background color */
    }

    .login-form-bg {
        background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        width: 100%; 
        max-width: 800px; /* Increased width */
    }
    </style>
  </head>
  <body>

  <!-- php script start -->
  <?php 
      $email_err = $pass_err = $login_Err = "";
      $email = $pass = "";

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (empty($_REQUEST["email"])) {
          $email_err = "<p style='color:red'> * Email Can Not Be Empty</p>";
        } else {
          $email = $_REQUEST["email"];
        }

        if (empty($_REQUEST["password"])) {
          $pass_err = "<p style='color:red'> * Password Can Not Be Empty</p>";
        } else {
          $pass = $_REQUEST["password"];
        }

        if (!empty($email) && !empty($pass)) {
          // database connection
          require_once "../connection.php";

          $sql_query = "SELECT * FROM admin WHERE email='$email' AND password='$pass'";
          $result = mysqli_query($conn, $sql_query);

          if (mysqli_num_rows($result) > 0) {
            while ($rows = mysqli_fetch_assoc($result)) {
              session_start();
              session_unset();
              $_SESSION["email"] = $rows["email"];
              header("Location: dashboard.php?login-success");
            }
          } else {
            $login_Err = "<div class='alert alert-warning alert-dismissible fade show'>
            <strong>Invalid Email/Password</strong>
            <button type='button' class='close' data-dismiss='alert'>
              <span aria-hidden='true'>&times;</span>
            </button>
          </div>";
          }
        }
      }
  ?>
  <!-- php script end -->

  <div class="bg">
    <div class="login-form-bg">
      <div class="form-input-content">
        <div class="card login-form mb-0">
          <div class="card-body pt-5 shadow">
            <h4 class="text-center">Hello, Admin</h4>
            <div class="text-center my-5"><?php echo $login_Err; ?></div>
            <form method="POST" action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>">
              <div class="form-group">
                <label>Email:</label>
                <input type="email" class="form-control" value="<?php echo $email; ?>" name="email">
                <?php echo $email_err; ?>
              </div>
              <div class="form-group">
                <label>Password:</label>
                <input type="password" class="form-control" name="password">
                <?php echo $pass_err; ?>
              </div>
              <div class="form-group">
                <input type="submit" value="Log-In" class="btn btn-primary btn-lg w-100" name="signin">
              </div>
              <p class="login-form__footer">Not an admin? <a href="../employee/login.php" class="text-primary">Log-In</a> as Employee now</p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  </body>
</html>
