<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: accueil.php");
    exit();
}

$sous_categorie_id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT E_sous_categories.id, E_sous_categories.nom, E_sous_categories.budget_max,
           E_sous_categories.categorie_id, E_categories.nom AS categorie_nom, E_categories.icone
    FROM E_sous_categories
    INNER JOIN E_categories ON E_sous_categories.categorie_id = E_categories.id
    WHERE E_sous_categories.id = :id
");
$stmt->execute(['id' => $sous_categorie_id]);
$sous_categorie = $stmt->fetch();

if (!$sous_categorie) {
    header("Location: accueil.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare("
    SELECT id, montant, date_depense, date_saisie
    FROM E_depenses
    WHERE sous_categorie_id = :sc_id
    AND utilisateur_id = :uid
    AND MONTH(date_depense) = MONTH(CURRENT_DATE())
    AND YEAR(date_depense) = YEAR(CURRENT_DATE())
    ORDER BY date_depense DESC
");
$stmt->execute([
    'sc_id' => $sous_categorie_id,
    'uid' => $_SESSION['id']
]);
$depenses = $stmt->fetchAll();

$total_depense = 0;
foreach ($depenses as $d) {
    $total_depense += $d['montant'];
}
$pourcentage = $sous_categorie['budget_max'] > 0 ? round(($total_depense / $sous_categorie['budget_max']) * 100, 1) : 0;

function getColorClassDetail($depense, $budget_max)
{
    if ($budget_max <= 0) return 'vert';
    $ratio = $depense / $budget_max;
    if ($ratio >= 1) return 'rouge';
    if ($ratio >= 0.75) return 'orange';
    return 'vert';
}

$titrePage = $sous_categorie['nom'] . ' - Détail';
require_once 'header.php';
?>

<a href="accueil.php" class="retour-lien">← Retour à l'accueil</a>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="message-succes"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<div class="section-header">
    <h2><?php echo htmlspecialchars($sous_categorie['icone'] . ' ' . $sous_categorie['categorie_nom'] . ' > ' . $sous_categorie['nom']); ?></h2>
    <a href="ajouter_depense.php?sous_categorie_id=<?php echo $sous_categorie_id; ?>" class="btn btn-primary btn-small">+ Ajouter une dépense</a>
</div>

<div class="resume-sous-categorie">
    <table class="tableau-budget">
        <thead>
            <tr>
                <th>Budget max</th>
                <th>Dépensé</th>
                <th>Reste</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo number_format($sous_categorie['budget_max'], 2, ',', ' '); ?> €</td>
                <td class="<?php echo getColorClassDetail($total_depense, $sous_categorie['budget_max']); ?>"><?php echo number_format($total_depense, 2, ',', ' '); ?> €</td>
                <td class="<?php echo ($sous_categorie['budget_max'] - $total_depense) < 0 ? 'rouge' : 'vert'; ?>"><?php echo number_format($sous_categorie['budget_max'] - $total_depense, 2, ',', ' '); ?> €</td>
                <td class="<?php echo getColorClassDetail($total_depense, $sous_categorie['budget_max']); ?>"><?php echo $pourcentage; ?> %</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="section-header" style="margin-top: 30px;">
    <h3>Dépenses du mois</h3>
    <a href="modifier.php?id=<?php echo $sous_categorie_id; ?>" class="btn btn-secondary btn-small">Modifier budget max</a>
</div>

<?php if (empty($depenses)): ?>
    <p>Aucune dépense ce mois-ci.</p>
<?php else: ?>
    <table class="tableau-budget">
        <thead>
            <tr>
                <th>Date</th>
                <th>Montant</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($depenses as $depense): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($depense['date_depense'])); ?></td>
                    <td class="rouge"><?php echo number_format($depense['montant'], 2, ',', ' '); ?> €</td>
                    <td>
                        <a href="modifier_depense.php?id=<?php echo $depense['id']; ?>&sous_categorie_id=<?php echo $sous_categorie_id; ?>" class="btn btn-secondary btn-small">Modifier</a>
                        <a href="confirmer_suppression.php?id=<?php echo $depense['id']; ?>&sous_categorie_id=<?php echo $sous_categorie_id; ?>" class="btn btn-danger btn-small">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="ligne-total">
                <td><strong>Total</strong></td>
                <td class="<?php echo getColorClassDetail($total_depense, $sous_categorie['budget_max']); ?>"><strong><?php echo number_format($total_depense, 2, ',', ' '); ?> €</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

    </main>
</body>
</html>
