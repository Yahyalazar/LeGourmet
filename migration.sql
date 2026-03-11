-- ============================================================
-- LE GOURMET - Script de migration / correction
-- A executer une seule fois dans phpMyAdmin ou MySQL CLI
-- ============================================================

-- 1. Ajouter la colonne utilisateur_id si elle n'existe pas
ALTER TABLE `reservations`
  ADD COLUMN IF NOT EXISTS `utilisateur_id` INT(11) DEFAULT NULL;

-- 2. Ajouter la colonne email si elle n'existe pas
ALTER TABLE `reservations`
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(150) NOT NULL DEFAULT '';

-- 3. Ajouter les colonnes de suivi d'emails si elles n'existent pas
ALTER TABLE `reservations`
  ADD COLUMN IF NOT EXISTS `confirmation_email_sent_at` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `reminder_email_sent_at` DATETIME DEFAULT NULL;

-- 4. Ajouter la cle etrangere vers utilisateurs (optionnel)
-- ALTER TABLE `reservations`
--   ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL;

-- 5. Verifier la structure finale
DESCRIBE `reservations`;
