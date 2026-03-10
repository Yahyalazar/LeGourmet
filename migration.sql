-- ============================================================
-- LE GOURMET — Script de migration / correction
-- À exécuter UNE SEULE FOIS dans phpMyAdmin ou MySQL CLI
-- ============================================================

-- 1. Ajouter la colonne utilisateur_id si elle n'existe pas
ALTER TABLE `reservations`
  ADD COLUMN IF NOT EXISTS `utilisateur_id` INT(11) DEFAULT NULL;

-- 2. Ajouter la colonne email si elle n'existe pas  
ALTER TABLE `reservations`
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(150) NOT NULL DEFAULT '';

-- 3. Ajouter la clé étrangère vers utilisateurs (optionnel, ignorer si déjà présente)
-- ALTER TABLE `reservations`
--   ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL;

-- 4. Vérifier la structure finale
DESCRIBE `reservations`;
