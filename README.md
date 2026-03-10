# Le Gourmet — Restaurant Gastronomique

## ⚠️ IMPORTANT — Pourquoi ça ne marche pas sur GitHub Pages

GitHub Pages **ne supporte PAS PHP**. Il n'affiche que des fichiers statiques (HTML, CSS, JS).

Ce projet nécessite un **serveur PHP + MySQL**. Pour le faire tourner :

### En local :
1. Installer **XAMPP** / **Wamp** / **Laragon**
2. Copier le dossier dans `htdocs/` (XAMPP) ou `www/` (Wamp)
3. Importer `migration.sql` dans phpMyAdmin
4. Accéder via `http://localhost/LeGourmet-v3/`

### En ligne (hébergement PHP) :
- OVH Perso
- InfinityFree (gratuit)
- 000webhost (gratuit)
- Heroku + ClearDB (MySQL)
- Railway.app

### Comptes de test :
- **Admin** : `admin@legourmet.fr` / `admin123` *(à vérifier selon votre BDD)*
- **Client** : `client@test.com` / *(mot de passe de votre BDD)*

---

## Structure
```
LeGourmet-v3/
├── css/style.css        ← Design system complet
├── js/main.js           ← Tout le JavaScript (15 modules)
├── includes/
│   ├── header.php
│   └── footer.php
├── config/database.php
├── index.php            ← Formulaire réservation
├── login.php
├── inscription.php
├── mes_reservations.php
├── admin.php
├── editer_reservation.php
└── migration.sql        ← À exécuter en BDD
```
