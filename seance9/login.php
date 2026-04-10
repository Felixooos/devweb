<?php
session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['email'])) {
    header("Location: accueil.php");
    exit();
}

$titrePage = 'Connexion';
require_once 'header.php';
?>

<div class="login-container">
    <div class="form-container">
        <h2>Connexion</h2>

        <?php
        if (isset($_SESSION['erreur'])) {
            echo '<div class="message-erreur">' . htmlspecialchars($_SESSION['erreur']) . '</div>';
            unset($_SESSION['erreur']);
        }

        if (isset($_SESSION['succes'])) {
            echo '<div class="message-succes">' . htmlspecialchars($_SESSION['succes']) . '</div>';
            unset($_SESSION['succes']);
        }
        ?>

        <form action="login_process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>

            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>

        <p>Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>
    </div>
</div>

<?php require_once 'footer.php'; ?>
