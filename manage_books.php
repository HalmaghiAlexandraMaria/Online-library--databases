<?php
session_start();
// Verificare dacă utilizatorul este autentificat, altfel redirecționare către pagina de login
if (!isset($_SESSION['username'])) {
    header("Location: logare.php");
    exit();
}

$error_message = "";

// Conexiunea la baza de date Oracle
$conn = oci_connect('hr', 'hr', 'localhost/XE');

// Verificare dacă conexiunea a fost realizată cu succes
if (!$conn) {
    echo 'Failed to connect to Oracle';
    exit;
}

// Adăugarea unei cărți noi
if (isset($_POST['add_book'])) {
    $titlu = $_POST['titlu'];
    $autor = $_POST['autor'];
    $pret = $_POST['pret'];
    $stoc = $_POST['stoc'];

    // Verificare dacă toate câmpurile sunt completate
    if (!empty($titlu) && !empty($autor) && !empty($pret) && !empty($stoc)) {
        $sql = "INSERT INTO carti (cod_carte, titlu, autor, pret, stoc) VALUES (pk_carti.nextval, :titlu, :autor, :pret, :stoc)";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':titlu', $titlu);
        oci_bind_by_name($stmt, ':autor', $autor);
        oci_bind_by_name($stmt, ':pret', $pret);
        oci_bind_by_name($stmt, ':stoc', $stoc);

        if (oci_execute($stmt)) {
            $error_message = "Book added successfully!";
        } else {
            $error_message = "Failed to add book!";
        }
    } else {
        $error_message = "Please enter all fields!";
    }
}

// Ștergerea unei cărți existente
if (isset($_POST['delete_book'])) {
    $cod_carte = $_POST['cod_carte'];

    if (!empty($cod_carte)) {
        $sql = "DELETE FROM carti WHERE cod_carte = :cod_carte";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':cod_carte', $cod_carte);

        if (oci_execute($stmt)) {
            $error_message = "Book deleted successfully!";
        } else {
            $error_message = "Failed to delete book!";
        }
    } else {
        $error_message = "Please enter book ID!";
    }
}

oci_close($conn);
?>

<html>
<head>
    <title>Manage Books</title>
    <style>
    </style>
</head>
<body>
    <h2>Manage Books</h2>
    <?php if (!empty($error_message)) { ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php } ?>
    <h3>Add Book:</h3>
    <form method="post">
        <input type="text" name="titlu" placeholder="Title" required />
        <input type="text" name="autor" placeholder="Author" required />
        <input type="number" name="pret" placeholder="Price" required />
        <input type="number" name="stoc" placeholder="Stock" required />
        <input type="submit" name="add_book" value="Add Book" />
    </form>
    <h3>Delete Book:</h3>
    <form method="post">
        <input type="number" name="cod_carte" placeholder="Book ID" required />
        <input type="submit" name="delete_book" value="Delete Book" />
    </form>
    <a href="bookstore.php">Back to Bookstore</a>
    <form method="post">
        <input type="submit" name="logout" value="Logout" class="logout-btn">
    </form>
</body>
</html>
