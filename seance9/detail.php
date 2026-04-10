<?php
session_start();
require_once 'config.php';

$titrePage = 'Détail catégorie';
require_once 'header.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: accueil.php");
    exit();
}

$categorie_id = $_GET['id'];

// Récupérer la catégorie
$stmt = $pdo->prepare("SELECT * FROM E_categories WHERE id = :id");
$stmt->execute(['id' => $categorie_id]);
$categorie = $stmt->fetch();

if (!$categorie) {
    header("Location: accueil.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupérer les sous-catégories avec le total des dépenses du mois
$stmt = $pdo->prepare("
    SELECT 
        E_sous_categories.id AS sous_categorie_id,
        E_sous_categories.nom AS sous_categorie_nom,
        E_sous_categories.budget_max,
        COALESCE(SUM(E_depenses.montant), 0) AS total_depense
    FROM E_sous_categories
    LEFT JOIN E_depenses ON E_depenses.sous_categorie_id = E_sous_categories.id
        AND E_depenses.utilisateur_id = :id
        AND MONTH(E_depenses.date_depense) = MONTH(CURRENT_DATE())
        AND YEAR(E_depenses.date_depense) = YEAR(CURRENT_DATE())
    WHERE E_sous_categories.categorie_id = :categorie_id
    GROUP BY E_sous_categories.id, E_sous_categories.nom, E_sous_categories.budget_max
    ORDER BY E_sous_categories.nom
");
$stmt->execute([
    'id' => $_SESSION['id'],
    'categorie_id' => $categorie_id
]);
$sous_categories = $stmt->fetchAll();

function getColorClassDetail($depense, $budget_max)
{
    if ($budget_max <= 0) {
        return 'vert';
    }
    $ratio = $depense / $budget_max;
    if ($ratio >= 1) {
        return 'rouge';
    }
    if ($ratio >= 0.75) {
        return 'orange';
    }
    return 'vert';
}
?>

<a href="accueil.php" class="retour-lien">← Retour à l'accueil</a>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="message-succes"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="section-header">
    <h2><?php echo htmlspecialchars($categorie['icone'] . ' ' . $categorie['nom']); ?></h2>
    <a href="ajouter_depense.php?categorie_id=<?php echo $categorie_id; ?>" class="btn btn-primary btn-small">+ Ajouter une dépense</a>
</div>

<?php if (empty($sous_categories)): ?>
    <p>Aucune sous-catégorie disponible.</p>
<?php else: ?>
    <div class="sous-categories-liste">
        <?php foreach ($sous_categories as $sc): ?>
            <div class="sous-categorie-carte">
                <div class="sous-categorie-info">
                    <div class="sous-categorie-nom"><?php echo htmlspecialchars($sc['sous_categorie_nom']); ?></div>
                    <div class="sous-categorie-budget">
                        Budget max : <?php echo number_format($sc['budget_max'], 2, ',', ' '); ?> €
                    </div>
                    <div class="budget-barre" style="margin-top: 8px;">
                        <?php 
                            $pourc = $sc['budget_max'] > 0 ? min(($sc['total_depense'] / $sc['budget_max']) * 100, 100) : 0;
                            $bgClass = getColorClassDetail($sc['total_depense'], $sc['budget_max']);
                            $bgClass = str_replace(['vert', 'orange', 'rouge'], ['bg-vert', 'bg-orange', 'bg-rouge'], $bgClass);
                        ?>
                        <div class="budget-barre-remplissage <?php echo $bgClass; ?>" 
                             style="width: <?php echo $pourc; ?>%"></div>
                    </div>
                </div>
                <div class="sous-categorie-actions">
                    <span class="sous-categorie-montant <?php echo getColorClassDetail($sc['total_depense'], $sc['budget_max']); ?>">
                        <?php echo number_format($sc['total_depense'], 2, ',', ' '); ?> €
                    </span>
                    <a href="modifier.php?id=<?php echo $sc['sous_categorie_id']; ?>" class="btn btn-secondary btn-small">Modifier</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Liste des dépenses récentes pour cette catégorie -->
    <?php
    $stmt = $pdo->prepare("
        SELECT 
            E_depenses.id,
            E_depenses.montant,
            E_depenses.date_depense,
            E_sous_categories.nom AS sous_categorie_nom
        FROM E_depenses
        INNER JOIN E_sous_categories ON E_depenses.sous_categorie_id = E_sous_categories.id
        WHERE E_sous_categories.categorie_id = :categorie_id
        AND E_depenses.utilisateur_id = :utilisateur_id
        AND MONTH(E_depenses.date_depense) = MONTH(CURRENT_DATE())
        AND YEAR(E_depenses.date_depense) = YEAR(CURRENT_DATE())
        ORDER BY E_depenses.date_depense DESC
    ");
    $stmt->execute([
        'categorie_id' => $categorie_id,
        'utilisateur_id' => $_SESSION['id']
    ]);
    $depenses = $stmt->fetchAll();
    ?>

    <?php if (!empty($depenses)): ?>
        <h3 style="margin-top: 30px; margin-bottom: 15px; color: #2c3e50;">Dépenses récentes</h3>
        <div class="depenses-liste">
            <?php foreach ($depenses as $depense): ?>
                <div class="depense-item">
                    <div>
                        <strong><?php echo htmlspecialchars($depense['sous_categorie_nom']); ?></strong>
                        <div class="depense-info"><?php echo date('d/m/Y', strtotime($depense['date_depense'])); ?></div>
                    </div>
                    <div class="depense-actions">
                        <span class="depense-montant">-<?php echo number_format($depense['montant'], 2, ',', ' '); ?> €</span>
                        <form action="supprimer_depense.php" method="POST" style="display:inline;" 
                              onsubmit="return confirm('Supprimer cette dépense ?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="id" value="<?php echo $depense['id']; ?>">
                            <input type="hidden" name="categorie_id" value="<?php echo $categorie_id; ?>">
                            <button type="submit" class="btn btn-danger btn-small">Supprimer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
