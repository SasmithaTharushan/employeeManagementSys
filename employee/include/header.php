<?php 
    session_start();
    if( empty($_SESSION["email_emp"]) ){
        header("Location: ./login.php");
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar With Bootstrap</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="..\resorce\csss\style.css">
</head>

<body>
    

    <div class="wrapper">
        <aside id="sidebar">
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <i class="lni lni-grid-alt"></i>
                </button>
                <div class="sidebar-logo">
                    <a href="#">Abcd Solution</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="./dashboard.php" class="sidebar-link">
                        <i class="lni lni-user"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="./leave-status.php" class="sidebar-link">
                        <i class="lni lni-user"></i>
                        <span>Leave Status</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="./apply-leave.php" class="sidebar-link">
                        <i class="lni lni-user"></i>
                        <span>Apply for Leave</span>
                    </a>
                </li>
               
                <li class="sidebar-item">
                    <a href="./view-employee.php" class="sidebar-link">
                        <i class="lni lni-popup"></i>
                        <span>Viwe All  Employee </span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="./profile.php" class="sidebar-link">
                        <i class="lni lni-cog"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <a href="./logout.php" class="sidebar-link">
                    <i class="lni lni-exit"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        <div class="main p-3">
            <div class="text-center">
                <h1>
                   Employee Management System 
                </h1>
            

       
    
               
 

    <div class="container-fluid">

            