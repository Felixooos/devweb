<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: accueil.php");
    exit();
}

$tokenForm = $_POST['csrf_token'] ?? '';
$tokenSession = $_SESSION['csrf_token'] ?? '';

if (empty($tokenForm) || $tokenForm !== $tokenSession) {
    $_SESSION['error_message'] = "Erreur de sécurité. Veuillez réessayer.";
    header("Location: accueil.php");
    exit();
}

if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
    header("Location: accueil.php");
    exit();
}

$id = $_POST['id'];
$sous_categorie_id = $_POST['sous_categorie_id'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM E_depenses WHERE id = :id AND utilisateur_id = :utilisateur_id");
$stmt->execute([
    'id' => $id,
    'utilisateur_id' => $_SESSION['id']
]);
$depense = $stmt->fetch();

if (!$depense) {
    $_SESSION['error_message'] = "Vous n'êtes pas autorisé à supprimer cette dépense.";
    header("Location: accueil.php");
    exit();
}

$stmt = $pdo->prepare("DELETE FROM E_depenses WHERE id = :id AND utilisateur_id = :utilisateur_id");
$stmt->execute([
    'id' => $id,
    'utilisateur_id' => $_SESSION['id']
]);

$_SESSION['success_message'] = 'Dépense supprimée avec succès !';

if (!empty($sous_categorie_id) && ctype_digit($sous_categorie_id)) {
    header("Location: detail.php?id=" . $sous_categorie_id);
} else {
    header("Location: accueil.php");
}
exit();
