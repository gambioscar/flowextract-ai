<?php use FlowExtract\Support\View; ?>
<section class="error-card">
    <span class="eyebrow">FlowExtract AI</span>
    <h1><?= View::e($title ?? 'Errore') ?></h1>
    <p><?= View::e($message ?? 'Si è verificato un errore.') ?></p>
    <a class="button button-primary" href="/">Torna alla dashboard</a>
</section>
