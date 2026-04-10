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
        header("Location: register.php");
        exit();
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $budget_mensuel = $_POST['budget_mensuel'] ?? '';

    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || $budget_mensuel === '') {
        $_SESSION['erreur'] = "Veuillez remplir tous les champs.";
        header("Location: register.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['erreur'] = "Adresse email invalide.";
        header("Location: register.php");
        exit();
    }

    if (!is_numeric($budget_mensuel) || $budget_mensuel < 0) {
        $_SESSION['erreur'] = "Le budget mensuel doit être un nombre positif.";
        header("Location: register.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM E_utilisateurs WHERE email = :email");
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        $_SESSION['erreur'] = "Cette adresse email est déjà utilisée.";
        header("Location: register.php");
        exit();
    }

    $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO E_utilisateurs (email, mot_de_passe, nom, prenom, budget_mensuel, date_creation) 
        VALUES (:email, :mot_de_passe, :nom, :prenom, :budget_mensuel, NOW())
    ");
    $stmt->execute([
        'email' => $email,
        'mot_de_passe' => $hash,
        'nom' => $nom,
        'prenom' => $prenom,
        'budget_mensuel' => $budget_mensuel
    ]);

    $_SESSION['succes'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
    header("Location: login.php");
    exit();
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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

        <form method="POST">
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

</body>
</html>
