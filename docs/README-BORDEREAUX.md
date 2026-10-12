# Bordereaux — Guide du système

Documentation du module **bordereaux de production et de reversement de primes** (Laravel + Inertia/Vue).

Objectif : comprendre le flow pour le réutiliser ou le porter dans un autre projet.

---

## 1. Rôle métier

Un bordereau regroupe la production d’un **courtier** pour une **compagnie** sur une **période**, afin de :

1. Lister les contrats concernés
2. Calculer la **commission** du courtier
3. Calculer la **prime à reverser** à l’assureur
4. Exporter (PDF / Excel) et éventuellement **valider**

Accès : réservé aux utilisateurs **root** (middleware `root`).

---

## 2. Vue d’ensemble du flow

```text
Compagnie + période (date début / fin)
        │
        ▼
┌───────────────────────────────────────┐
│  Sélection des contrats               │
│  • même company_id                    │
│  • created_at ∈ [period_start, end]   │
│  • status ≠ cancelled                 │
└───────────────────┬───────────────────┘
                    │
                    ▼
┌───────────────────────────────────────┐
│  Calculs                              │
│  • Prime TTC = Σ contracts.total_amount│
│  • Commission = Σ round(prime_nette × % / 100)
│  • Prime à reverser = TTC − commission │
└───────────────────┬───────────────────┘
                    │
                    ▼
┌───────────────────────────────────────┐
│  Bordereau (status = draft)           │
│  + référence BR-XXXXXXXXXXX           │
│  → Show / Valider / PDF / Excel       │
└───────────────────────────────────────┘
```

**Point important :** il n’y a **pas de table pivot** contrat ↔ bordereau.  
Les lignes sont **recalculées à la volée** à chaque affichage / PDF / Excel selon les mêmes filtres.

---

## 3. Modèle de données

### Table `bordereaux`

| Champ | Type | Rôle |
|-------|------|------|
| `organization_id` | FK | Organisation du créateur |
| `company_id` | FK | Compagnie (assureur) |
| `reference` | string | `BR-` + 11 caractères alphanumériques |
| `status` | string | Voir cycle de vie |
| `period_start` / `period_end` | date | Fenêtre sur `contracts.created_at` |
| `total_amount` | decimal | Σ Prime TTC figée à la création |
| `total_commission` | decimal | Σ commissions figée à la création |
| `commission_pct` | decimal | % figé à la création (depuis config) |
| `submitted_at` / `approved_at` / `paid_at` | timestamps | Présents en schéma, peu utilisés |

### Relations

```text
Organization 1 ── * Bordereau * ── 1 Company
Contract  ✗  (pas de FK vers Bordereau)
```

### Config commission

Table `organization_company_configs` : taux `commission` (%) par **code compagnie** (`companies.code`).

À la création du bordereau :

```text
commission_pct = OrganizationCompanyConfig.where(code = company.code).commission
```

---

## 4. Contrats inclus

Même requête pour **création**, **Show**, **PDF** et **Excel** :

| Critère | Règle |
|---------|--------|
| Compagnie | `contracts.company_id` = bordereau |
| Période | `whereDate(created_at)` entre `period_start` et `period_end` |
| Statut | **tout sauf** `cancelled` (`draft`, `validated`, `active`, `expired` inclus) |
| Accès | `Contract::accessibleBy($user)` |

> La période porte sur la **date de création** du contrat, **pas** sur `start_date` / `end_date`.

> Conséquence : un contrat créé après le bordereau, mais dont `created_at` tombe dans la période, apparaîtra au Show/PDF alors que les totaux stockés (`total_amount`, `total_commission`) restent ceux de la création.

---

## 5. Formules (FCFA)

### Par contrat

**Prime nette** (base commission) — accessor `prime_nette_for_commission` :

```text
garanties = rc_amount
          + defence_appeal_amount
          + person_transport_amount
          + optional_guarantees_amount

après_réduction = max(0, garanties − total_reduction_amount)

prime_nette = après_réduction + accessory_amount
```

**Hors** assiette commission : taxes, FGA, CEDEAO, Prime ASACI (`cp_amount`).

**Prime TTC** (ligne bordereau) :

```text
prime_ttc = contracts.total_amount
```

**Commission** :

```text
si commission_pct renseigné et prime_nette > 0 :
  commission = round(prime_nette × commission_pct / 100)
sinon :
  commission = 0
```

**Prime à reverser** (ligne) :

```text
prime_a_reverser = max(0, prime_ttc − commission)
```

(également affichée comme montant encaissé / à reverser selon le libellé UI).

### Totaux bordereau

| Champ | Formule |
|-------|---------|
| `total_amount` | Σ `contracts.total_amount` |
| `total_commission` | Σ commissions lignes |
| Prime à reverser (dérivée) | `total_amount − total_commission` |

---

## 6. Cycle de vie

Statuts définis sur le modèle :

`draft` · `validated` · `submitted` · `approved` · `rejected` · `paid`

| Transition | Implémentée ? |
|------------|----------------|
| Création → `draft` | Oui |
| `draft` → `validated` (`POST …/validate`) | Oui |
| Suppression (uniquement `draft`) | Oui |
| `submitted` / `approved` / `rejected` / `paid` | **Non** (pas d’endpoints UI) |

Après **validation** : plus de suppression ni de re-validation ; PDF / Excel restent disponibles.

---

## 7. Création (détail technique)

1. `GET /bordereaux/create` — formulaire compagnie + période  
2. `POST /bordereaux` — `BordereauController::store`  
3. Charger les contrats filtrés  
4. Sommer `total_amount`  
5. Lire `commission_pct` depuis `OrganizationCompanyConfig`  
6. Sommer les commissions ligne à ligne  
7. Créer le bordereau (`status = draft`)  
8. Générer `reference` (`Bordereau::generateUniqueReference()`)  
9. Redirection vers Show  

Toute la logique métier est dans le **contrôleur** (pas de service dédié).

---

## 8. Exports

| Export | Route | Techno |
|--------|-------|--------|
| PDF | `GET /bordereaux/{id}/pdf` | DomPDF + vue Blade |
| Excel | `GET /bordereaux/{id}/excel` | PhpSpreadsheet |

PDF : `resources/views/bordereaux/pdf.blade.php`  
Titre type : « BORDEREAU DE PRODUCTION ET DE REVERSEMENT DE PRIMES » (A4 paysage).

Les lignes utilisent les **mêmes formules** que le Show (recalcul live).

---

## 9. Frontend

| Page | Fichier | Rôle |
|------|---------|------|
| Liste | `resources/js/Pages/Bordereaux/Index.vue` | Filtres, liens |
| Création | `…/Create.vue` | Compagnie + dates |
| Détail | `…/Show.vue` | Totaux, lignes, Valider / Supprimer / PDF / Excel |

Routes web : middleware `root` dans `routes/web.php`.

---

## 10. Fichiers clés

| Rôle | Chemin |
|------|--------|
| Contrôleur | `app/Http/Controllers/BordereauController.php` |
| Modèle | `app/Models/Bordereau.php` |
| Prime nette contrat | `app/Models/Contract.php` (`getPrimeNetteForCommissionAttribute`) |
| Config commission | `app/Models/OrganizationCompanyConfig.php` |
| PDF | `resources/views/bordereaux/pdf.blade.php` |
| UI | `resources/js/Pages/Bordereaux/*.vue` |
| Migrations | `database/migrations/2026_02_15_100016_create_bordereaux_table.php` (+ total_commission, commission_pct) |

---

## 11. Checklist de réimplémentation

1. Table bordereau : org, compagnie, période, totaux, `commission_pct` figé, référence `BR-…`
2. Sélection contrats : `company_id` + `created_at` dans la période + `≠ cancelled` (sans pivot)
3. Commission = `round(prime_nette × % / 100)` ; prime nette = garanties − réductions + accessoire
4. Prime à reverser = Prime TTC − commission
5. Cycle : `draft` → `validated` (+ suppression draft uniquement)
6. PDF / Excel avec les mêmes formules que le détail
7. Restreindre l’accès (ici : root)

---

## 12. Schéma résumé

```text
┌────────────────────────────────────────────────────────────┐
│  Contrats (compagnie + période created_at, ≠ cancelled)    │
│                                                            │
│  Pour chaque contrat :                                     │
│    Prime TTC          = total_amount                       │
│    Prime nette        = (RC+DR+TP+opt − réductions)        │
│                       + accessoire                         │
│    Commission         = round(nette × commission_pct/100)  │
│    Prime à reverser   = max(0, TTC − commission)           │
│                                                            │
│  Totaux bordereau :                                        │
│    Σ Prime TTC · Σ Commission · (TTC − Commission)         │
└────────────────────────────────────────────────────────────┘
```

---

*Document basé sur l’implémentation Certy / kissappmetier.*
