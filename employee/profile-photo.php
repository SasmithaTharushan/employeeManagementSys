<?php 
require_once "include/header.php";
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.slim.min.js"></script>

<?php 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $filename = $_FILES["dp"]['name'];
    $temp_loc = $_FILES["dp"]["tmp_name"];
    $temp_ex = explode(".", $filename);
    $extension = strtolower(end($temp_ex));
    $allowed_types = array("png", "jpg", "jpeg");

    if (in_array($extension, $allowed_types)) {
        $new_file_name = uniqid("", true) . $filename;
        $location = "upload/" . $new_file_name;
        if (move_uploaded_file($temp_loc, $location)) {
            // database connection 
            require_once "../connection.php";
            $sql = "UPDATE employee SET dp = '$new_file_name' WHERE email = '$_SESSION[email_emp]'";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                echo "<script>
                $(document).ready(function() {
                    $('#showModal').modal('show');
                    $('#addMsg').text('Profile Photo Updated Successfully!');
                    $('#linkBtn').attr('href', 'profile.php');
                    $('#linkBtn').text('Check Profile');
                    $('#linkBtn').css('background-color', '#6f42c1').css('color', '#ffffff'); // Purple theme
                    $('#closeBtn').text('Upload Again');
                    $('#closeBtn').css('background-color', '#6f42c1').css('color', '#ffffff'); // Purple theme
                });
                </script>";
            }
        }
    } else {
        echo "<script>
        $(document).ready(function() {
            $('#showModal').modal('show');
            $('#addMsg').text('Only JPG, PNG, and JPEG files allowed!');
            $('#linkBtn').hide();
            $('#closeBtn').text('Ok, Understood');
            $('#closeBtn').css('background-color', '#6f42c1').css('color', '#ffffff'); // Purple theme
        });
        </script>";
    }
}
?>

<div style="margin-top:100px">
    <div class="login-form-bg h-100">
        <div class="container mt-5 h-100">
            <div class="row justify-content-center h-100">
                <div class="col-xl-6">
                    <div class="form-input-content">
                        <div class="card login-form mb-0">
                            <div class="card-body pt-5 shadow">
                                <h4 class="text-center">Change Profile Photo</h4>
                                <form method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="form-group">
                                        <label>Select Image:</label>
                                        <input type="file" name="dp" class="form-control">
                                    </div>

                                    <div class="btn-toolbar justify-content-between" role="toolbar" aria-label="Toolbar with button groups">
                                        <div class="btn-group">
                                            <input type="submit" value="Save Changes" class="btn" style="background-color: #6f42c1; color: #ffffff; border: none; padding: 15px; font-size: 16px; border-radius: 4px;" name="save_changes">
                                        </div>
                                        <div class="input-group">
                                            <a href="profile.php" class="btn" style="background-color: #6f42c1; color: #ffffff; border: none; padding: 15px; font-size: 16px; border-radius: 4px;">Close</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once "include/footer.php";
?>
