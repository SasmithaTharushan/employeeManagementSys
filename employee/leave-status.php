
<?php 
    require_once "include/header.php";
?>

<?php 
 
  $email = $_SESSION["email_emp"];
//  database connection
require_once "../connection.php";

$sql = "SELECT * FROM emp_leave WHERE email = '$email'  ";
$result = mysqli_query($conn , $sql);

$i = 1;
?>

<div class="container" style="background-color: #f4e6ff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <div class="py-4 mt-5"> 
        <h4 class="text-center pb-3" style="color: #6f42c1;">Leave Status</h4>
        <table style="width:100%; border-collapse: collapse;" class="table-hover text-center">
            <tr style="background-color: #6f42c1; color: #ffffff;">
                <th style="border: 1px solid #6f42c1; padding: 15px;">S.No.</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Starting Date</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Ending Date</th> 
                <th style="border: 1px solid #6f42c1; padding: 15px;">Total Days</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Reason</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Status</th>
                <th style="border: 1px solid #6f42c1; padding: 15px;">Action</th>
            </tr>
            <?php 
    
            if( mysqli_num_rows($result) > 0){
                while( $rows = mysqli_fetch_assoc($result) ){
                    $start_date= $rows["start_date"];
                    $last_date = $rows["last_date"];
                    $email= $rows["email"];
                    $reason = $rows["reason"];
                    $status = $rows["status"]; 
                    $id = $rows["id"];   
                    ?>
                <tr>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo "$i."; ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo date("jS F", strtotime($start_date)); ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo date("jS F", strtotime($last_date)); ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php 
                        $date1=date_create($start_date);
                        $date2=date_create($last_date);
                        $diff=date_diff($date1,$date2); 
                        echo $diff->format("%a days");
                    ?></td>
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $reason; ?></td> 
                    <td style="border: 1px solid #6f42c1; padding: 15px;"><?php echo $status; ?></td> 
                    <td style="border: 1px solid #6f42c1; padding: 15px;">  
                        <?php 
                            $delete_icon = " <a href='delete-leave.php?id={$id}' id='bin' class='btn-sm' style='background-color: #6f42c1; color: #ffffff; border: none; padding: 5px 10px; border-radius: 4px;'> <span ><i class='fa fa-trash '></i></span> </a>";
                            echo  $delete_icon;
                        ?> 
                    </td>
                <?php 
                    $i++;
                }
            } else {
                echo "<script>
                $(document).ready(function(){
                    $('#showModal').modal('show');
                    $('#addMsg').text('No leave Applied by you!!');
                    $('#linkBtn').attr('href', 'apply-leave.php');
                    $('#linkBtn').text('Apply for Leave');
                    $('#closeBtn').text('Remind me Later');
                })
            </script>";
            }
            ?>
            </tr>
        </table>
    </div>
</div>

<?php 
    require_once "include/footer.php";
?>
