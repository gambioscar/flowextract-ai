<?php

use FlowExtract\Support\Csrf;
use FlowExtract\Support\View;

$data = $document['extracted_data'];
$confidence = $document['confidence_data'];
$warnings = $document['warning_data'];
$editable = in_array($document['status'], ['needs_review'], true);

$labels = [
    'document_type' => 'Tipo documento', 'document_number' => 'Numero documento',
    'issue_date' => 'Data emissione', 'due_date' => 'Scadenza',
    'sender_name' => 'Mittente', 'sender_tax_id' => 'P. IVA mittente',
    'sender_email' => 'Email mittente', 'recipient_name' => 'Destinatario',
    'recipient_tax_id' => 'P. IVA destinatario', 'currency' => 'Valuta',
    'subtotal' => 'Imponibile', 'tax' => 'Imposta', 'total' => 'Totale',
    'priority' => 'Priorità', 'summary' => 'Sintesi',
];

$eventLabels = [
    'sample_loaded' => 'Documento campione caricato',
    'extraction_completed' => 'Estrazione completata',
    'fields_reviewed' => 'Campi revisionati',
    'document_approved' => 'Documento approvato',
    'document_rejected' => 'Documento rifiutato',
    'document_exported' => 'Documento esportato',
];
?>
<section class="review-header">
    <a href="/" class="back-link">← Dashboard</a>
    <div class="review-title">
        <div><span class="eyebrow">Revisione assistita</span><h1><?= View::e($document['original_name']) ?></h1></div>
        <span class="status status-<?= View::e($document['status']) ?>"><?= View::e(str_replace('_', ' ', $document['status'])) ?></span>
    </div>
</section>

<section class="review-layout">
    <aside class="source-panel">
        <div class="panel-heading"><span>Documento sorgente</span><em><?= View::e(strtoupper($document['extraction_mode'])) ?></em></div>
        <div class="source-preview">
            <div class="paper">
                <span class="paper-tag"><?= str_contains($document['mime_type'], 'pdf') ? 'PDF' : 'IMG' ?></span>
                <h3><?= View::e($data['sender_name'] ?? '') ?></h3>
                <p><?= View::e($data['document_type'] ?? '') ?> · <?= View::e($data['document_number'] ?? '') ?></p>
                <div class="paper-rule"></div>
                <?php foreach (($data['line_items'] ?? []) as $item): ?>
                    <div class="paper-row"><span><?= View::e($item['description'] ?? '') ?></span><b><?= isset($item['total']) && $item['total'] !== null ? '€ ' . number_format((float) $item['total'], 2, ',', '.') : '—' ?></b></div>
                <?php endforeach; ?>
                <div class="paper-rule"></div>
                <div class="paper-total"><span>Totale</span><strong><?= ($data['total'] ?? '') !== '' ? '€ ' . number_format((float) $data['total'], 2, ',', '.') : 'Da definire' ?></strong></div>
                <small>Anteprima sintetica del documento dimostrativo</small>
            </div>
        </div>
        <div class="privacy-note"><strong>Dati dimostrativi</strong><span>Questo contenuto è interamente sintetico e non identifica aziende reali.</span></div>
    </aside>

    <div class="extraction-panel">
        <div class="panel-heading"><span>Dati estratti</span><em><?= count($warnings) ?> <?= count($warnings) === 1 ? 'avviso' : 'avvisi' ?></em></div>

        <?php if ($warnings): ?>
            <div class="warning-box"><strong>Controllo consigliato</strong><ul><?php foreach ($warnings as $warning): ?><li><?= View::e($warning) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" action="/documents/<?= View::e($document['public_id']) ?>/review" id="review-form">
            <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
            <div class="field-grid">
                <?php foreach ($labels as $field => $label):
                    $score = $confidence[$field] ?? null;
                    $low = $score !== null && $score < .8;
                    $type = str_contains($field, 'date') ? 'date' : (str_contains($field, 'email') ? 'email' : 'text');
                    $wide = in_array($field, ['sender_name','recipient_name','summary'], true);
                ?>
                    <label class="field <?= $wide ? 'field-wide' : '' ?> <?= $low ? 'field-warning' : '' ?>">
                        <span><?= View::e($label) ?><?php if ($score !== null): ?><em><?= (int) round($score * 100) ?>%</em><?php endif; ?></span>
                        <?php if ($field === 'summary'): ?>
                            <textarea name="<?= View::e($field) ?>" <?= !$editable ? 'readonly' : '' ?>><?= View::e($data[$field] ?? '') ?></textarea>
                        <?php else: ?>
                            <input type="<?= $type ?>" name="<?= View::e($field) ?>" value="<?= View::e((string) ($data[$field] ?? '')) ?>" <?= !$editable ? 'readonly' : '' ?>>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($editable): ?><button class="button button-secondary" type="submit">Salva revisione</button><?php endif; ?>
        </form>

        <div class="line-items">
            <h2>Righe rilevate</h2>
            <div class="table-wrap"><table><thead><tr><th>Descrizione</th><th>Qtà</th><th>Prezzo</th><th>Totale</th></tr></thead><tbody>
            <?php foreach (($data['line_items'] ?? []) as $item): ?>
                <tr><td><?= View::e($item['description'] ?? '') ?></td><td><?= View::e((string) ($item['quantity'] ?? '')) ?></td><td><?= isset($item['unit_price']) && $item['unit_price'] !== null ? '€ ' . number_format((float) $item['unit_price'], 2, ',', '.') : '—' ?></td><td><?= isset($item['total']) && $item['total'] !== null ? '€ ' . number_format((float) $item['total'], 2, ',', '.') : '—' ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        </div>

        <?php if ($editable): ?>
            <form method="post" action="/documents/<?= View::e($document['public_id']) ?>/decision" class="decision-bar">
                <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                <input type="text" name="reason" placeholder="Motivazione richiesta solo in caso di rifiuto">
                <div><button class="button button-reject" type="submit" name="decision" value="rejected">Rifiuta</button><button class="button button-primary" type="submit" name="decision" value="approved">Approva documento</button></div>
            </form>
        <?php elseif (in_array($document['status'], ['approved','exported'], true)): ?>
            <div class="export-bar"><div><strong>Documento validato</strong><span>I dati sono pronti per un sistema esterno.</span></div><div><a class="button button-ghost" href="/documents/<?= View::e($document['public_id']) ?>/export.csv">CSV</a><a class="button button-primary" href="/documents/<?= View::e($document['public_id']) ?>/export.json">JSON</a></div></div>
        <?php elseif ($document['status'] === 'rejected'): ?>
            <div class="rejected-box"><strong>Documento rifiutato</strong><span><?= View::e($document['rejection_reason'] ?? '') ?></span></div>
        <?php endif; ?>
    </div>
</section>

<section class="audit section-block">
    <div class="section-heading compact"><div><span class="eyebrow">Audit trail</span><h2>Storico delle operazioni</h2></div></div>
    <ol><?php foreach ($document['audit'] as $event): ?><li><i></i><div><strong><?= View::e($eventLabels[$event['event_type']] ?? $event['event_type']) ?></strong><span><?= View::e($event['created_at']) ?></span></div></li><?php endforeach; ?></ol>
</section>
