<?php
session_start();
?>

<?php if(!empty($_SESSION['auth'])): ?>

    <?php require_once 'headear.php'; ?>
            <h1>
                 Bonjour <?= htmlspecialchars($_SESSION['auth']['prenom']) ?> 👋
            </h1>




        <?php require_once 'footer.php'; ?>

<?php else: ?>

    <?php
        header("Location: index.php");
        exit();
    ?>

<?php endif; ?>