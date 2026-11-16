# SIGPE – Système Intégré de Gestion du Patrimoine de l'État

Plateforme de gestion du patrimoine de l'État en **architecture micro-services** (Laravel 12, PostgreSQL 16, Docker).
Projet individuel – Brief « Développement d'une solution numérique (Gestion du patrimoine) » – 10 au 15/11/2026.

## Sommaire
1. [Présentation](#1-présentation) · 2. [Fonctionnalités](#2-fonctionnalités) · 3. [Architecture](#3-architecture) · 4. [Conception UML](#4-conception-uml) · 5. [Base de données et normalisation](#5-base-de-données-et-normalisation) · 6. [Installation](#6-installation) · 7. [Utilisation](#7-utilisation) · 8. [API](#8-api) · 9. [Sécurité](#9-sécurité) · 10. [Déploiement gratuit](#10-déploiement-gratuit) · 11. [Limites et évolutions](#11-limites-et-évolutions) · 12. [Auteur](#12-auteur)

## 1. Présentation
**Problème** : le patrimoine de l'État (bâtiments, véhicules, mobilier, matériel informatique, équipements) est suivi sur papier ou tableurs dispersés : biens non recensés, affectations inconnues, pas d'historique, pas de valeur comptable à jour.

**Solution** : un inventaire centralisé, des mouvements tracés (transfert, maintenance, cession, réforme), un calcul automatique de la valeur nette comptable et un tableau de bord de pilotage, le tout protégé par des rôles.

Les livrables académiques détaillés (maquettes Figma, fiche technique, proposition financière et guide utilisateur) sont à fournir séparément; ils ne sont pas inclus dans ce dépôt de code.

## 2. Fonctionnalités
- Authentification et rôles : `admin_central`, `admin`, `gestionnaire`, `auditeur`
- Espaces séparés pour l'administration centrale, les ministères, les régions et les communes
- CRUD des biens, recherche texte, filtres catégorie/type/statut/espace, pagination
- Transferts d'affectation, maintenance, cession, réforme, avec historique par bien
- Valeur nette comptable (amortissement linéaire, calculée à la volée)
- Registre privé de suivi des actifs financiers et dettes (sans comptabilité générale)
- Tableau de bord : totaux, répartition par catégorie, biens à réformer
- Export CSV de l'inventaire
- Accueil public sans connexion : fiches publiées de patrimoines naturels et vivants avec des champs limités
- Passerelle API (`/gateway/{service}/...`)

## 3. Architecture

| Service | Rôle | Port local | Base |
|---|---|---|---|
| `web` | Interface Blade/Tailwind + passerelle API | 8080 | aucune (session fichier) |
| `auth` | Utilisateurs, rôles, jetons | 8001 | `auth_db` |
| `patrimoine` | Biens, catégories, statistiques, export | 8002 | `patrimoine_db` |
| `mouvement` | Mouvements et historique | 8003 | `mouvement_db` |
| `postgres` | Une instance, 3 bases | 5432 | – |

Principes : une base par service ; aucune jointure inter-services ; le service Mouvements modifie un bien **uniquement via l'API du service Patrimoine** ; chaque service valide le jeton en appelant `GET /api/me` du service Auth. Les UUID d'organisation dans Patrimoine et Mouvements sont des références logiques entre bases.

```mermaid
flowchart LR
  U[Navigateur] --> W[web : interface + passerelle]
  W --> A[auth]
  W --> P[patrimoine]
  W --> M[mouvement]
  P -- valide le jeton --> A
  M -- valide le jeton --> A
  M -- lit / met à jour un bien --> P
  A --- DA[(auth_db)]
  P --- DP[(patrimoine_db)]
  M --- DM[(mouvement_db)]
```

## 4. Conception UML

### Cas d'utilisation
```mermaid
flowchart LR
  Aud((Auditeur)) --> C1[Consulter les biens]
  Aud --> C2[Voir l'historique]
  Aud --> C3[Exporter CSV]
  Ges((Gestionnaire)) --> C1
  Ges --> C4[Créer / modifier un bien]
  Ges --> C5[Enregistrer un mouvement]
  Adm((Administrateur)) --> C1
  Adm --> C4
  Adm --> C5
  Adm --> C6[Supprimer un bien]
  Adm --> C7[Gérer les utilisateurs]
  All((Tous)) --> C0[S'authentifier]
```

### Diagramme de classes
```mermaid
classDiagram
  class Organisation { +id +nom +code +type +parent_id }
  class User { +id +name +email +password +role +api_token +organisation_id }
  class Categorie { +id +nom }
  class Bien {
    +id +code +designation +categorie_id
    +localisation +service_affecte
    +etat +statut
    +date_acquisition +valeur_acquisition +duree_amortissement
    +type_patrimoine +est_public +organisation_id
    +valeurNette() float?
  }
  class Mouvement {
    +id +bien_id +organisation_id +type
    +depuis_service +vers_service
    +description +cout +date_mouvement +user_name
  }
  class EngagementFinancier { +id +organisation_id +code +nature +designation +contrepartie +montant_initial +solde_courant }
  Organisation "1" --> "0..*" User : rattache
  Organisation "1" --> "0..*" Bien : possède
  Organisation "1" --> "0..*" EngagementFinancier : possède
  Categorie "1" --> "0..*" Bien : regroupe
  Bien "1" .. "0..*" Mouvement : référence logique
```

### Séquence : transfert d'un bien
```mermaid
sequenceDiagram
  actor G as Gestionnaire
  participant W as web
  participant M as mouvement
  participant A as auth
  participant P as patrimoine
  G->>W: Formulaire de transfert
  W->>M: POST /api/mouvements (Bearer)
  M->>A: GET /api/me
  A-->>M: utilisateur + rôle
  M->>P: GET /api/biens/{id}
  P-->>M: bien (statut actif)
  M->>P: PUT /api/biens/{id} {service_affecte}
  P->>A: GET /api/me
  A-->>P: utilisateur + rôle
  P-->>M: bien mis à jour
  M-->>W: 201 mouvement enregistré
  W-->>G: Confirmation
```

### Déploiement
```mermaid
flowchart TB
  subgraph Docker["Réseau Docker"]
    W[web :8000] --> A[auth :8000]
    W --> P[patrimoine :8000]
    W --> M[mouvement :8000]
    A --> PG[(postgres)]
    P --> PG
    M --> PG
  end
  Internet --> W
```

## 5. Base de données et normalisation
```
auth_db       organisations(id, nom, code UNIQUE, type, parent_id)
              users(id, name, email UNIQUE, password, role, api_token UNIQUE, organisation_id, timestamps)
patrimoine_db categories(id, nom UNIQUE)
              biens(id, organisation_id, code, designation, type_patrimoine, est_public, categorie_id FK,
                    localisation, service_affecte, etat, statut, date_acquisition, valeur_acquisition,
                    duree_amortissement, zone_publique, description_publique, timestamps)
              engagements_financiers(id, organisation_id, code, nature, designation, contrepartie,
                    montant_initial, solde_courant, date_effet, date_echeance, statut, notes, timestamps)
mouvement_db  mouvements(id, bien_id, organisation_id, type, depuis_service, vers_service, description,
                         cout, date_mouvement, user_name, timestamps)
```
- **1FN** : valeurs atomiques, pas de groupes répétitifs.
- **2FN** : clés simples (`id`), pas de dépendance partielle.
- **3FN** : la catégorie est isolée ; la valeur nette est calculée, jamais stockée.
- **Choix assumés** : le code d'un bien est unique par organisation ; `bien_id` des mouvements et `organisation_id` entre bases sont des références logiques ; `user_name` est une copie volontaire pour garder un historique d'audit lisible.

## 6. Installation
Prérequis : Docker et Docker Compose.
```bash
git clone <url-du-depot> && cd gestion-patrimoine-etat
docker compose up --build
```
Le premier démarrage est long (Laravel est téléchargé pour chaque service). Puis ouvrir http://localhost:8080.

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur central | admin@etat.gov | Admin@123 |
| Gestionnaire | gestionnaire@etat.gov | Gest@123 |
| Auditeur | auditeur@etat.gov | Audit@123 |

Changer ces mots de passe et les secrets PostgreSQL de `docker-compose.yml` avant toute mise en ligne.

## 7. Utilisation
L'accueil public (`/`) permet de rechercher et consulter les fiches explicitement publiées des patrimoines naturels et vivants. Les autres biens et toutes les données financières restent privés. Les biens déjà recensés sont matériels et privés par défaut; le catalogue public reste donc vide jusqu'à publication d'une fiche naturelle ou vivante.

L'administration centrale gère ses biens et ceux des ministères rattachés, crée les espaces ministériels, régionaux et communaux et peut y provisionner des comptes. Elle ne consulte pas les données des régions et communes autonomes. Un administrateur local gère les utilisateurs et les données de son seul espace. Les biens et mouvements déjà présents sont rattachés à l'administration centrale par les migrations.

## 8. API
Seules les routes `GET /api/public/biens`, `GET /api/public/biens/{id}` et `GET /api/public/categories` du service Patrimoine sont publiques. Elles ne retournent que les champs autorisés des patrimoines naturels ou vivants explicitement publiés. Les autres routes API exigent `Authorization: Bearer <token>` et appliquent la portée de l'organisation.

| Service | Méthode et route | Rôles |
|---|---|---|
| auth | `POST /api/login`, `POST /api/logout`, `GET /api/me` | tous |
| auth | `GET/POST /api/users` | admin central (provisionnement) ou admin local (son espace) |
| auth | `GET /api/organisations` | admin central ou admin local (son espace) |
| auth | `POST /api/organisations` | admin central |
| patrimoine | `GET /api/public/biens`, `GET /api/public/biens/{id}`, `GET /api/public/categories` | public, champs limités |
| patrimoine | `GET /api/biens` (`q`, `categorie_id`, `type_patrimoine`, `organisation_id`, `statut`, `page`), `GET /api/biens/{id}`, `GET /api/categories` | authentifiés, portée organisationnelle |
| patrimoine | `GET /api/biens/stats`, `GET /api/biens/export`, `GET /api/engagements-financiers` | authentifiés, portée organisationnelle |
| patrimoine | `POST/PUT /api/biens`, `POST/PUT /api/engagements-financiers` | admin central, admin d'espace, gestionnaire |
| patrimoine | `DELETE /api/biens/{id}`, `DELETE /api/engagements-financiers/{id}` | admin central ou admin local |
| mouvement | `GET /api/mouvements` (`bien_id`, `type`) | tous |
| mouvement | `POST /api/mouvements` | admin, gestionnaire |

Exemple via la passerelle :
```bash
TOKEN=$(curl -s -X POST localhost:8080/gateway/auth/login -H 'Content-Type: application/json' \
  -d '{"email":"admin@etat.gov","password":"Admin@123"}' | jq -r .token)
curl -H "Authorization: Bearer $TOKEN" localhost:8080/gateway/patrimoine/biens/stats
```

## 9. Sécurité
Mots de passe hachés (bcrypt), jetons hachés (SHA-256) en base, contrôle des rôles dans chaque service, validation de toutes les entrées, CSRF actif sur l'interface web.

## 10. Déploiement gratuit
Les offres gratuites (Render, Koyeb, Fly.io, etc.) changent souvent : vérifier leurs limites actuelles. Démarche type : pousser le dépôt sur GitHub, créer une base PostgreSQL gratuite, créer un service par dossier `services/*` avec `docker/Dockerfile` (argument de build `SERVICE`), définir les variables `AUTH_URL`, `PATRIMOINE_URL`, `MOUVEMENT_URL`, `DB_*`, `RUN_MIGRATIONS` comme dans `docker-compose.yml`. Les offres gratuites mettent les services en veille : prévoir un temps de réveil, et garder la démonstration locale (`docker compose`) en secours pour l'oral.

## 11. Limites et évolutions
Pas de tests automatisés ; pas d'import CSV ni de pièces jointes ; passerelle simple (à remplacer par Traefik ou Kong) ; cohérence des mouvements entre services synchrone et non atomique (évolution : événements et saga). Le registre financier est un suivi simplifié et ne remplace pas un logiciel comptable.

## 12. Auteur
Mawoulomi SENOU – développeur Full Stack Junior
#   g e s t i o n - p a t r i m o i n e - e t a t  
 