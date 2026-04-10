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
$categorie_id = $_GET['categorie_id'] ?? '';

// Vérifier que la dépense appartient à l'utilisateur connecté
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

if (!empty($categorie_id) && ctype_digit($categorie_id)) {
    header("Location: detail.php?id=" . $categorie_id);
} else {
    header("Location: accueil.php");
}
exit();
