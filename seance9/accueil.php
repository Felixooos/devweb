<?php
session_start();
require_once 'config.php';

$titrePage = 'Accueil - Mon Budget';
require_once 'header.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$budget_mensuel = $_SESSION['budget_mensuel'];

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

$stmt = $pdo->prepare("
    SELECT 
        E_categories.id AS categorie_id,
        E_categories.nom AS categorie_nom,
        E_categories.icone,
        E_sous_categories.id AS sous_categorie_id,
        E_sous_categories.nom AS sous_categorie_nom,
        E_sous_categories.budget_max,
        COALESCE(SUM(E_depenses.montant), 0) AS total_depense
    FROM E_categories
    LEFT JOIN E_sous_categories ON E_sous_categories.categorie_id = E_categories.id
    LEFT JOIN E_depenses ON E_depenses.sous_categorie_id = E_sous_categories.id
        AND E_depenses.utilisateur_id = :id
        AND MONTH(E_depenses.date_depense) = MONTH(CURRENT_DATE())
        AND YEAR(E_depenses.date_depense) = YEAR(CURRENT_DATE())
    GROUP BY E_categories.id, E_categories.nom, E_categories.icone, 
             E_sous_categories.id, E_sous_categories.nom, E_sous_categories.budget_max
    ORDER BY E_categories.nom, E_sous_categories.nom
");
$stmt->execute(['id' => $_SESSION['id']]);
$resultats = $stmt->fetchAll();

$categories = [];
foreach ($resultats as $row) {
    $cat_id = $row['categorie_id'];
    if (!isset($categories[$cat_id])) {
        $categories[$cat_id] = [
            'nom' => $row['categorie_nom'],
            'icone' => $row['icone'],
            'sous_categories' => []
        ];
    }
    if ($row['sous_categorie_id']) {
        $categories[$cat_id]['sous_categories'][] = $row;
    }
}

function getColorClass($depense, $budget_max)
{
    if ($budget_max <= 0) return 'vert';
    $ratio = $depense / $budget_max;
    if ($ratio >= 1) return 'rouge';
    if ($ratio >= 0.75) return 'orange';
    return 'vert';
}

function getBgColorClass($depense, $budget_max)
{
    if ($budget_max <= 0) return 'bg-vert';
    $ratio = $depense / $budget_max;
    if ($ratio >= 1) return 'bg-rouge';
    if ($ratio >= 0.75) return 'bg-orange';
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
    <a href="ajouter_depense.php" class="btn btn-primary btn-small">+ Ajouter une dépense</a>
</div>

<?php if (empty($categories)): ?>
    <p>Aucune catégorie disponible.</p>
<?php else: ?>
    <?php foreach ($categories as $cat_id => $cat): ?>
        <?php
            $total_cat = 0;
            $budget_cat = 0;
            foreach ($cat['sous_categories'] as $sc) {
                $total_cat += $sc['total_depense'];
                $budget_cat += $sc['budget_max'];
            }
            $pourc_cat = $budget_cat > 0 ? round(($total_cat / $budget_cat) * 100, 1) : 0;
        ?>
        <div class="categorie-bloc">
            <h3><?php echo htmlspecialchars($cat['icone'] . ' ' . $cat['nom']); ?></h3>
            <?php if (!empty($cat['sous_categories'])): ?>
                <table class="tableau-budget">
                    <thead>
                        <tr>
                            <th>Sous-catégorie</th>
                            <th>Budget max</th>
                            <th>Dépensé</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cat['sous_categories'] as $sc): ?>
                            <?php $pourc_sc = $sc['budget_max'] > 0 ? round(($sc['total_depense'] / $sc['budget_max']) * 100, 1) : 0; ?>
                            <tr>
                                <td><a href="detail.php?id=<?php echo $sc['sous_categorie_id']; ?>" class="lien-sous-categorie"><?php echo htmlspecialchars($sc['sous_categorie_nom']); ?></a></td>
                                <td><?php echo number_format($sc['budget_max'], 2, ',', ' '); ?> €</td>
                                <td class="<?php echo getColorClass($sc['total_depense'], $sc['budget_max']); ?>"><?php echo number_format($sc['total_depense'], 2, ',', ' '); ?> €</td>
                                <td class="<?php echo getColorClass($sc['total_depense'], $sc['budget_max']); ?>"><?php echo $pourc_sc; ?> %</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="ligne-total">
                            <td><strong>Total</strong></td>
                            <td><strong><?php echo number_format($budget_cat, 2, ',', ' '); ?> €</strong></td>
                            <td class="<?php echo getColorClass($total_cat, $budget_cat); ?>"><strong><?php echo number_format($total_cat, 2, ',', ' '); ?> €</strong></td>
                            <td class="<?php echo getColorClass($total_cat, $budget_cat); ?>"><strong><?php echo $pourc_cat; ?> %</strong></td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p>Aucune sous-catégorie.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

    </main>
</body>
</html>
