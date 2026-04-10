<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titrePage ?? 'Budget Manager'); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <a href="accueil.php" class="logo">💰 Mon Budget</a>
            <?php if (isset($_SESSION['email'])): ?>
                <div class="nav-links">
                    <span>Bonjour, <?php echo htmlspecialchars($_SESSION['prenom']); ?></span>
                    <a href="accueil.php">Tableau de bord</a>
                    <a href="logout.php">Déconnexion</a>
                </div>
            <?php endif; ?>
        </nav>
    </header>
    <main>

