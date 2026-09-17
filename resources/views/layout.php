<?php

use FlowExtract\Support\Csrf;
use FlowExtract\Support\View;

$flash = $_SESSION['_flash'] ?? null;
unset($_SESSION['_flash']);
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="FlowExtract AI: progetto dimostrativo per estrazione strutturata, revisione e integrazione di documenti aziendali.">
    <title><?= View::e($title ?? 'FlowExtract AI') ?> · FlowExtract AI</title>
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<div class="ambient ambient-one"></div>
<div class="ambient ambient-two"></div>
<header class="site-header">
    <a class="brand" href="/" aria-label="FlowExtract AI — Dashboard">
        <span class="brand-mark" aria-hidden="true"><span></span><span></span><span></span></span>
        <span><strong>FlowExtract</strong><em>AI</em></span>
    </a>
    <nav aria-label="Navigazione principale">
        <a href="/">Dashboard</a>
        <a href="/#workflow">Workflow</a>
        <span class="demo-pill"><i></i> Demo pubblica</span>
    </nav>
</header>

<main>
    <?php if (is_array($flash)): ?>
        <div class="flash flash-<?= View::e($flash['type'] ?? 'success') ?>" role="status">
            <?= View::e($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>

<footer>
    <p><strong>FlowExtract AI</strong> è un progetto dimostrativo sviluppato da Oscar Gambi.</p>
    <p>Nessun documento campione rappresenta clienti o transazioni reali.</p>
</footer>
</body>
</html>
