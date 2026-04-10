<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$tokenForm = $_POST['csrf_token'] ?? '';
$tokenSession = $_SESSION['csrf_token'] ?? '';

if (empty($tokenForm) || $tokenForm !== $tokenSession) {
    $_SESSION['erreur'] = "Erreur de sécurité. Veuillez réessayer.";
    header("Location: login.php");
    exit();
}

$email = trim($_POST['email'] ?? '');
$mot_de_passe = $_POST['mot_de_passe'] ?? '';

if (empty($email) || empty($mot_de_passe)) {
    $_SESSION['erreur'] = "Veuillez remplir tous les champs.";
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM E_utilisateurs WHERE email = :email");
$stmt->execute(['email' => $email]);
$utilisateur = $stmt->fetch();

if (!$utilisateur || !password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
    $_SESSION['erreur'] = "Email ou mot de passe incorrect.";
    header("Location: login.php");
    exit();
}

$_SESSION['id'] = $utilisateur['id'];
$_SESSION['email'] = $utilisateur['email'];
$_SESSION['nom'] = $utilisateur['nom'];
$_SESSION['prenom'] = $utilisateur['prenom'];
$_SESSION['budget_mensuel'] = $utilisateur['budget_mensuel'];

header("Location: accueil.php");
exit();
