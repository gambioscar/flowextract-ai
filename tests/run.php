<?php

declare(strict_types=1);

use FlowExtract\Service\SampleExtractionService;
use FlowExtract\Support\Uuid;

require dirname(__DIR__) . '/bootstrap.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$samples = new SampleExtractionService();
foreach (['invoice', 'purchase-order', 'quote-request'] as $type) {
    $result = $samples->extract($type);
    $assert(isset($result['data']['document_type']), "{$type}: document_type mancante");
    $assert(is_array($result['data']['line_items']), "{$type}: line_items non è un array");
    $assert(is_array($result['confidence']), "{$type}: confidence non è un array");
    $assert(is_array($result['warnings']), "{$type}: warnings non è un array");
}

$uuid = Uuid::v4();
$assert((bool) preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', $uuid), 'UUID v4 non valido');

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql') ?: '';
foreach (['documents', 'audit_events', 'webhook_deliveries'] as $table) {
    $assert(str_contains($schema, "CREATE TABLE IF NOT EXISTS {$table}"), "Tabella {$table} mancante");
}

if ($failures) {
    fwrite(STDERR, "Test falliti:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "OK: campioni, UUID e schema verificati.\n";
