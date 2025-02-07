<?php 
require_once "include/header.php";
?>

<?php

// database connection
require_once "../connection.php";

$currentDay = date('Y-m-d', strtotime("today"));
$tomorrow = date('Y-m-d', strtotime("+1 day"));

$today_leave = 0;
$tomorrow_leave = 0;
$i = 1;

// total admin
$select_admins = "SELECT * FROM admin";
$total_admins = mysqli_query($conn, $select_admins);

// total employee
$select_emp = "SELECT * FROM employee";
$total_emp = mysqli_query($conn, $select_emp);

// employee on leave
$emp_leave  = "SELECT * FROM emp_leave";
$total_leaves = mysqli_query($conn, $emp_leave);

if (mysqli_num_rows($total_leaves) > 0) {
    while ($leave = mysqli_fetch_assoc($total_leaves)) {
        $leave_date = $leave["start_date"];

        // daywise
        if ($currentDay == $leave_date) {
            $today_leave += 1;
        } elseif ($tomorrow == $leave_date) {
            $tomorrow_leave += 1;
        }
    }
} else {
    echo "No leave found";
}

// highest paid employee
$sql_highest_salary = "SELECT * FROM employee ORDER BY salary DESC";
$emp_ = mysqli_query($conn, $sql_highest_salary);

?>

<div class="container">

    <div class="row mt-5">
        <div class="col-4">
            <div class="card shadow" style="width: 18rem; border-color: #007bff; border-width: 2px;">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item text-center" style="background-color: #007bff; color: #ffffff;">Admins</li>
                    <li class="list-group-item">Total Admin: <?php echo mysqli_num_rows($total_admins); ?> </li>
                    <li class="list-group-item text-center"><a href="manage-admin.php" style="color: #007bff; text-decoration: none;"><b>View All Admins</b></a></li>
                </ul>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow" style="width: 18rem; border-color: #007bff; border-width: 2px;">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item text-center" style="background-color: #007bff; color: #ffffff;">Employees</li>
                    <li class="list-group-item">Total Employees: <?php echo mysqli_num_rows($total_emp); ?></li>
                    <li class="list-group-item text-center"><a href="manage-employee.php" style="color: #007bff; text-decoration: none;"><b>View All Employees</b></a></li>
                </ul>
            </div>
        </div>
        <div class="col-4">
            <div class="card shadow" style="width: 18rem; border-color: #007bff; border-width: 2px;">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item text-center" style="background-color: #007bff; color: #ffffff;">Employees on Leave (Daywise)</li>
                    <li class="list-group-item">Today: <?php echo $today_leave; ?></li>
                    <li class="list-group-item">Tomorrow: <?php echo $tomorrow_leave; ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mt-5 bg-white shadow">
        <div class="col-12">
            <div class="text-center my-3">
                <h4 style="color: #007bff;">Employee Leadership Board</h4>
            </div>
            <table class="table table-hover">
                <thead>
                    <tr style="background-color: #007bff; color: #ffffff;">
                        <th scope="col">S.No.</th>
                        <th scope="col">Employee's Id</th>
                        <th scope="col">Employee's Name</th>
                        <th scope="col">Employee's Email</th>
                        <th scope="col">Salary in Rs.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($emp_info = mysqli_fetch_assoc($emp_)) {
                        $emp_id = $emp_info["id"];
                        $emp_name = $emp_info["name"];
                        $emp_email = $emp_info["email"];
                        $emp_salary = $emp_info["salary"];
                    ?>
                        <tr>
                            <th><?php echo "$i. "; ?></th>
                            <th><?php echo $emp_id; ?></th>
                            <td><?php echo $emp_name; ?></td>
                            <td><?php echo $emp_email; ?></td>
                            <td><?php echo $emp_salary; ?></td>
                        </tr>
                    <?php
                        $i++;
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require_once "include/footer.php";
?>
