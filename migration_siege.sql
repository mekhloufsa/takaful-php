USE takaful1;

ALTER TABLE membre_association ADD COLUMN IF NOT EXISTS siege_id VARCHAR(36) NULL AFTER association_id;
ALTER TABLE membre_association MODIFY COLUMN statut ENUM('en_attente','actif','inactif') DEFAULT 'en_attente';
