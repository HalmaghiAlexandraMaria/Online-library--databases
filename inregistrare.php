<html>
<head>
<title>Register Page</title>
</head>
<body> 

<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connect to Oracle database
    $conn = oci_connect('hr', 'hr', 'localhost/XE');

    // Check if connection is successful
    //if (!$conn) {
        //echo 'Failed to connect to Oracle';
       // exit; // Exit the script if connection fails
    //} else {
       // echo 'Successfully connected with Oracle DB';
    //}

    // Prepare and bind the SQL statement
    $sql = "INSERT INTO clienti (cod_client, fullname, username, password) VALUES (pk_clienti.nextval, :fullname, :username, :password)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':fullname', $_POST['fullname']);
    oci_bind_by_name($stmt, ':username', $_POST['username']);
    oci_bind_by_name($stmt, ':password', $_POST['password']);

    // Execute the statement
    if (!oci_execute($stmt)) {
    $error = oci_error($stmt);
    echo "Oracle query error: " . $error['message'];
    exit;
}
    // Check if execution is successful
	if ($stmt) {
		echo '<div class="message">Registration successful!</div>';

    // Așteaptă 5 secunde
		header("Refresh: 2; URL=logare.php");
	} else {
		$error = oci_error($stmt);
		echo "Error: " . $error['message'];
	}

    // Close the connection
    oci_close($conn);
}
?>

<form method="post" action=<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> name="register_form" id="register_form">
  <div class="container">
        <?php
        if (isset($_GET['message'])) {
            echo '<div class="message">' . htmlspecialchars($_GET['message']) . '</div>';
        }
        ?>
    </div>
<div class="form-element">
<h2>Registration</h2>
<input type="text" name="fullname" id="fullname" placeholder="Full name" required />
<input type="text" name="username" id="username" placeholder="Username" required />
<input type="password" name="password" id="password" placeholder="Password" required />
<input type="submit" id="submit" value="Register" />
</div>
</form>
</body>
</html>
<style>
 .container {
width: 50%;
margin: auto;
overflow: hidden;
        }
.message {
background-color: #dff0d8;
color: #3c763d;
padding: 10px;
margin-bottom: 20px;
border: 1px solid #d6e9c6;
border-radius: 4px;
text-align: center;
        }
html{
width: 100%;
height: 100%;
overflow: hidden;
}
body {
width: 100%;
height:100%;
background: #465151;
}
h2{
color: #fff;
text-shadow: 0 0 10px rgba(0,0,0,0.3);
letter-spacing: 1px;
text-align: center;
}
input {
width: 100%;
line-height: 4;
margin-bottom: 10px;
background: rgba(0,0,0,0.3);
border: none;
outline: none;
font-size: 13px;
color: #fff;
text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
border: 1px solid rgba(0,0,0,0.3);
border-radius: 4px;
box-shadow: inset 0 -5px 45px rgba(100,100,100,0.2), 0 1px 1px rgba(255,255,255,0.2);
-webkit-transition: box-shadow .5s ease;
-moz-transition: box-shadow .5s ease;
-o-transition: box-shadow .5s ease;
-ms-transition: box-shadow .5s ease;
transition: box-shadow .5s ease;
}
#submit{
background-color: #4a77d4;
padding: 25px 14px;
font-size: 15px;
line-height: normal
}
form#register_form {
width: 30%;
margin-left: 35%;
margin-top:100px;
}
::placeholder {
color:#fff;
font-size: 18px;
padding-left: 20px;
}
.message {
color: #fff;
background: #4a77d4;
padding: 10px;
text-align: center;
margin-top: 20px;
border-radius: 5px;
}
</style>
