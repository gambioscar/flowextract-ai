<?php

declare(strict_types=1);

namespace FlowExtract\Repository;

use DateTimeImmutable;
use FlowExtract\Support\Uuid;
use PDO;
use RuntimeException;

final class DocumentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function purgeExpired(): void
    {
        $this->pdo->exec('DELETE FROM documents WHERE expires_at < NOW()');
    }

    /** @param array{filename:string,mime_type:string,data:array<string,mixed>,confidence:array<string,float>,warnings:list<string>} $extraction */
    public function createSample(string $sessionToken, array $extraction, int $retentionHours): string
    {
        $publicId = Uuid::v4();
        $expiresAt = (new DateTimeImmutable("+{$retentionHours} hours"))->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO documents
                (public_id, session_token, original_name, mime_type, byte_size, status, extraction_mode, extracted_data, confidence_data, warning_data, expires_at)
                VALUES (:public_id, :session_token, :original_name, :mime_type, 0, :status, :mode, :data, :confidence, :warnings, :expires_at)'
            );
            $statement->execute([
                'public_id' => $publicId,
                'session_token' => $sessionToken,
                'original_name' => $extraction['filename'],
                'mime_type' => $extraction['mime_type'],
                'status' => 'needs_review',
                'mode' => 'sample',
                'data' => $this->json($extraction['data']),
                'confidence' => $this->json($extraction['confidence']),
                'warnings' => $this->json($extraction['warnings']),
                'expires_at' => $expiresAt,
            ]);
            $documentId = (int) $this->pdo->lastInsertId();
            $this->audit($documentId, 'sample_loaded', ['mode' => 'sample']);
            $this->audit($documentId, 'extraction_completed', ['warnings' => count($extraction['warnings'])]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $publicId;
    }

    /** @return list<array<string,mixed>> */
    public function recent(string $sessionToken, int $limit = 8): array
    {
        $statement = $this->pdo->prepare(
            'SELECT public_id, original_name, mime_type, status, extraction_mode, extracted_data, warning_data, created_at, updated_at
             FROM documents WHERE session_token = :session_token ORDER BY created_at DESC LIMIT :result_limit'
        );
        $statement->bindValue('session_token', $sessionToken);
        $statement->bindValue('result_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(fn (array $row): array => $this->decode($row), $statement->fetchAll());
    }

    /** @return array<string,mixed>|null */
    public function find(string $publicId, string $sessionToken): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM documents WHERE public_id = :public_id AND session_token = :session_token LIMIT 1'
        );
        $statement->execute(['public_id' => $publicId, 'session_token' => $sessionToken]);
        $document = $statement->fetch();
        if (!$document) {
            return null;
        }

        $document = $this->decode($document);
        $document['audit'] = $this->auditFor((int) $document['id']);
        return $document;
    }

    /** @param array<string,mixed> $fields */
    public function updateFields(string $publicId, string $sessionToken, array $fields): void
    {
        $document = $this->find($publicId, $sessionToken);
        if (!$document) {
            throw new RuntimeException('Documento non trovato.');
        }

        $statement = $this->pdo->prepare(
            'UPDATE documents SET extracted_data = :data, status = :status WHERE id = :id'
        );
        $statement->execute(['data' => $this->json($fields), 'status' => 'needs_review', 'id' => $document['id']]);
        $this->audit((int) $document['id'], 'fields_reviewed', ['field_count' => count($fields)]);
    }

    public function decide(string $publicId, string $sessionToken, string $decision, ?string $reason = null): void
    {
        $document = $this->find($publicId, $sessionToken);
        if (!$document) {
            throw new RuntimeException('Documento non trovato.');
        }

        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Decisione non valida.');
        }

        $timeColumn = $decision === 'approved' ? 'approved_at' : 'rejected_at';
        $statement = $this->pdo->prepare(
            "UPDATE documents SET status = :status, {$timeColumn} = NOW(), rejection_reason = :reason WHERE id = :id"
        );
        $statement->execute(['status' => $decision, 'reason' => $decision === 'rejected' ? $reason : null, 'id' => $document['id']]);
        $this->audit((int) $document['id'], 'document_' . $decision, $reason ? ['reason' => $reason] : []);
    }

    public function markExported(int $documentId, string $format): void
    {
        $this->pdo->prepare("UPDATE documents SET status = IF(status = 'approved', 'exported', status) WHERE id = :id")
            ->execute(['id' => $documentId]);
        $this->audit($documentId, 'document_exported', ['format' => $format]);
    }

    /** @return array<string,int> */
    public function stats(string $sessionToken): array
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) total,
                    SUM(status = 'needs_review') needs_review,
                    SUM(status IN ('approved','exported')) approved,
                    SUM(status = 'rejected') rejected
             FROM documents WHERE session_token = :session_token"
        );
        $statement->execute(['session_token' => $sessionToken]);
        $row = $statement->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'needs_review' => (int) ($row['needs_review'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
        ];
    }

    /** @param array<string,mixed> $data */
    private function audit(int $documentId, string $eventType, array $data = []): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO audit_events (document_id, event_type, event_data) VALUES (:document_id, :event_type, :event_data)'
        );
        $statement->execute(['document_id' => $documentId, 'event_type' => $eventType, 'event_data' => $this->json($data)]);
    }

    /** @return list<array<string,mixed>> */
    private function auditFor(int $documentId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT event_type, event_data, created_at FROM audit_events WHERE document_id = :document_id ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(['document_id' => $documentId]);
        return array_map(function (array $row): array {
            $row['event_data'] = json_decode((string) ($row['event_data'] ?? '{}'), true) ?: [];
            return $row;
        }, $statement->fetchAll());
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function decode(array $row): array
    {
        foreach (['extracted_data', 'confidence_data', 'warning_data'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = json_decode((string) ($row[$field] ?? ($field === 'warning_data' ? '[]' : '{}')), true) ?: [];
            }
        }
        return $row;
    }

    /** @param mixed $value */
    private function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
