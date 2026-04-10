<?php
session_start();
require_once 'config.php';

$titrePage = 'Modifier une sous-catégorie';
require_once 'header.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: accueil.php");
    exit();
}

$id = $_GET['id'];

// Récupérer la sous-catégorie et sa catégorie parente
$stmt = $pdo->prepare('
    SELECT E_sous_categories.id, E_sous_categories.nom, E_sous_categories.budget_max, 
           E_sous_categories.categorie_id, E_categories.nom AS categorie_nom
    FROM E_sous_categories 
    INNER JOIN E_categories ON E_sous_categories.categorie_id = E_categories.id
    WHERE E_sous_categories.id = ?
');
$stmt->execute([$id]);
$sous_categorie = $stmt->fetch();

if (!$sous_categorie) {
    header("Location: accueil.php");
    exit();
}

$errors = [];
$budget_max = $sous_categorie['budget_max'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $budget_max = trim($_POST['budget_max'] ?? '');
    $tokenForm = $_POST['csrf_token'] ?? '';
    $tokenSession = $_SESSION['csrf_token'] ?? '';
    
    if (empty($tokenForm) || $tokenForm !== $tokenSession) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    }

    if ($budget_max === '') {
        $errors[] = 'Le budget max est obligatoire.';
    } elseif (!is_numeric($budget_max) || $budget_max < 0) {
        $errors[] = 'Le budget max doit être un nombre positif.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE E_sous_categories SET budget_max = ? WHERE id = ?');
        $stmt->execute([$budget_max, $id]);

        $_SESSION['success_message'] = 'Budget modifié avec succès !';
        header("Location: detail.php?id=" . $sous_categorie['categorie_id']);
        exit();
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<a href="detail.php?id=<?php echo $sous_categorie['categorie_id']; ?>" class="retour-lien">← Retour à <?php echo htmlspecialchars($sous_categorie['categorie_nom']); ?></a>

<div class="form-container">
    <h2>Modifier : <?php echo htmlspecialchars($sous_categorie['nom']); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="message-erreur">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="form-group">
            <label for="budget_max">Budget max (€)</label>
            <input type="number" id="budget_max" name="budget_max" step="0.01" min="0" 
                   value="<?php echo htmlspecialchars($budget_max); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
