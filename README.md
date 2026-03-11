# Le Gourmet

Le Gourmet est une application de reservation de restaurant en PHP + MySQL, concue pour un hebergement local avec XAMPP, WAMP ou Laragon. Elle comprend l'inscription et la connexion des clients, la reservation de tables, un espace personnel pour suivre les reservations, un tableau de bord administrateur, l'impression des reservations, ainsi que l'envoi d'emails de confirmation et de rappel J-1 via SMTP.

## Fonctionnalites

- Creation de compte client et connexion
- Formulaire de reservation pour les utilisateurs connectes
- Suivi des reservations dans `mes_reservations.php`
- Annulation de reservation par le client
- Tableau de bord administrateur pour gerer toutes les reservations
- Modification des reservations par l'admin
- Attribution de table et gestion du statut de reservation
- Envoi automatique d'un email de confirmation
- Envoi de rappels pour les reservations du lendemain
- Impression de reservation depuis `imprimer_reservation.php`

## Technologies utilisees

- PHP
- MySQL / MariaDB
- PDO
- HTML / CSS / JavaScript
- SMTP avec un systeme d'envoi d'email personnalise en PHP

## Pages principales

- `index.php` : formulaire de reservation pour les utilisateurs connectes
- `login.php` : connexion utilisateur
- `inscription.php` : inscription client
- `mes_reservations.php` : historique des reservations du client
- `admin.php` : tableau de bord administrateur
- `editer_reservation.php` : modification d'une reservation par l'admin
- `envoyer_notifications_reservations.php` : envoi des notifications et rappels en ligne de commande

## Prerequis

- PHP 8.x recommande
- MySQL ou MariaDB
- Un serveur local Apache comme XAMPP
- Une base de donnees contenant les tables principales de l'application

## Information importante sur la base de donnees

Ce depot ne contient pas un script SQL complet pour une installation depuis zero.

Le fichier [`migration.sql`](./migration.sql) ajoute ou corrige seulement certaines colonnes de la table `reservations` :

- `utilisateur_id`
- `email`
- `confirmation_email_sent_at`
- `reminder_email_sent_at`

L'application suppose que ces tables existent deja :

- `utilisateurs`
- `reservations`
- `creneaux`
- `tables_restaurant`

Si ces tables n'existent pas encore dans votre base, vous devrez les creer ou les importer avant d'utiliser le projet.

## Installation en local

1. Copier le projet dans le dossier de votre serveur web, par exemple :
   - XAMPP : `C:\xampp\htdocs\LeGourmet-final2`
2. Creer une base de donnees nommee `reservation_restaurant`
3. Importer votre schema SQL de base contenant les tables necessaires
4. Executer [`migration.sql`](./migration.sql) dans phpMyAdmin ou MySQL CLI
5. Verifier ou modifier les identifiants MySQL dans [`config/database.php`](./config/database.php)
6. Configurer les parametres SMTP dans [`config/mail.php`](./config/mail.php)
7. Ouvrir le projet dans le navigateur :
   - `http://localhost/LeGourmet-final2/`

## Configuration de la base de donnees

La configuration actuelle de la base se trouve dans [`config/database.php`](./config/database.php) :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'reservation_restaurant');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Modifiez ces valeurs si votre configuration MySQL est differente.

## Configuration des emails

Les parametres SMTP se trouvent dans [`config/mail.php`](./config/mail.php).

Avant d'utiliser le projet en production, remplacez les identifiants SMTP actuels par vos propres informations securisees. Champs principaux a verifier :

- `host`
- `port`
- `encryption`
- `username`
- `password`
- `from_email`
- `from_name`
- `reply_to`

## Comment utiliser le projet

### Cote client

1. Ouvrir `login.php` ou `inscription.php`
2. Creer un compte client
3. Se connecter
4. Aller sur `index.php`
5. Remplir le formulaire avec :
   - nom complet
   - telephone
   - email
   - date de reservation
   - creneau horaire
   - nombre de personnes
   - commentaire optionnel
6. Valider la reservation
7. Consulter les reservations dans `mes_reservations.php`
8. Imprimer ou annuler une reservation depuis l'espace personnel

### Cote administrateur

1. Se connecter avec un compte admin
2. Ouvrir `admin.php`
3. Consulter toutes les reservations
4. Modifier une reservation via `editer_reservation.php`
5. Mettre a jour :
   - le statut
   - la table attribuee
   - le message ou commentaire visible par le client
6. Envoyer des emails de confirmation ou des rappels J-1 depuis le tableau de bord

## Statuts de reservation

Le systeme utilise les statuts suivants :

- `en_attente`
- `confirmee`
- `annulee`

Lorsqu'un administrateur passe une reservation a `confirmee`, l'application peut envoyer automatiquement un email de confirmation si celui-ci n'a pas encore ete envoye.

## Emails de rappel

Les rappels du lendemain peuvent etre envoyes de deux manieres :

- Depuis le tableau de bord administrateur avec le bouton `Rappels J-1`
- Depuis la ligne de commande :

```bash
php envoyer_notifications_reservations.php
```

La commande CLI recherche les reservations confirmees prevues pour le lendemain et envoie les rappels aux clients qui n'en ont pas encore recu.

## Comptes de test

L'interface mentionne ces exemples de comptes :

- Admin : `admin@legourmet.fr`
- Client : `client@test.com`

Les mots de passe reels dependent des donnees presentes dans votre base.

Si le mot de passe administrateur ne fonctionne plus mais que le compte existe deja, vous pouvez utiliser [`reparation_admin.php`](./reparation_admin.php) pour reinitialiser le mot de passe de `admin@legourmet.fr` a :

```text
admin123
```

Ce fichier doit etre utilise uniquement en developpement local.

## Structure du projet

```text
LeGourmet-final2/
|-- admin.php
|-- index.php
|-- login.php
|-- inscription.php
|-- mes_reservations.php
|-- editer_reservation.php
|-- envoyer_notifications_reservations.php
|-- imprimer_reservation.php
|-- traitement_reservation.php
|-- migration.sql
|-- config/
|   |-- database.php
|   `-- mail.php
|-- includes/
|   |-- header.php
|   |-- footer.php
|   `-- reservation_notifier.php
|-- css/
|   `-- style.css
`-- js/
    `-- main.js
```

## Remarques

- GitHub Pages ne peut pas executer ce projet, car GitHub Pages ne prend pas en charge PHP ni MySQL.
- Le projet doit etre deploye sur un hebergement compatible PHP avec acces a une base de donnees.
- Certaines verifications et corrections de schema sont aussi effectuees automatiquement en PHP lors de l'utilisation des notifications.

## Depannage

- Si vous etes redirige vers la page de connexion, verifiez que les sessions PHP fonctionnent correctement.
- Si les reservations ne s'enregistrent pas, verifiez que les tables et colonnes requises existent bien dans la base.
- Si les emails ne partent pas, verifiez les identifiants SMTP, le port, le type de chiffrement et l'autorisation SMTP de votre fournisseur.
- Si le compte admin ne fonctionne pas, verifiez la table `utilisateurs` ou utilisez `reparation_admin.php` en environnement local.
