# 🏠🐾 DariPets (داري بيتس)

**DariPets** (« dar » = la maison en darija) — marketplace de **garde d'animaux** pour le marché marocain.
Marque, code et design 100% originaux ; adaptations locales : villes marocaines, dirham MAD,
FR/AR + RTL, paiement CMI.

> ⚖️ Projet original. Marque propre « DariPets ». Aucune ressource (nom, textes, logo, code) d'un tiers n'est copiée.

## Stack

| Couche | Techno |
|--------|--------|
| Backend / API | Symfony 7.4 + API Platform 4 |
| Auth | LexikJWTAuthenticationBundle (JWT) |
| Base de données | MySQL (WAMP) via Doctrine ORM |
| Frontend | Vue 3 + Vuetify 3 (Vite, Pinia, Vue Router, vue-i18n) |
| Paiement | CMI (Centre Monétique Interbancaire, MAD / devise 504) |
| i18n | Français + Arabe (RTL automatique) |

## Modèle de domaine

`User` (proprio/gardien/admin) · `PetSitterProfile` · `Animal` · `Service` ·
`Booking` · `Conversation`/`Message` · `Review` · `Region`/`City` (Maroc).

## Démarrage

### Backend (depuis `E:\animaute-maroc`)
```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php bin/console lexik:jwt:generate-keypair --skip-if-exists
symfony server:start -d --no-tls --port=8004
```

### Frontend (depuis `frontend/`)
```bash
npm install
npm run dev        # http://localhost:5173 (proxy /api -> :8004)
```

## Comptes de démo (mot de passe : `password`)

| Rôle | Email |
|------|-------|
| Admin | admin@daripets.ma |
| Propriétaire | proprio@daripets.ma |
| Gardiens | karim@ / salma@ / youssef@ / fatima@ / amine@ / nadia@ dari.ma |

## Flux principaux

- **Recherche géolocalisée** de gardiens (ville, service, type d'animal, prix, vérifié) → `GET /api/pet_sitter_profiles`
- **Réservation** : `POST /api/bookings` (le total MAD est calculé serveur = tarif/jour × nuits)
- **Cycle** : `pending` → (gardien) `accepted` → (proprio) **paiement CMI** → `paid` → `completed`
- **Paiement** : `POST /api/bookings/{id}/pay` renvoie l'URL passerelle + params signés (HASH ver3) ;
  CMI rappelle `POST /api/payment/cmi/callback` qui vérifie le HASH et passe la résa en `paid`.
- **Avis** : `POST /api/reviews` recalcule automatiquement la note moyenne du gardien.

## Configuration CMI

Renseigner dans `.env.local` les identifiants marchands fournis par CMI :
```
CMI_CLIENT_ID=...
CMI_STORE_KEY=...
CMI_GATEWAY_URL=https://testpayment.cmi.co.ma/fim/est3Dgate   # sandbox
```

## Déploiement (Render + Supabase + Vercel)

| Brique | Service | Détail |
|--------|---------|--------|
| Base de données | **Supabase** (PostgreSQL) | Récupérer l'URI Postgres (pooler, port 6543) |
| Backend API | **Render** (Docker, `Dockerfile` à la racine) | Le conteneur génère les clés JWT et applique le schéma au démarrage |
| Frontend | **Vercel** (root directory `frontend/`) | Build Vite, rewrites SPA via `vercel.json` |

### Variables d'environnement à définir sur Render
```
APP_ENV=prod
APP_SECRET=<chaîne aléatoire 32+ caractères>
DATABASE_URL=postgresql://postgres.xxxx:MOT_DE_PASSE@aws-0-eu-west-1.pooler.supabase.com:6543/postgres?serverVersion=15&charset=utf8
JWT_PASSPHRASE=<chaîne aléatoire>
CORS_ALLOW_ORIGIN=^https://.*\.vercel\.app$
COMMISSION_RATE=0.15
CMI_* (voir .env quand les identifiants marchands seront disponibles)
```

### Variables d'environnement à définir sur Vercel
```
VITE_API_URL=https://<ton-service>.onrender.com/api
```

### Données de démo (optionnel, après le 1er déploiement)
Depuis le Shell Render : `php bin/console doctrine:fixtures:load --no-interaction`

## Reste à faire (post-MVP)

- Upload réel des photos (avatars, animaux) — actuellement champs URL
- Notifications e-mail (Mailer) sur changement de statut de réservation
- Géocodage / recherche par rayon (lat/lng déjà stockés sur les villes)
- Traduction des entités (services/villes) servie selon le locale courant
- Tests fonctionnels (PHPUnit) sur les parcours réservation/paiement
