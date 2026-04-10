<?php
session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['email'])) {
    header("Location: accueil.php");
    exit();
}

$titrePage = 'Inscription';
require_once 'header.php';
?>

<div class="login-container">
    <div class="form-container">
        <h2>Inscription</h2>

        <?php
        if (isset($_SESSION['erreur'])) {
            echo '<div class="message-erreur">' . htmlspecialchars($_SESSION['erreur']) . '</div>';
            unset($_SESSION['erreur']);
        }
        ?>

        <form action="register_process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required>
            </div>

            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>

            <div class="form-group">
                <label for="budget_mensuel">Budget mensuel (€)</label>
                <input type="number" id="budget_mensuel" name="budget_mensuel" step="0.01" min="0" required>
            </div>

            <button type="submit" class="btn btn-primary">S'inscrire</button>
        </form>

        <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
    </div>
</div>

<?php require_once 'footer.php'; ?>
