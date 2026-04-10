<?php
session_start();
require_once 'config.php';

$titrePage = 'Accueil - Mon Budget';
require_once 'header.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Récupérer le budget mensuel de l'utilisateur
$budget_mensuel = $_SESSION['budget_mensuel'];

// Récupérer le total des dépenses du mois en cours
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(montant), 0) AS total_depenses
    FROM E_depenses
    WHERE utilisateur_id = :id
    AND MONTH(date_depense) = MONTH(CURRENT_DATE())
    AND YEAR(date_depense) = YEAR(CURRENT_DATE())
");
$stmt->execute(['id' => $_SESSION['id']]);
$total_depenses = $stmt->fetch()['total_depenses'];

$reste = $budget_mensuel - $total_depenses;
$pourcentage = $budget_mensuel > 0 ? min(($total_depenses / $budget_mensuel) * 100, 100) : 0;

// Récupérer les catégories avec le total des dépenses et le budget max par catégorie
$stmt = $pdo->prepare("
    SELECT 
        E_categories.id AS categorie_id,
        E_categories.nom AS categorie_nom,
        E_categories.icone,
        COALESCE(SUM(E_depenses.montant), 0) AS total_depense_categorie,
        COALESCE(SUM(E_sous_categories.budget_max), 0) AS budget_max_categorie
    FROM E_categories
    LEFT JOIN E_sous_categories ON E_sous_categories.categorie_id = E_categories.id
    LEFT JOIN E_depenses ON E_depenses.sous_categorie_id = E_sous_categories.id
        AND E_depenses.utilisateur_id = :id
        AND MONTH(E_depenses.date_depense) = MONTH(CURRENT_DATE())
        AND YEAR(E_depenses.date_depense) = YEAR(CURRENT_DATE())
    GROUP BY E_categories.id, E_categories.nom, E_categories.icone
    ORDER BY E_categories.nom
");
$stmt->execute(['id' => $_SESSION['id']]);
$categories = $stmt->fetchAll();

function getColorClass($depense, $budget_max)
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

function getBgColorClass($depense, $budget_max)
{
    if ($budget_max <= 0) {
        return 'bg-vert';
    }
    $ratio = $depense / $budget_max;
    if ($ratio >= 1) {
        return 'bg-rouge';
    }
    if ($ratio >= 0.75) {
        return 'bg-orange';
    }
    return 'bg-vert';
}
?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="message-succes"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="message-erreur"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="budget-global">
    <h2>Budget du mois</h2>
    <div class="budget-montant"><?php echo number_format($budget_mensuel, 2, ',', ' '); ?> €</div>
    <div class="budget-restant <?php echo $reste < 0 ? 'rouge' : 'vert'; ?>">
        Reste : <?php echo number_format($reste, 2, ',', ' '); ?> € 
        (Dépensé : <?php echo number_format($total_depenses, 2, ',', ' '); ?> €)
    </div>
    <div class="budget-barre">
        <div class="budget-barre-remplissage <?php echo getBgColorClass($total_depenses, $budget_mensuel); ?>" 
             style="width: <?php echo $pourcentage; ?>%"></div>
    </div>
</div>

<div class="section-header">
    <h2>Budget par catégorie</h2>
</div>

<?php if (empty($categories)): ?>
    <p>Aucune catégorie disponible.</p>
<?php else: ?>
    <div class="categories-liste">
        <?php foreach ($categories as $categorie): ?>
            <?php 
                $pourc_cat = $categorie['budget_max_categorie'] > 0 
                    ? min(($categorie['total_depense_categorie'] / $categorie['budget_max_categorie']) * 100, 100) 
                    : 0;
            ?>
            <a href="detail.php?id=<?php echo $categorie['categorie_id']; ?>" class="categorie-carte">
                <div class="categorie-header">
                    <span class="categorie-nom"><?php echo htmlspecialchars($categorie['categorie_nom']); ?></span>
                    <span class="categorie-icone"><?php echo htmlspecialchars($categorie['icone']); ?></span>
                </div>
                <div class="categorie-montants">
                    <span>Dépensé : <?php echo number_format($categorie['total_depense_categorie'], 2, ',', ' '); ?> €</span>
                    <span class="<?php echo getColorClass($categorie['total_depense_categorie'], $categorie['budget_max_categorie']); ?>">
                        Budget : <?php echo number_format($categorie['budget_max_categorie'], 2, ',', ' '); ?> €
                    </span>
                </div>
                <div class="budget-barre">
                    <div class="budget-barre-remplissage <?php echo getBgColorClass($categorie['total_depense_categorie'], $categorie['budget_max_categorie']); ?>" 
                         style="width: <?php echo $pourc_cat; ?>%"></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
