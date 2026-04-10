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

$id = $_GET['id'];
$sous_categorie_id = $_GET['sous_categorie_id'] ?? '';

$stmt = $pdo->prepare("
    SELECT E_depenses.id, E_depenses.montant, E_depenses.date_depense, E_depenses.date_saisie,
           E_sous_categories.nom AS sous_categorie_nom, E_sous_categories.id AS sc_id,
           E_categories.nom AS categorie_nom, E_categories.icone
    FROM E_depenses
    INNER JOIN E_sous_categories ON E_depenses.sous_categorie_id = E_sous_categories.id
    INNER JOIN E_categories ON E_sous_categories.categorie_id = E_categories.id
    WHERE E_depenses.id = :id AND E_depenses.utilisateur_id = :uid
");
$stmt->execute([
    'id' => $id,
    'uid' => $_SESSION['id']
]);
$depense = $stmt->fetch();

if (!$depense) {
    $_SESSION['error_message'] = "Dépense introuvable.";
    header("Location: accueil.php");
    exit();
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$titrePage = 'Confirmer la suppression';
require_once 'header.php';
?>

<a href="detail.php?id=<?php echo htmlspecialchars($sous_categorie_id); ?>" class="retour-lien">← Retour</a>

<div class="form-container">
    <h2>Confirmer la suppression</h2>

    <p style="text-align: center; margin-bottom: 20px;">Êtes-vous sûr de vouloir supprimer cette dépense ?</p>

    <table class="tableau-budget">
        <tbody>
            <tr>
                <td><strong>Catégorie</strong></td>
                <td><?php echo htmlspecialchars($depense['icone'] . ' ' . $depense['categorie_nom']); ?></td>
            </tr>
            <tr>
                <td><strong>Sous-catégorie</strong></td>
                <td><?php echo htmlspecialchars($depense['sous_categorie_nom']); ?></td>
            </tr>
            <tr>
                <td><strong>Montant</strong></td>
                <td class="rouge"><?php echo number_format($depense['montant'], 2, ',', ' '); ?> €</td>
            </tr>
            <tr>
                <td><strong>Date dépense</strong></td>
                <td><?php echo date('d/m/Y', strtotime($depense['date_depense'])); ?></td>
            </tr>
            <tr>
                <td><strong>Date saisie</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($depense['date_saisie'])); ?></td>
            </tr>
        </tbody>
    </table>

    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <a href="detail.php?id=<?php echo htmlspecialchars($sous_categorie_id); ?>" class="btn btn-secondary" style="flex: 1;">Annuler</a>
        <form action="supprimer_depense.php" method="POST" style="flex: 1;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo $depense['id']; ?>">
            <input type="hidden" name="sous_categorie_id" value="<?php echo htmlspecialchars($sous_categorie_id); ?>">
            <button type="submit" class="btn btn-danger" style="width: 100%;">Supprimer</button>
        </form>
    </div>
</div>

    </main>
</body>
</html>
