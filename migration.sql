-- ============================================
-- MIGRATION TAKAFUL — v2
-- Exécuter dans phpMyAdmin sur la base takaful1
-- ============================================

USE takaful1;

-- 1. Corriger la colonne nin dans membre : la rendre nullable pour éviter les doublons
--    (si elle est déjà NULL => pas de changement, sinon ALTER)
ALTER TABLE membre MODIFY COLUMN nin VARCHAR(18) NULL DEFAULT NULL;

-- 2. Ajouter document_path dans association (pièce jointe de la demande)
ALTER TABLE association ADD COLUMN IF NOT EXISTS document_path VARCHAR(500) NULL AFTER description;

-- 3. Ajouter document_path dans demande_aide (pièce jointe optionnelle)
ALTER TABLE demande_aide ADD COLUMN IF NOT EXISTS document_path VARCHAR(500) NULL AFTER type_aide;

-- 4. Ajouter association_id dans don (association cible du don)
ALTER TABLE don ADD COLUMN IF NOT EXISTS association_id VARCHAR(36) NULL AFTER siege_id;
ALTER TABLE don ADD CONSTRAINT IF NOT EXISTS fk_don_association FOREIGN KEY (association_id) REFERENCES association(id) ON DELETE SET NULL;

-- Vérification
SELECT 'Migration terminée avec succès.' as message;
