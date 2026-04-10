<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['email'])) {
    header("Location: accueil.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenForm = $_POST['csrf_token'] ?? '';
    $tokenSession = $_SESSION['csrf_token'] ?? '';

    if (empty($tokenForm) || $tokenForm !== $tokenSession) {
        $_SESSION['erreur'] = "Erreur de sécurité. Veuillez réessayer.";
        header("Location: login.php");
        exit();
    }

    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mot_de_passe)) {
        $_SESSION['erreur'] = "Veuillez remplir tous les champs.";
        header("Location: login.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM E_utilisateurs WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $utilisateur = $stmt->fetch();

    if (!$utilisateur || !password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
        $_SESSION['erreur'] = "Email ou mot de passe incorrect.";
        header("Location: login.php");
        exit();
    }

    $_SESSION['id'] = $utilisateur['id'];
    $_SESSION['email'] = $utilisateur['email'];
    $_SESSION['nom'] = $utilisateur['nom'];
    $_SESSION['prenom'] = $utilisateur['prenom'];
    $_SESSION['budget_mensuel'] = $utilisateur['budget_mensuel'];

    header("Location: accueil.php");
    exit();
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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

        <form method="POST">
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

</body>
</html>
