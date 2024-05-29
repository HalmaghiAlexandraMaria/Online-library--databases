<?php
session_start(); // Start the session

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connect to Oracle database
    $conn = oci_connect('hr', 'hr', 'localhost/XE');

    // Check if connection is successful
    if (!$conn) {
        echo 'Failed to connect to Oracle';
        exit; // Exit the script if connection fails
    }

    // Prepare the SQL statement to select user from the database
    $sql = "SELECT * FROM clienti WHERE username = :username AND password = :password";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':username', $_POST['username']);
    oci_bind_by_name($stmt, ':password', $_POST['password']);

    // Execute the statement
    oci_execute($stmt);

    // Fetch the row
    $row = oci_fetch_assoc($stmt);

    // Check if a row is fetched
    if ($row) {
        // User exists, set session variables and redirect to another page
        $_SESSION['username'] = $row['USERNAME'];
        $_SESSION['fullname'] = $row['FULLNAME'];
        $_SESSION['cod_client'] = $row['COD_CLIENT']; // Set the cod_client in session

        header("Location: bookstore.php"); // Redirect to bookstore page
        exit();
    } else {
        // User doesn't exist, display error message
        $error_message = "Invalid username or password";
    }

    // Close the connection
    oci_close($conn);
}
?>

<html>
<head>
<title>Login Page</title>
</head>
<body>
<?php
// Display error message if set
if (isset($error_message)) {
    echo '<div class="error-message">' . $error_message . '</div>';
}
?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" name="login_form" id="login_form">
<div class="form-element">
<h2>Welcome </h2>
<input type="text" name="username" id="username" placeholder="Username" required />
<input type="password" name="password" id="password" placeholder="Password" required />
<input type="submit" id="submit" value="Let me in" />
</div>
</form>
</body>
<style>
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
form#login_form {
width: 30%;
margin-left: 35%;
margin-top:100px;
}
::placeholder {
color:#fff;
font-size: 18px;
padding-left: 20px;
}
.error-message {
color: #fff;
background: #ff6347; /* Red color */
padding: 10px;
text-align: center;
margin-top: 20px;
border-radius: 5px;
}
</style>
</html>
