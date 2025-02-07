<?php 
    require_once "include/header.php";
?>

<?php 
//  database connection
require_once "../connection.php";

$sql = "SELECT * FROM employee";
$result = mysqli_query($conn , $sql);

$i = 1;
$you = "";
?>

<div class="container" style="background-color: #f3e8f8; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <div class="py-4 mt-5"> 
        <h4 class="text-center pb-3" style="color: #6f42c1;">All Employees</h4>
        <table style="width:100%; border-collapse: collapse;" class="table-hover text-center">
            <tr style="background-color: #6f42c1; color: #ffffff;">
                <th style="border: 1px solid #6f42c1; padding: 15px;">S.No.</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Employee Id</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Name</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Email</th> 
                <th style="border: 1px solid #6f42c1; padding: 15px;">Gender</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Date of Birth</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Age</th>
            </tr>
            <?php 
            if( mysqli_num_rows($result) > 0){
                while( $rows = mysqli_fetch_assoc($result) ){
                    $name= $rows["name"];
                    $email= $rows["email"];
                    $dob = $rows["dob"];
                    $gender = $rows["gender"];
                    $id = $rows["id"];
                    if($gender == "" ){
                        $gender = "Not Defined";
                    } 

                    if($dob == "" ){
                        $dob = "Not Defined";
                        $age = "Not Defined";
                    }else{
                        $date1=date_create($dob);
                        $date2=date_create("now");
                        $diff=date_diff($date1,$date2);
                        $age = $diff->format("%y Years"); 
                    }

                    if($email == $_SESSION["email_emp"] ){
                        $name = "{$name} (You)";
                    } 
                    
                    ?>
                <tr>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $i; ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $id; ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $name ; ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $email; ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $gender; ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $dob; ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $age; ?></td> 
                </tr>
            <?php 
                $i++;
                }
            }else{
                echo "<tr><td colspan='7' style='text-align: center; padding: 15px;'>No employees found</td></tr>";
            }
            ?>
        </table>
    </div>
</div>

<?php 
    require_once "include/footer.php";
?>
