<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom CSS */
        html, body {
            height: 100%;
            margin: 0;
        }

        .bg {
            background-color: blue; /* Example background color */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Full viewport height */
        }

        .border {
            border: 1px solid #dee2e6; /* Example border color */
        }

        .login-form-bg {
        background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        width: 100%; 
        max-width: 800px; /* Increased width */
    }

        .mt-5 {
            margin-top: 1.5rem;
        }

        .mt-4 {
            margin-top: 1.5rem;
        }

        .pb-4 {
            padding-bottom: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        .shadow {
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); /* Example shadow effect */
        }

        /* Button specific customizations */
        .btn-toolbar {
            display: flex;
            justify-content: space-between;
        }

        .btn-group {
            display: inline-flex;
        }
    </style>
</head>
<body>
    <div class="bg">
        <div class="login-form-bg border">
            <div class="form-input-content">
                <div class="card login-form mt-5">
                    <div class="card-body shadow">
                        <h2 class="text-center pb-4">Employee Management System</h2>
                        <h6 class="text-center pb-4">Please Log-In According To Your Role!!</h6>
                        <div class="container mt-4">
                            <div class="btn-toolbar justify-content-between">
                                <div class="btn-group">
                                    <a href="employee/dashboard.php" class="btn btn-primary btn-lg">Log-in As Employee</a>
                                </div>
                                <div class="btn-group">
                                    <a href="admin/dashboard.php" class="btn btn-primary btn-lg">Log-In As Admin</a>
                                </div>
                            </div>
                        </div>   
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
