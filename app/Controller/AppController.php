<?php

declare(strict_types=1);

namespace FlowExtract\Controller;

use FlowExtract\Repository\DocumentRepository;
use FlowExtract\Service\SampleExtractionService;
use FlowExtract\Support\Csrf;
use FlowExtract\Support\Env;
use FlowExtract\Support\View;
use RuntimeException;

final class AppController
{
    public function __construct(
        private readonly DocumentRepository $documents,
        private readonly SampleExtractionService $samples,
        private readonly string $sessionToken,
    ) {
    }

    public function dashboard(): void
    {
        View::render('dashboard', [
            'title' => 'Dashboard',
            'documents' => $this->documents->recent($this->sessionToken),
            'stats' => $this->documents->stats($this->sessionToken),
            'demoMode' => Env::bool('APP_DEMO_MODE', true),
        ]);
    }

    public function createSample(): never
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        $sample = (string) ($_POST['sample'] ?? '');
        $extraction = $this->samples->extract($sample);
        $publicId = $this->documents->createSample(
            $this->sessionToken,
            $extraction,
            max(1, Env::int('DEMO_RETENTION_HOURS', 24))
        );
        $this->flash('success', 'Documento campione elaborato. Controlla i campi segnalati prima di approvarlo.');
        $this->redirect('/documents/' . $publicId);
    }

    public function show(string $publicId): void
    {
        $document = $this->requireDocument($publicId);
        View::render('documents.show', ['title' => 'Revisione documento', 'document' => $document]);
    }

    public function review(string $publicId): never
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        $document = $this->requireDocument($publicId);
        $existing = $document['extracted_data'];

        $fields = [];
        foreach (['document_type','document_number','issue_date','due_date','sender_name','sender_tax_id','sender_email','recipient_name','recipient_tax_id','currency','subtotal','tax','total','summary','priority'] as $field) {
            $fields[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $fields['line_items'] = $existing['line_items'] ?? [];

        $this->documents->updateFields($publicId, $this->sessionToken, $fields);
        $this->flash('success', 'Revisione salvata e registrata nello storico.');
        $this->redirect('/documents/' . $publicId);
    }

    public function decide(string $publicId): never
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        $decision = (string) ($_POST['decision'] ?? '');
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($decision === 'rejected' && $reason === '') {
            $this->flash('error', 'Inserisci una motivazione prima di rifiutare il documento.');
            $this->redirect('/documents/' . $publicId);
        }
        $this->documents->decide($publicId, $this->sessionToken, $decision, $reason ?: null);
        $this->flash('success', $decision === 'approved' ? 'Documento approvato.' : 'Documento rifiutato.');
        $this->redirect('/documents/' . $publicId);
    }

    public function export(string $publicId, string $format): never
    {
        $document = $this->requireDocument($publicId);
        if (!in_array($document['status'], ['approved', 'exported'], true)) {
            throw new RuntimeException('Approva il documento prima di esportarlo.');
        }
        $payload = $document['extracted_data'];
        $basename = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo((string) $document['original_name'], PATHINFO_FILENAME));

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $basename . '.json"');
            $this->documents->markExported((int) $document['id'], 'json');
            echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $basename . '.csv"');
            $this->documents->markExported((int) $document['id'], 'csv');
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['campo', 'valore'], ';', '"', '');
            foreach ($payload as $key => $value) {
                $csvValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
                fputcsv($stream, [$this->csvSafe((string) $key), $this->csvSafe((string) $csvValue)], ';', '"', '');
            }
            fclose($stream);
            exit;
        }

        throw new RuntimeException('Formato di esportazione non supportato.');
    }

    /** @return array<string,mixed> */
    private function requireDocument(string $publicId): array
    {
        $document = $this->documents->find($publicId, $this->sessionToken);
        if (!$document) {
            http_response_code(404);
            throw new RuntimeException('Documento non trovato o sessione demo scaduta.');
        }
        return $document;
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }

    private function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@]/', ltrim($value)) ? "'" . $value : $value;
    }
}
