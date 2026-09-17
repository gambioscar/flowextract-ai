<?php

use FlowExtract\Support\Csrf;
use FlowExtract\Support\View;
?>
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow"><i></i> Intelligent document workflow</span>
        <h1>Dal documento al dato.<br><span>Con controllo umano.</span></h1>
        <p>FlowExtract AI trasforma documenti aziendali in informazioni strutturate, segnala i campi incerti e mantiene traccia di ogni revisione.</p>
        <div class="hero-actions">
            <a href="#samples" class="button button-primary">Prova il flusso demo <span>→</span></a>
            <a href="#workflow" class="button button-ghost">Scopri come funziona</a>
        </div>
        <ul class="trust-list" aria-label="Caratteristiche">
            <li>Output strutturato</li>
            <li>Human in the loop</li>
            <li>Audit completo</li>
        </ul>
    </div>
    <div class="hero-visual" aria-label="Anteprima del processo di estrazione">
        <div class="scan-card">
            <div class="scan-top"><span>FT-2026-0142.pdf</span><b>PDF</b></div>
            <div class="doc-line w90"></div><div class="doc-line w62"></div>
            <div class="doc-grid"><span></span><span></span><span></span><span></span></div>
            <div class="doc-line w76"></div><div class="doc-line w48"></div>
            <div class="scan-beam"></div>
        </div>
        <div class="result-card result-top"><span>Totale documento</span><strong>€ 2.989,00</strong><em>99% confidence</em></div>
        <div class="result-card result-bottom"><span>Scadenza</span><strong>12 ott 2026</strong><em class="warning">73% · verifica</em></div>
        <div class="flow-chip">AI extraction</div>
    </div>
</section>

<section class="stats" aria-label="Statistiche della sessione">
    <article><span>Documenti elaborati</span><strong><?= (int) $stats['total'] ?></strong><i class="tone-blue">sessione attuale</i></article>
    <article><span>Da verificare</span><strong><?= (int) $stats['needs_review'] ?></strong><i class="tone-orange">richiedono attenzione</i></article>
    <article><span>Approvati</span><strong><?= (int) $stats['approved'] ?></strong><i class="tone-green">pronti all’export</i></article>
    <article><span>Rifiutati</span><strong><?= (int) $stats['rejected'] ?></strong><i>nella sessione</i></article>
</section>

<section id="samples" class="section-block">
    <div class="section-heading">
        <div><span class="eyebrow">Demo sicura</span><h2>Scegli un documento campione</h2></div>
        <p>I documenti sono sintetici. Il flusso riproduce estrazione, confidence score, verifica, approvazione ed export senza utilizzare dati reali.</p>
    </div>
    <div class="sample-grid">
        <?php foreach ([
            ['invoice','Fattura fornitore','Totali, scadenza, anagrafiche e righe','PDF','01'],
            ['purchase-order','Ordine di acquisto','Articoli, quantità, priorità e importi','PDF','02'],
            ['quote-request','Richiesta preventivo','Intento, scadenza e dati di contatto','IMG','03'],
        ] as [$slug,$name,$description,$format,$number]): ?>
            <form method="post" action="/documents/sample" class="sample-card">
                <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                <input type="hidden" name="sample" value="<?= View::e($slug) ?>">
                <div class="sample-number"><?= View::e($number) ?></div>
                <span class="file-icon"><?= View::e($format) ?></span>
                <h3><?= View::e($name) ?></h3>
                <p><?= View::e($description) ?></p>
                <button type="submit">Elabora campione <span>→</span></button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($documents): ?>
<section class="section-block recent">
    <div class="section-heading compact"><div><span class="eyebrow">Sessione</span><h2>Documenti recenti</h2></div></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Documento</th><th>Tipo</th><th>Stato</th><th>Totale</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($documents as $document): $data = $document['extracted_data']; ?>
            <tr>
                <td><strong><?= View::e($document['original_name']) ?></strong><small><?= View::e($document['created_at']) ?></small></td>
                <td><?= View::e($data['document_type'] ?? '') ?></td>
                <td><span class="status status-<?= View::e($document['status']) ?>"><?= View::e(str_replace('_', ' ', $document['status'])) ?></span></td>
                <td><?= View::e($data['currency'] ?? 'EUR') ?> <?= View::e($data['total'] ?? '—') ?></td>
                <td><a class="row-link" href="/documents/<?= View::e($document['public_id']) ?>">Apri →</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php endif; ?>

<section id="workflow" class="section-block workflow">
    <div class="section-heading"><div><span class="eyebrow">Workflow verificabile</span><h2>AI dove accelera. Persone dove conta.</h2></div><p>L’automazione propone dati strutturati; l’utente conserva la decisione finale prima dell’integrazione con altri sistemi.</p></div>
    <ol>
        <li><b>01</b><span><strong>Acquisizione</strong>PDF o immagine entra nel flusso protetto.</span></li>
        <li><b>02</b><span><strong>Estrazione</strong>I campi vengono normalizzati in uno schema JSON.</span></li>
        <li><b>03</b><span><strong>Validazione</strong>Score e avvisi indirizzano la revisione umana.</span></li>
        <li><b>04</b><span><strong>Integrazione</strong>Dati approvati esportati verso ERP, CRM o webhook.</span></li>
    </ol>
</section>
