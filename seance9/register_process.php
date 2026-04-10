<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

$tokenForm = $_POST['csrf_token'] ?? '';
$tokenSession = $_SESSION['csrf_token'] ?? '';

if (empty($tokenForm) || $tokenForm !== $tokenSession) {
    $_SESSION['erreur'] = "Erreur de sécurité. Veuillez réessayer.";
    header("Location: register.php");
    exit();
}

$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$email = trim($_POST['email'] ?? '');
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$budget_mensuel = $_POST['budget_mensuel'] ?? '';

if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || $budget_mensuel === '') {
    $_SESSION['erreur'] = "Veuillez remplir tous les champs.";
    header("Location: register.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['erreur'] = "Adresse email invalide.";
    header("Location: register.php");
    exit();
}

if (!is_numeric($budget_mensuel) || $budget_mensuel < 0) {
    $_SESSION['erreur'] = "Le budget mensuel doit être un nombre positif.";
    header("Location: register.php");
    exit();
}

// Vérifier si l'email existe déjà
$stmt = $pdo->prepare("SELECT id FROM E_utilisateurs WHERE email = :email");
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    $_SESSION['erreur'] = "Cette adresse email est déjà utilisée.";
    header("Location: register.php");
    exit();
}

$hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO E_utilisateurs (email, mot_de_passe, nom, prenom, budget_mensuel, date_creation) 
    VALUES (:email, :mot_de_passe, :nom, :prenom, :budget_mensuel, NOW())
");
$stmt->execute([
    'email' => $email,
    'mot_de_passe' => $hash,
    'nom' => $nom,
    'prenom' => $prenom,
    'budget_mensuel' => $budget_mensuel
]);

$_SESSION['succes'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
header("Location: login.php");
exit();
