<?php 
    require_once "include/header.php";
?>

<?php 
//  database connection
require_once "../connection.php";

$sql = "SELECT * FROM emp_leave WHERE status = 'pending' ";
$result = mysqli_query($conn , $sql);

$i = 1;
?>

<style>
table, th, td {
  border: 1px solid #007bff;
  padding: 15px;
}

table {
  border-spacing: 10px;
}

th {
  background-color: #007bff;
  color: white;
}

td {
  background-color: #e7f1ff;
}

.btn-outline-primary {
  color: #007bff;
  border-color: #007bff;
}

.btn-outline-primary:hover {
  background-color: #007bff;
  color: white;
}

.btn-outline-danger {
  color: #dc3545;
  border-color: #dc3545;
}

.btn-outline-danger:hover {
  background-color: #dc3545;
  color: white;
}

.container {
  background-color: white;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-top: 40px;
}
</style>

<div class="container">
    <div class="py-4 mt-5"> 
    <h4 class="text-center pb-3">Leave Requests</h4>
    <table style="width:100%" class="table-hover text-center ">
    <tr>
        <th>S.No.</th>
        <th>Employee Email</th>
        <th>Starting Date</th>
        <th>Ending Date</th> 
        <th>Total Days</th>
        <th>Reason</th>
        <th>Status</th>
    </tr>
    <?php 
    if(mysqli_num_rows($result) > 0){
        while($rows = mysqli_fetch_assoc($result)){
            $start_date = $rows["start_date"];
            $last_date = $rows["last_date"];
            $email = $rows["email"];
            $reason = $rows["reason"];
            $status = $rows["status"];   
            $id = $rows["id"]; 
            ?>
        <tr>
        <td><?php echo "$i."; ?></td>
        <td><?php echo $email; ?></td>
        <td><?php echo date("jS F", strtotime($start_date)); ?></td>
        <td><?php echo date("jS F", strtotime($last_date)); ?></td>
        <td><?php 
            $date1 = date_create($start_date);
            $date2 = date_create($last_date);
            $diff = date_diff($date1, $date2); 
            echo $diff->format("%a days");
        ?></td>
        <td><?php echo $reason; ?></td> 
        <td>
            <?php echo "<a href='accept-leave.php?id={$id}' class='btn btn-sm btn-outline-primary mr-2'>Accept</a>";
                  echo "<a href='cancel-leave.php?id={$id}' class='btn btn-sm btn-outline-danger'>Cancel</a>";
            ?>  
        </td> 
        </tr>
    <?php 
            $i++;
        }
    } else {
        echo "<script>
        $(document).ready(function(){
            $('#showModal').modal('show');
            $('#linkBtn').hide();
            $('#addMsg').text('No Leave Requests Found');
            $('#closeBtn').text('Ok, Understood');
        })
        </script>";
    }
    ?>
    </table>
    </div>
</div>

<?php 
    require_once "include/footer.php";
?>
