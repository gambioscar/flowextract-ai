CREATE TABLE IF NOT EXISTS documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id CHAR(36) NOT NULL UNIQUE,
    session_token CHAR(64) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NULL,
    mime_type VARCHAR(100) NOT NULL,
    byte_size INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('uploaded','processing','needs_review','approved','rejected','exported','failed') NOT NULL DEFAULT 'uploaded',
    extraction_mode ENUM('sample','simulation','openai') NOT NULL DEFAULT 'sample',
    extracted_data JSON NULL,
    confidence_data JSON NULL,
    warning_data JSON NULL,
    rejection_reason VARCHAR(500) NULL,
    approved_at DATETIME NULL,
    rejected_at DATETIME NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_documents_session_created (session_token, created_at),
    INDEX idx_documents_expiry (expires_at),
    INDEX idx_documents_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(60) NOT NULL,
    event_data JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_document_created (document_id, created_at),
    CONSTRAINT fk_audit_document
        FOREIGN KEY (document_id) REFERENCES documents(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS webhook_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    target_url VARCHAR(500) NOT NULL,
    request_payload JSON NOT NULL,
    response_code SMALLINT UNSIGNED NULL,
    response_body TEXT NULL,
    status ENUM('pending','delivered','failed','simulated') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_webhook_document_created (document_id, created_at),
    CONSTRAINT fk_webhook_document
        FOREIGN KEY (document_id) REFERENCES documents(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
