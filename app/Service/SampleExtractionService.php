<?php

declare(strict_types=1);

namespace FlowExtract\Service;

use InvalidArgumentException;

final class SampleExtractionService
{
    /** @return array{filename:string,mime_type:string,data:array<string,mixed>,confidence:array<string,float>,warnings:list<string>} */
    public function extract(string $sample): array
    {
        return match ($sample) {
            'invoice' => $this->invoice(),
            'purchase-order' => $this->purchaseOrder(),
            'quote-request' => $this->quoteRequest(),
            default => throw new InvalidArgumentException('Documento campione non riconosciuto.'),
        };
    }

    /** @return array{filename:string,mime_type:string,data:array<string,mixed>,confidence:array<string,float>,warnings:list<string>} */
    private function invoice(): array
    {
        return [
            'filename' => 'fattura-demo-2026-0142.pdf',
            'mime_type' => 'application/pdf',
            'data' => [
                'document_type' => 'Fattura',
                'document_number' => 'FT-2026-0142',
                'issue_date' => '2026-09-12',
                'due_date' => '2026-10-12',
                'sender_name' => 'Northwind Digital S.r.l.',
                'sender_tax_id' => 'IT09876543210',
                'sender_email' => 'billing@northwind.example',
                'recipient_name' => 'Camping Aurora S.r.l.',
                'recipient_tax_id' => 'IT01234567890',
                'currency' => 'EUR',
                'subtotal' => '2450.00',
                'tax' => '539.00',
                'total' => '2989.00',
                'summary' => 'Licenza software gestionale, configurazione iniziale e assistenza.',
                'priority' => 'Normale',
                'line_items' => [
                    ['description' => 'Licenza PMS annuale', 'quantity' => 1, 'unit_price' => 1800.00, 'total' => 1800.00],
                    ['description' => 'Configurazione e importazione dati', 'quantity' => 1, 'unit_price' => 650.00, 'total' => 650.00],
                ],
            ],
            'confidence' => ['document_number' => 0.99, 'issue_date' => 0.98, 'due_date' => 0.73, 'sender_tax_id' => 0.96, 'recipient_tax_id' => 0.94, 'total' => 0.99],
            'warnings' => ['Verificare la data di scadenza: il testo originale presenta contrasto ridotto.'],
        ];
    }

    /** @return array{filename:string,mime_type:string,data:array<string,mixed>,confidence:array<string,float>,warnings:list<string>} */
    private function purchaseOrder(): array
    {
        return [
            'filename' => 'ordine-acquisto-demo-4481.pdf',
            'mime_type' => 'application/pdf',
            'data' => [
                'document_type' => 'Ordine di acquisto',
                'document_number' => 'PO-4481',
                'issue_date' => '2026-09-14',
                'due_date' => '',
                'sender_name' => 'Riviera Hospitality S.p.A.',
                'sender_tax_id' => 'IT02233445566',
                'sender_email' => 'acquisti@riviera.example',
                'recipient_name' => 'IoT Access Systems S.r.l.',
                'recipient_tax_id' => 'IT06655443322',
                'currency' => 'EUR',
                'subtotal' => '7875.00',
                'tax' => '1732.50',
                'total' => '9607.50',
                'summary' => 'Fornitura lettori accessi, gateway e installazione per tre strutture.',
                'priority' => 'Alta',
                'line_items' => [
                    ['description' => 'Lettore accessi IP65', 'quantity' => 15, 'unit_price' => 375.00, 'total' => 5625.00],
                    ['description' => 'Gateway IoT', 'quantity' => 3, 'unit_price' => 450.00, 'total' => 1350.00],
                    ['description' => 'Installazione e collaudo', 'quantity' => 3, 'unit_price' => 300.00, 'total' => 900.00],
                ],
            ],
            'confidence' => ['document_number' => 0.99, 'issue_date' => 0.99, 'due_date' => 0.41, 'sender_tax_id' => 0.97, 'recipient_tax_id' => 0.97, 'total' => 0.99],
            'warnings' => ['Data di consegna non mappata come scadenza.', 'Confermare il valore mancante del campo “Scadenza”.'],
        ];
    }

    /** @return array{filename:string,mime_type:string,data:array<string,mixed>,confidence:array<string,float>,warnings:list<string>} */
    private function quoteRequest(): array
    {
        return [
            'filename' => 'richiesta-preventivo-demo.png',
            'mime_type' => 'image/png',
            'data' => [
                'document_type' => 'Richiesta di preventivo',
                'document_number' => 'RFQ-773',
                'issue_date' => '2026-09-15',
                'due_date' => '2026-09-22',
                'sender_name' => 'GreenStay Camping',
                'sender_tax_id' => '',
                'sender_email' => 'operations@greenstay.example',
                'recipient_name' => 'Fornitore da selezionare',
                'recipient_tax_id' => '',
                'currency' => 'EUR',
                'subtotal' => '',
                'tax' => '',
                'total' => '',
                'summary' => 'Richiesta urgente per integrazione channel manager e migrazione prenotazioni.',
                'priority' => 'Urgente',
                'line_items' => [
                    ['description' => 'Integrazione channel manager', 'quantity' => 1, 'unit_price' => null, 'total' => null],
                    ['description' => 'Migrazione prenotazioni storiche', 'quantity' => 1, 'unit_price' => null, 'total' => null],
                ],
            ],
            'confidence' => ['document_number' => 0.94, 'issue_date' => 0.96, 'due_date' => 0.92, 'sender_tax_id' => 0.12, 'recipient_tax_id' => 0.10, 'total' => 0.08],
            'warnings' => ['Partita IVA del mittente non presente.', 'Importi non presenti: documento non fiscale.'],
        ];
    }
}
