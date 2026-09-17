<?php

declare(strict_types=1);

use FlowExtract\Controller\AppController;
use FlowExtract\Repository\DocumentRepository;
use FlowExtract\Service\SampleExtractionService;
use FlowExtract\Support\Database;
use FlowExtract\Support\View;

require dirname(__DIR__) . '/bootstrap.php';

header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    $documents = new DocumentRepository((new Database())->connection());
    $documents->purgeExpired();

    $controller = new AppController(
        $documents,
        new SampleExtractionService(),
        (string) $_SESSION['_demo_token'],
    );

    if ($method === 'GET' && $path === '/') {
        $controller->dashboard();
        return;
    }
    if ($method === 'POST' && $path === '/documents/sample') {
        $controller->createSample();
    }
    if (preg_match('#^/documents/([a-f0-9-]{36})$#', $path, $match)) {
        if ($method === 'GET') {
            $controller->show($match[1]);
            return;
        }
    }
    if ($method === 'POST' && preg_match('#^/documents/([a-f0-9-]{36})/review$#', $path, $match)) {
        $controller->review($match[1]);
    }
    if ($method === 'POST' && preg_match('#^/documents/([a-f0-9-]{36})/decision$#', $path, $match)) {
        $controller->decide($match[1]);
    }
    if ($method === 'GET' && preg_match('#^/documents/([a-f0-9-]{36})/export\.(json|csv)$#', $path, $match)) {
        $controller->export($match[1], $match[2]);
    }

    http_response_code(404);
    View::render('error', ['title' => 'Pagina non trovata', 'message' => 'La risorsa richiesta non esiste.']);
} catch (Throwable $exception) {
    $status = http_response_code();
    if (!is_int($status) || $status < 400) {
        http_response_code(500);
        $status = 500;
    }
    if ($status >= 500) {
        error_log($exception->__toString());
    }
    View::render('error', [
        'title' => 'Operazione non completata',
        'message' => $exception->getMessage(),
    ]);
}
