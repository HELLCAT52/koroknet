ALTER TABLE reviews
    ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS moderation_note VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS moderated_at TIMESTAMP NULL DEFAULT NULL;
