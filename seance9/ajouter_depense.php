<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        E_sous_categories.id AS sous_categorie_id,
        E_sous_categories.nom AS sous_categorie_nom,
        E_categories.id AS categorie_id,
        E_categories.nom AS categorie_nom,
        E_categories.icone
    FROM E_sous_categories
    INNER JOIN E_categories ON E_sous_categories.categorie_id = E_categories.id
    ORDER BY E_categories.nom, E_sous_categories.nom
");
$stmt->execute();
$sous_categories = $stmt->fetchAll();

$groupes = [];
foreach ($sous_categories as $sc) {
    $cle = $sc['categorie_id'];
    $groupes[$cle]['nom'] = $sc['categorie_nom'];
    $groupes[$cle]['icone'] = $sc['icone'];
    $groupes[$cle]['sous_categories'][] = $sc;
}

$errors = [];
$montant = '';
$sous_categorie_id = $_GET['sous_categorie_id'] ?? '';
$date_depense = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montant = trim($_POST['montant'] ?? '');
    $sous_categorie_id = $_POST['sous_categorie_id'] ?? '';
    $date_depense = $_POST['date_depense'] ?? '';
    $tokenForm = $_POST['csrf_token'] ?? '';
    $tokenSession = $_SESSION['csrf_token'] ?? '';
    
    if (empty($tokenForm) || $tokenForm !== $tokenSession) {
        $errors[] = "Erreur de sécurité. Veuillez réessayer.";
    }

    if ($montant === '' || !is_numeric($montant) || $montant <= 0) {
        $errors[] = 'Le montant doit être un nombre positif.';
    }

    if (empty($sous_categorie_id) || !ctype_digit($sous_categorie_id)) {
        $errors[] = 'Veuillez sélectionner une sous-catégorie.';
    }

    if (empty($date_depense)) {
        $errors[] = 'La date de dépense est obligatoire.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO E_depenses (utilisateur_id, sous_categorie_id, montant, date_depense, date_saisie) 
            VALUES (:utilisateur_id, :sous_categorie_id, :montant, :date_depense, NOW())
        ");
        $stmt->execute([
            'utilisateur_id' => $_SESSION['id'],
            'sous_categorie_id' => $sous_categorie_id,
            'montant' => $montant,
            'date_depense' => $date_depense
        ]);

        $_SESSION['success_message'] = 'Dépense ajoutée avec succès !';
        header("Location: detail.php?id=" . $sous_categorie_id);
        exit();
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$titrePage = 'Ajouter une dépense';
require_once 'header.php';
?>

<a href="accueil.php" class="retour-lien">← Retour à l'accueil</a>

<div class="form-container">
    <h2>Ajouter une dépense</h2>

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
            <label for="sous_categorie_id">Sous-catégorie</label>
            <select id="sous_categorie_id" name="sous_categorie_id" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($groupes as $cat_id => $cat): ?>
                    <optgroup label="<?php echo htmlspecialchars($cat['icone'] . ' ' . $cat['nom']); ?>">
                        <?php foreach ($cat['sous_categories'] as $sc): ?>
                            <option value="<?php echo $sc['sous_categorie_id']; ?>"
                                <?php if ($sous_categorie_id == $sc['sous_categorie_id']): ?> selected<?php endif; ?>>
                                <?php echo htmlspecialchars($sc['sous_categorie_nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="montant">Montant (€)</label>
            <input type="number" id="montant" name="montant" step="0.01" min="0.01" 
                   value="<?php echo htmlspecialchars($montant); ?>" required>
        </div>

        <div class="form-group">
            <label for="date_depense">Date de la dépense</label>
            <input type="date" id="date_depense" name="date_depense" 
                   value="<?php echo htmlspecialchars($date_depense); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
    </form>
</div>

    </main>
</body>
</html>
