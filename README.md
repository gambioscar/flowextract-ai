# FlowExtract AI

Progetto dimostrativo full-stack per l’estrazione assistita di dati da documenti aziendali, con controllo umano, audit trail ed esportazione strutturata.

> **Portfolio demo:** nomi, importi e documenti inclusi nel progetto sono sintetici. Non rappresentano clienti o transazioni reali.

## Demo online

[Apri FlowExtract AI](https://flowextract.psdt.pro)

La demo pubblica utilizza esclusivamente documenti campione sintetici. L'estrazione AI reale e il caricamento di file esterni non sono attivi nella milestone corrente.

## Perché esiste

Molti processi amministrativi iniziano da PDF, scansioni o immagini e terminano con dati inseriti manualmente in un gestionale. FlowExtract AI mostra un flusso più affidabile:

1. acquisizione del documento;
2. estrazione in uno schema JSON prevedibile;
3. confidence score e avvisi sui campi incerti;
4. revisione e decisione umana;
5. registrazione immutabile delle operazioni;
6. esportazione JSON/CSV o integrazione webhook.

## Stato attuale

La prima milestone implementa il workflow completo con tre documenti campione sintetici:

- fattura fornitore;
- ordine di acquisto;
- richiesta di preventivo.

L’integrazione AI reale e il caricamento pubblico protetto sono milestone successive. Finché `APP_DEMO_MODE=true`, l’applicazione dichiara sempre in interfaccia la natura simulata dell’estrazione.

## Stack

- PHP 8.2+ con tipizzazione stretta e autoload PSR-4;
- MySQL 8 e PDO con prepared statement;
- HTML semantico, CSS responsive e JavaScript minimale;
- Docker Compose per l’ambiente locale;
- Responses API con file input e output JSON strutturato (milestone AI).

## Avvio locale con Docker

```bash
docker compose up --build
```

Aprire `http://localhost:8080`. Lo schema viene inizializzato automaticamente nel database MySQL.

## Avvio su hosting PHP/MySQL

1. Puntare il document root alla directory `public/`.
2. Eseguire `database/schema.sql` su MySQL 8.
3. Copiare `.env.example` in `.env` e configurare database, URL e una `APP_KEY` casuale.
4. Rendere scrivibile dall’utente PHP soltanto la directory `storage/`.
5. Non committare mai `.env` o chiavi API.

## Sicurezza già prevista

- session cookie `HttpOnly` e `SameSite=Lax`;
- token CSRF su ogni operazione mutativa;
- query PDO preparate;
- isolamento dei documenti tramite token di sessione;
- UUID pubblici al posto degli ID incrementali;
- Content Security Policy e principali security header;
- prevenzione CSV injection;
- cancellazione automatica dei dati demo alla scadenza configurata;
- credenziali e chiavi escluse dal repository.

Prima dell’apertura dell’upload pubblico saranno inoltre applicati controllo MIME tramite `fileinfo`, limite dimensione, filename casuali, archiviazione fuori dalla web root, rate limit e cancellazione automatica.

## Test

Con PHP 8.2+ disponibile:

```bash
composer test
```

## Roadmap

- [x] modello dati, session isolation e audit trail;
- [x] dashboard responsive e documenti campione;
- [x] revisione, approvazione/rifiuto ed export JSON/CSV;
- [ ] upload PDF/JPEG/PNG protetto;
- [ ] estrazione con Responses API e schema JSON;
- [ ] simulazione webhook verificabile;
- [x] pubblicazione online e collaudo manuale end-to-end;

## Autore

Oscar Gambi — Software Developer Freelance<br>
[psdt.pro](https://psdt.pro) · `oscar@psdt.pro`
