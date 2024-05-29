<?php
session_start();

// Verificați dacă utilizatorul este autentificat, altfel redirecționați către pagina de login
if (!isset($_SESSION['username'])) {
    header("Location: logare.php");
    exit();
}

// Conexiunea la baza de date Oracle
$conn = oci_connect('hr', 'hr', 'localhost/XE');

// Verificare dacă conexiunea a fost realizată cu succes
if (!$conn) {
    echo 'Failed to connect to Oracle';
    exit;
}

$error_message = "";

// Adăugarea unei cărți în coșul de cumpărături
if (isset($_POST['add_to_cart'])) {
    $cod_carte = $_POST['cod_carte'];
    $cantitate = $_POST['cantitate'];

    // Verificare dacă cantitatea este un număr pozitiv
    if (!is_numeric($cantitate) || $cantitate <= 0) {
        $error_message = "Invalid quantity!";
    } else {
        // Verificare dacă cartea există și este în stoc
        $sql = "SELECT * FROM carti WHERE cod_carte = :cod_carte AND stoc >= :cantitate";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':cod_carte', $cod_carte);
        oci_bind_by_name($stmt, ':cantitate', $cantitate);
        oci_execute($stmt);

        $book = oci_fetch_assoc($stmt);

        if (!$book) {
            $error_message = "Book not available or insufficient stock!";
        } else {
            // Verificare dacă cartea există deja în coșul de cumpărături
            $found = false;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['cod_carte'] == $cod_carte) {
                        $item['cantitate'] += $cantitate;
                        $item['total_pret'] += $cantitate * $book['PRET'];
                        $found = true;
                        break;
                    }
                }
                unset($item); // Scoatem referința la elementul curent pentru a evita bug-uri
            }

            // Dacă cartea nu există în coș, adaugă o nouă intrare
            if (!$found) {
                $_SESSION['cart'][] = array(
                    'cod_carte' => $book['COD_CARTE'],
                    'titlu' => $book['TITLU'],
                    'cantitate' => $cantitate,
                    'pret_unitar' => $book['PRET'],
                    'total_pret' => $cantitate * $book['PRET']
                );
            }

            // Actualizare stoc în baza de date
            $sql_update_stock = "UPDATE Carti SET stoc = stoc - :cantitate WHERE cod_carte = :cod_carte";
            $stmt_update_stock = oci_parse($conn, $sql_update_stock);
            oci_bind_by_name($stmt_update_stock, ':cantitate', $cantitate);
            oci_bind_by_name($stmt_update_stock, ':cod_carte', $cod_carte);
            oci_execute($stmt_update_stock);
        }
    }
}

// Scăderea unei cărți din coșul de cumpărături
if (isset($_POST['decrease_quantity'])) {
    $cod_carte = $_POST['cod_carte'];

    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => &$item) {
            if ($item['cod_carte'] == $cod_carte) {
                $item['cantitate'] -= 1;
                $item['total_pret'] -= $item['pret_unitar'];

                // Actualizare stoc în baza de date
                $sql_update_stock = "UPDATE Carti SET stoc = stoc + 1 WHERE cod_carte = :cod_carte";
                $stmt_update_stock = oci_parse($conn, $sql_update_stock);
                oci_bind_by_name($stmt_update_stock, ':cod_carte', $cod_carte);
                oci_execute($stmt_update_stock);

                // Eliminarea cărții dacă cantitatea este 0
                if ($item['cantitate'] <= 0) {
                    unset($_SESSION['cart'][$key]);
                }
                break;
            }
        }
        unset($item); // Scoatem referința la elementul curent pentru a evita bug-uri
    }
}

// Ștergerea unei cărți din coșul de cumpărături
if (isset($_POST['remove_from_cart'])) {
    $cod_carte = $_POST['cod_carte'];

    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['cod_carte'] == $cod_carte) {
                // Actualizare stoc în baza de date
                $sql_update_stock = "UPDATE Carti SET stoc = stoc + :cantitate WHERE cod_carte = :cod_carte";
                $stmt_update_stock = oci_parse($conn, $sql_update_stock);
                oci_bind_by_name($stmt_update_stock, ':cantitate', $item['cantitate']);
                oci_bind_by_name($stmt_update_stock, ':cod_carte', $cod_carte);
                oci_execute($stmt_update_stock);

                // Ștergerea cărții din coș
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
    }
}

// Calcularea prețului total al cărților din coș
$total_price = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_price += $item['total_pret'];
    }
}

// Resetează coșul de cumpărături și refă stocul
if (isset($_POST['reset_cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cod_carte = $item['cod_carte'];
        $cantitate = $item['cantitate'];

        // Actualizare stoc în baza de date
        $sql_reset_stock = "UPDATE Carti SET stoc = stoc + :cantitate WHERE cod_carte = :cod_carte";
        $stmt_reset_stock = oci_parse($conn, $sql_reset_stock);
        oci_bind_by_name($stmt_reset_stock, ':cantitate', $cantitate);
        oci_bind_by_name($stmt_reset_stock, ':cod_carte', $cod_carte);
        oci_execute($stmt_reset_stock);
    }

    // Resetează coșul de cumpărături și prețul total
    unset($_SESSION['cart']);
    $total_price = 0;
}

// Plasarea comenzii
if (isset($_POST['place_order']) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    // Deschide o nouă conexiune la baza de date
    $conn_insert = oci_connect('hr', 'hr', 'localhost/XE');

    // Verificare dacă conexiunea a fost realizată cu succes
    if (!$conn_insert) {
        echo 'Failed to connect to Oracle';
        exit;
    }

    // Crează un nou id_comanda
    $sql_new_order_id = "SELECT pk_Orders.NEXTVAL AS id_comanda FROM dual";
    $stmt_new_order_id = oci_parse($conn_insert, $sql_new_order_id);
    oci_execute($stmt_new_order_id);
    $row_new_order_id = oci_fetch_assoc($stmt_new_order_id);
    $id_comanda = $row_new_order_id['ID_COMANDA'];

    // Parcurge fiecare element din coșul de cumpărături și adaugă datele în tabela Istoric_Achizitii
    foreach ($_SESSION['cart'] as $item) {
        $cod_carte = $item['cod_carte'];
        $cantitate = $item['cantitate'];

        // Interogare pentru inserarea datelor în tabela Istoric_Achizitii
        $sql_insert_history = "INSERT INTO Istoric_Achizitii (idIstoric, cod_client, cod_carte, cantitate, data_achizitie, id_comanda) 
            VALUES (pk_Istoric_Achizitii.NEXTVAL, :cod_client, :cod_carte, :cantitate, SYSDATE, :id_comanda)";
        $stmt_insert_history = oci_parse($conn_insert, $sql_insert_history);
        oci_bind_by_name($stmt_insert_history, ':cod_client', $_SESSION['cod_client']);
        oci_bind_by_name($stmt_insert_history, ':cod_carte', $cod_carte);
        oci_bind_by_name($stmt_insert_history, ':cantitate', $cantitate);
        oci_bind_by_name($stmt_insert_history, ':id_comanda', $id_comanda);
        
        // Execută interogarea
        oci_execute($stmt_insert_history);
    }

    // Închide conexiunea la baza de date
    oci_close($conn_insert);

    // Resetează coșul de cumpărături și prețul total
    unset($_SESSION['cart']);
    $total_price = 0;

}

// Afisare istoric achizitii
$istoric_achizitii = [];
if (isset($_POST['view_history'])) {
    // Interogare pentru a selecta istoricul achizițiilor utilizatorului curent, grupat pe comenzi
    $sql_history = "SELECT h.id_comanda, h.cod_carte, h.cantitate, h.data_achizitie, c.titlu, c.pret 
                    FROM Istoric_Achizitii h
                    JOIN Carti c ON h.cod_carte = c.cod_carte
                    WHERE h.cod_client = :cod_client
                    ORDER BY h.id_comanda DESC, h.data_achizitie DESC";
    $stmt_history = oci_parse($conn, $sql_history);
    oci_bind_by_name($stmt_history, ':cod_client', $_SESSION['cod_client']);
    oci_execute($stmt_history);
    
    while ($row = oci_fetch_assoc($stmt_history)) {
        $istoric_achizitii[] = $row;
    }
}

// Interogare pentru a selecta cărțile disponibile
$sql_books = "SELECT * FROM Carti WHERE stoc > 0";
$stmt_books = oci_parse($conn, $sql_books);
oci_execute($stmt_books);

// Ștergerea contului utilizatorului
if (isset($_POST['delete_account'])) {
    $cod_client = $_SESSION['cod_client'];

    // Ștergeți înregistrările din Istoric_Achizitii
    $sql_delete_history = "DELETE FROM Istoric_Achizitii WHERE cod_client = :cod_client";
    $stmt_delete_history = oci_parse($conn, $sql_delete_history);
    oci_bind_by_name($stmt_delete_history, ':cod_client', $cod_client);
    oci_execute($stmt_delete_history);

    // Ștergeți contul utilizatorului din baza de date
    $sql_delete_account = "DELETE FROM Clienti WHERE cod_client = :cod_client";
    $stmt_delete_account = oci_parse($conn, $sql_delete_account);
    oci_bind_by_name($stmt_delete_account, ':cod_client', $cod_client);

    if (oci_execute($stmt_delete_account)) {
        // Distrugeți sesiunea și redirecționați utilizatorul către pagina de înregistrare cu un mesaj
        session_destroy();
        header("Location: inregistrare.php?message=" . urlencode("Contul a fost sters cu succes"));
        exit();
    } else {
        echo 'Failed to delete account';
    }
}

// Închide conexiunea la baza de date
oci_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
	
	
	<link rel="stylesheet" href="styles.css">
    <title>Bookstore</title>
</head>
<body>
    <div class="content">
        <h2>Welcome, <?php echo $_SESSION['fullname']; ?>!</h2>
        <h3>Available Books:</h3>
        <?php while ($book = oci_fetch_assoc($stmt_books)) { ?>
            <div class="book">
                <div class="book-title"><?php echo $book['TITLU']; ?></div>
                <div class="book-details">Author: <?php echo $book['AUTOR']; ?></div>
                <div class="book-details">Price: $<?php echo $book['PRET']; ?></div>
                <div class="book-details">Stock: <?php echo $book['STOC']; ?></div>
                <form method="post">
                    <input type="hidden" name="cod_carte" value="<?php echo $book['COD_CARTE']; ?>">
                    <input type="number" name="cantitate" class="quantity-input" placeholder="Qty" min="1" max="<?php echo $book['STOC']; ?>" required>
                    <input type="submit" name="add_to_cart" class="add-to-cart-btn" value="Add to Cart">
                </form>
            </div>
        <?php } ?>
        <div class="shopping-cart">
            <h3>Shopping Cart:</h3>
            <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) { ?>
                    <div class="cart-item">
                        <div><?php echo $item['titlu']; ?></div>
                        <div>Quantity: <?php echo $item['cantitate']; ?></div>
                        <div>Unit Price: $<?php echo number_format($item['pret_unitar'], 2); ?></div>
                        <div>Total Price: $<?php echo number_format($item['total_pret'], 2); ?></div>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="cod_carte" value="<?php echo $item['cod_carte']; ?>">
                            <input type="submit" name="decrease_quantity" value="Remove" class="decrease-quantity-btn">
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="cod_carte" value="<?php echo $item['cod_carte']; ?>">
                            <input type="submit" name="remove_from_cart" value="Remove All" class="remove-from-cart-btn">
                        </form>
                    </div>
                <?php }
            } else {
                echo "<p>Your shopping cart is empty.</p>";
            } ?>
            <h3>Total Price: $<?php echo number_format($total_price, 2); ?></h3>
            <form method="post">
                <input type="submit" name="reset_cart" value="Reset Cart" class="reset-cart-btn">
                <input type="submit" name="place_order" value="Place Order" class="place-order-btn">
                <input type="submit" name="view_history" value="View History" class="view-history-btn">
            </form>
        </div>
        <?php 
        // Afisare istoric achizitii
        if (!empty($istoric_achizitii)) {
            echo "<h3>Purchase History:</h3>";
            
            $current_order_id = null;
            foreach ($istoric_achizitii as $history) {
                if ($current_order_id !== $history['ID_COMANDA']) {
                    if ($current_order_id !== null) {
                        echo "</div>"; // Închide div-ul anterior pentru comanda
                    }
                    $current_order_id = $history['ID_COMANDA'];
                    echo "<div class='history-entry'>";
                    echo "<div><strong>Order ID:</strong> " . $current_order_id . "</div>";
                    echo "<div><strong>Order Date:</strong> " . $history['DATA_ACHIZITIE'] . "</div>";
                }
                echo "<div><strong>Book Title:</strong> " . $history['TITLU'] . "</div>";
                echo "<div><strong>Quantity:</strong> " . $history['CANTITATE'] . "</div>";
                echo "<div><strong>Price:</strong> $" . number_format($history['PRET'], 2) . "</div>";
            }
            if ($current_order_id !== null) {
                echo "</div>"; // Închide ultimul div pentru comanda
            }
        }
        ?>
        <form method="post">
            <input type="submit" name="logout" value="Logout" class="logout-btn">
            <input type="submit" name="delete_account" value="Sterge contul" class="delete-account-btn">
        </form>
    </div>
</body>
</html>