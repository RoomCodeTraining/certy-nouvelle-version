# Réductions — Guide du système

Documentation des **types de réductions** appliqués aux contrats d'assurance automobile, de leur saisie à leur impact sur la prime TTC, la commission et les exports (PDF, bordereaux).

Complément de [`README-TARIFICATION.md`](README-TARIFICATION.md) (section 5.2) et [`README-BORDEREAUX.md`](README-BORDEREAUX.md) (assiette commission).

---

## 1. Rôle métier

Les réductions permettent d'ajuster la prime nette d'un contrat selon plusieurs critères commerciaux ou contractuels :

- **BNS** (Bonus / Malus) — réduction liée à l'historique de sinistralité
- **Commission** — réduction accordée sur la part commission
- **Profession** — réduction liée à la profession de l'assuré (% ou montant fixe)
- **Autre** — réduction libre en FCFA, soustraite en fin de chaîne

Toutes les réductions impactent le **montant payé par l'assuré** (Prime TTC) et, indirectement, la **base commission** des bordereaux.

---

## 2. Les 5 types supportés

| Champ (BDD) | Libellé UI | Type | Plage | Application |
|-------------|------------|------|-------|-------------|
| `reduction_bns` | Réduction BNS | Pourcentage | 0–100 % | Sur **chaque garantie**, puis somme |
| `reduction_on_commission` | Réduction sur commission | Pourcentage | 0–100 % | Sur **chaque garantie**, puis somme |
| `reduction_on_profession_percent` | Réduction profession | Pourcentage | 0–100 % | Sur **chaque garantie**, puis somme |
| `reduction_on_profession_amount` | Réduction profession | Montant fixe (FCFA) | ≥ 0 | Réparti **au prorata** sur chaque garantie |
| `reduction_amount` | Réduction autre | Montant fixe (FCFA) | ≥ 0 | Soustrait **après** les réductions % et profession fixe |

### Montants calculés et stockés

Lors du calcul (`computeAndFillStoredAmounts`), le système persiste les montants dérivés :

| Champ stocké | Description |
|--------------|-------------|
| `reduction_bns_amount` | Montant FCFA correspondant au % BNS |
| `reduction_on_commission_amount` | Montant FCFA correspondant au % commission |
| `reduction_on_profession_amount_stored` | Montant FCFA profession (% ou fixe) |
| `total_reduction_amount` | **Total global** de toutes les réductions en FCFA |

---

## 3. Garanties concernées

Les réductions s'appliquent sur l'ensemble des garanties du contrat :

```text
garanties = RC (rc_amount)
          + DR (defence_appeal_amount)
          + TP (person_transport_amount)
          + chaque garantie optionnelle (metadata optional_guarantees)
          + reliquat optional_guarantees_amount si besoin
```

Chaque garantie est réduite **individuellement**, puis les montants réduits sont **sommés** pour obtenir la prime nette.

---

## 4. Formules de calcul

**Source :** `app/Models/Contract.php` → `computeAndFillStoredAmounts()`

### 4.1 Réductions en pourcentage (BNS, commission, profession %)

Les trois pourcentages sont **additionnés** avant application :

```text
totalPct = reduction_bns + reduction_on_commission + reduction_on_profession_percent
```

Pour chaque garantie G :

```text
reduced = G
si totalPct > 0 :
  reduced = max(0, G − round(G × totalPct / 100))
```

> Les trois % ne se cumulent pas séparément sur chaque garantie : un seul taux global `totalPct` est appliqué en une passe.

### 4.2 Réduction profession en montant fixe

Si `reduction_on_profession_amount > 0`, le montant est réparti proportionnellement :

```text
share = round((G / totalGaranties) × reduction_on_profession_amount)
reduced = max(0, reduced − share)
```

Cette réduction s'applique **en plus** des réductions en %.

### 4.3 Réduction autre

Après réduction de toutes les garanties :

```text
primeNette = Σ reduced
montantApresReduction = max(0, primeNette − reduction_amount)
```

La « réduction autre » est la seule à être soustraite **sur le total**, et non garantie par garantie.

### 4.4 Montants stockés par type

```text
reduction_bns_amount           = round(totalGaranties × reduction_bns / 100)           si BNS > 0
reduction_on_commission_amount = round(totalGaranties × reduction_on_commission / 100) si commission > 0
reduction_on_profession_amount_stored = round(totalGaranties × profession_% / 100)     si profession % > 0
                                      = reduction_on_profession_amount                 sinon (montant fixe)

total_reduction_amount = totalGaranties − primeNette + reduction_amount
```

### 4.5 Profession : % ou montant fixe ?

Pour le montant stocké `reduction_on_profession_amount_stored` :

- Si `reduction_on_profession_percent > 0` → le **pourcentage prime** (calculé sur le total des garanties avant réduction)
- Sinon → le **montant fixe** `reduction_on_profession_amount`

Les deux peuvent coexister en base, mais le % est prioritaire pour le montant stocké. En pratique, un seul mode est utilisé à la fois.

---

## 5. Impact sur la prime TTC

Après réductions, le calcul continue (voir [`README-TARIFICATION.md`](README-TARIFICATION.md)) :

```text
taxes_amount = round((montantApresReduction + accessory_amount) × 0.145)

hasReduction = (totalPct > 0) OU (profession_fixe > 0) OU (reduction_amount > 0)

si hasReduction ET RC après réduction > 0 :
  fga_amount = round(RC_après_réduction × 0.02)
sinon :
  fga_amount = valeur grille (fga_amount stocké)

total_amount (Prime TTC) = montantApresReduction
                         + accessory_amount
                         + taxes_amount
                         + fga_amount
                         + cedeao_amount
                         + cp_amount   (Prime ASACI)
```

### Effet sur la FGA

Dès qu'une réduction est active, la **taxe FGA** n'est plus prise telle quelle depuis la grille : elle est recalculée à **2 % de la RC après réduction**.

---

## 6. Impact sur la commission (bordereaux)

La base commission utilise le total stocké des réductions :

```text
garanties = rc_amount + defence_appeal_amount + person_transport_amount + optional_guarantees_amount

après_réduction = max(0, garanties − total_reduction_amount)

prime_nette_commission = après_réduction + accessory_amount
```

Cette formule est **équivalente** à `montantApresReduction + accessory_amount` lorsque `total_reduction_amount` a été correctement calculé par `computeAndFillStoredAmounts()`.

Voir [`README-BORDEREAUX.md`](README-BORDEREAUX.md) pour le détail des bordereaux.

---

## 7. Saisie et modification (interface)

### 7.1 Création de contrat

**Fichier :** `resources/js/Pages/Contracts/Create.vue`

Champs saisissables dans le formulaire :

| Champ | Libellé |
|-------|---------|
| `reduction_bns` | (%) Réduction BNS |
| `reduction_on_commission` | (%) Réduction sur commission |
| `reduction_on_profession_percent` | (%) Réduction profession |

Les champs `reduction_amount` et `reduction_on_profession_amount` existent dans le modèle de formulaire et sont pris en compte dans le **calcul côté client** (preview), mais **n'ont pas de champ de saisie dédié** dans l'interface de création.

### 7.2 Édition de contrat

**Fichier :** `resources/js/Pages/Contracts/Edit.vue`

Mêmes trois champs en pourcentage que la création. Pas de saisie pour « réduction autre » ni profession fixe.

### 7.3 Modification post-validation (root uniquement)

**Fichier :** `resources/js/Pages/Contracts/Show.vue`  
**Route :** `PUT /contracts/{contract}/reductions`  
**Contrôleur :** `ContractController::updateReductions()`

Conditions :

- Utilisateur **root** (`is_root = true`)
- Contrat en statut **`validated`** ou **`active`**

Champs modifiables depuis la fiche contrat :

- `reduction_bns`
- `reduction_on_commission`
- `reduction_on_profession_percent`

> Note : `UpdateContractReductionsRequest` accepte aussi `reduction_on_profession_amount`, mais le contrôleur ne transmet actuellement que les trois champs en %.

### 7.4 Validation API

| Request | Champs réductions |
|---------|-------------------|
| `StoreContractRequest` | Les 5 champs |
| `UpdateContractRequest` | Les 5 champs |
| `UpdateContractReductionsRequest` | BNS, commission, profession %, profession montant |

---

## 8. Affichage

### 8.1 Fiche contrat (Show.vue)

Affichage conditionnel des pourcentages saisis et du montant « Réduction autre » :

- Réduction BNS (si > 0 %)
- Réduction commission (si > 0 %)
- Réduction profession (si > 0 %)
- Réduction autre (si > 0 FCFA, en rouge avec signe −)
- Montant après réduction

### 8.2 PDF contrat

**Fichier :** `resources/views/contracts/pdf.blade.php`

- Tableau **Garanties** : montants **après réduction** (via `getDisplayAmounts()`)
- **Résumé financier** : ligne « Réduction autre » si `reduction_amount > 0`, puis « Montant après réduction »

Les réductions % ne sont pas détaillées ligne par ligne dans le PDF : elles sont déjà intégrées dans les montants de garanties affichés.

### 8.3 Liste et exports

- `Index.vue` : calcul preview avec les mêmes règles que Create
- `Production.vue` / exports : agrégation via `total_reduction_amount`

---

## 9. Fichiers clés

| Fichier | Rôle |
|---------|------|
| `app/Models/Contract.php` | Calcul (`computeAndFillStoredAmounts`), affichage (`getDisplayAmounts`, `getGuaranteeAmountsReducedForDisplay`), commission (`prime_nette_for_commission`) |
| `app/Actions/StoreContractAction.php` | Persistance à la création |
| `app/Actions/UpdateContractAction.php` | Persistance à la mise à jour |
| `app/Http/Controllers/ContractController.php` | `updateReductions()` (root) |
| `resources/js/Pages/Contracts/Create.vue` | Saisie + preview |
| `resources/js/Pages/Contracts/Show.vue` | Affichage + édition root |
| `resources/views/contracts/pdf.blade.php` | PDF |
| `database/migrations/2026_02_15_160000_add_reduction_amount_to_contracts_table.php` | Colonne `reduction_amount` |
| `database/migrations/2026_02_15_180000_add_reductions_and_accessories_to_contracts_table.php` | Colonnes BNS, commission, profession |
| `database/migrations/2026_02_15_190000_add_stored_amounts_to_contracts_table.php` | Montants calculés stockés |

---

## 10. Exemple chiffré

Contrat avec :

- RC = 50 000 FCFA
- DR = 5 000 FCFA
- TP = 0
- `reduction_bns` = 10 %
- `reduction_on_commission` = 5 %
- `reduction_amount` = 1 000 FCFA

```text
totalGaranties = 55 000
totalPct = 10 + 5 = 15 %

RC réduite  = max(0, 50 000 − round(50 000 × 15 / 100)) = 42 500
DR réduite  = max(0,  5 000 − round( 5 000 × 15 / 100)) =  4 250

primeNette = 42 500 + 4 250 = 46 750
montantApresReduction = max(0, 46 750 − 1 000) = 45 750

reduction_bns_amount           = round(55 000 × 10 / 100) = 5 500
reduction_on_commission_amount = round(55 000 ×  5 / 100) = 2 750
total_reduction_amount         = 55 000 − 46 750 + 1 000 = 9 250
```

Ensuite : taxes 14,5 %, FGA 2 % sur RC réduite (42 500 → 850 FCFA), + accessoires, CEDEAO, Prime ASACI.

---

## 11. Récapitulatif visuel

```text
Grille tarifaire
      │
      ▼
┌─────────────────────────────────────────┐
│  Garanties brutes (RC, DR, TP, opt.)    │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│  Réductions % (BNS + commission + prof) │
│  appliquées sur CHAQUE garantie         │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│  Réduction profession fixe (prorata)    │  (optionnel)
└──────────────────┬──────────────────────┘
                   │
                   ▼
              primeNette
                   │
                   ▼
┌─────────────────────────────────────────┐
│  Réduction autre (FCFA)                 │  (optionnel)
└──────────────────┬──────────────────────┘
                   │
                   ▼
         montantApresReduction
                   │
                   ▼
    + accessoire + taxes 14,5 % + FGA + CEDEAO + Prime ASACI
                   │
                   ▼
              Prime TTC
```
