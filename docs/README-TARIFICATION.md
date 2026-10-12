# Tarification contrats d’assurance — Guide de réimplémentation

Documentation du système de tarification tel qu’implémenté dans ce projet (Laravel + Inertia/Vue).  
Objectif : permettre de **reproduire le même flow** dans un autre projet.

Monnaie : **FCFA** (entiers partout). Arrondis : `round()` / `Math.round()` à l’entier le plus proche.

---

## 1. Vue d’ensemble du flow

```text
Véhicule + type contrat + dates
        │
        ▼
┌───────────────────────────┐
│  Grille tarifaire         │  VP / TPC / TPM / TWO_WHEELER
│  (lookup par critères)    │
└─────────────┬─────────────┘
              │ ContractPricingService::calculate()
              ▼
┌───────────────────────────┐
│  Montants grille          │  RC, DR, TP, Accessoire,
│  + CEDEAO + Prime ASACI   │  FGA grille, taxes provisoires
│  + FGA grille             │
└─────────────┬─────────────┘
              │ Preview AJAX (Create) / Store
              ▼
┌───────────────────────────┐
│  Garanties optionnelles   │  rate % × valeur véhicule
│  + Réductions             │  % BNS, commission, profession + fixe
└─────────────┬─────────────┘
              │ Contract::computeAndFillStoredAmounts()
              ▼
┌───────────────────────────┐
│  Prime TTC (source de     │  = après réductions + accessoire
│  vérité backend)          │  + taxes 14,5 % + FGA + CEDEAO
│                           │  + Prime ASACI (cp_amount)
└───────────────────────────┘
```

**Règle d’or :** le front (Create.vue) **mire** les formules pour l’affichage, mais le **backend** (`computeAndFillStoredAmounts`) est la source de vérité à la sauvegarde.

---

## 2. Types de contrat

| Code | Signification | Clé de lookup grille |
|------|---------------|----------------------|
| `VP` | Véhicule particulier | énergie + durée + puissance fiscale |
| `TPC` | Transport de personnes / marchandises léger | durée + charge utile (tonnes) |
| `TPM` | Transport de marchandises | durée + charge utile (tonnes) |
| `TWO_WHEELER` | Deux-roues | cylindrée (cm³) uniquement |

---

## 3. Grilles tarifaires

### Colonnes communes (montants)

| Champ | Rôle |
|-------|------|
| `base_amount` | Métadonnée grille — **ne entre pas** dans le calcul prime nette / TTC |
| `rc_amount` | Responsabilité civile |
| `defence_appeal_amount` | Défense & recours (DR) |
| `person_transport_amount` | Transport de personnes (TP) |
| `accessory_amount` | Accessoire (grille) |
| `taxes_amount` | Valeur seedée ; **recalculée** à la finalisation |
| `cedeao_amount` | CEDEAO (souvent **1000** ; **0** sur 2-roues) |
| `cp_amount` | **Prime ASACI** (souvent **1000** ; **0** sur 2-roues) |
| `fga_amount` | Taxe FGA grille (gardée si **aucune** réduction) |
| `is_active` | Seules les lignes actives sont interrogées |

### 3.1 VP — `vp_pricing_grids`

**Clé unique :** `(energy_source_id, duration, power_range)`

**Mapping puissance fiscale (CV) + énergie :**

| Énergie | Code | CV | `power_range` |
|---------|------|----|---------------|
| Essence | `SEES` | ≤ 6 | `3_6` |
| | | ≤ 9 | `7_9` |
| | | ≤ 11 | `10_11` |
| | | sinon | `12_plus` |
| Diesel | `SEDI` | ≤ 4 | `2_4` |
| | | ≤ 6 | `5_6` |
| | | ≤ 8 | `7_8` |
| | | sinon | `9_plus` |

Champs véhicule : `energy_source_id`, `fiscal_power`.

### 3.2 TPC — `tpc_pricing_grids`

**Clé unique :** `(duration, payload_range)`

| Charge utile (t) | `payload_range` |
|------------------|-----------------|
| ≤ 1 | `inferior_or_equal_to_1` |
| &lt; 3 | `1_3` |
| &lt; 5 | `3_5` |
| &lt; 9 | `5_8` |
| &lt; 12 | `9_12` |
| &lt; 15 | `12_15` |
| sinon | `over_15` |

Champ véhicule : `payload_capacity`.

### 3.3 TPM — `tpm_pricing_grids`

Même logique de tonnage que TPC, sauf le dernier bucket : `15_plus` (au lieu de `over_15`).

### 3.4 Deux-roues — `two_wheeler_pricing_grids`

**Clé :** `power_range` seul (pas de durée / énergie).

| Cylindrée (cm³) | `power_range` |
|-----------------|---------------|
| &lt; 50 | `inferior_to_50` |
| &lt; 100 | `51_99` |
| &lt; 176 | `100_175` |
| ≤ 350 | `176_350` |
| sinon | `350_plus` |

Champ véhicule : `engine_capacity`. L’UI force souvent une durée 12 mois, mais la grille n’utilise pas la durée.

### 3.5 Durées disponibles par grille (seeders)

| Grille | Durées seedées | Codes |
|--------|----------------|-------|
| **VP** (essence & diesel) | 1, 2, 3, 6, 12 mois | `1_month`, `2_months`, `3_months`, `6_months`, `12_months` |
| **TPC** | 1, 3, 6, 12 mois (**pas** de 2 mois) | `1_month`, `3_months`, `6_months`, `12_months` |
| **TPM** | 1, 3, 6, 12 mois (**pas** de 2 mois) | idem TPC |
| **Deux-roues** | aucune (tarif unique par cylindrée) | — |

### 3.6 Résolution de durée (`resolveDuration`)

À partir de `start_date` / `end_date` (`diffInMonths`) :

| Mois | Valeur |
|------|--------|
| ≤ 1 | `1_month` |
| ≤ 2 | `2_months` |
| ≤ 3 | `3_months` |
| ≤ 6 | `6_months` |
| sinon | `12_months` |

Calcul date de fin depuis une durée UI : `start + N mois − 1 jour`.

> **Attention TPC/TPM :** si `resolveDuration` renvoie `2_months`, aucune ligne de grille n’existe → le lookup échoue. Prévoir soit une durée UI sans 2 mois, soit un fallback.

Les **montants complets** (durée × tranche) sont en **annexe A–E** en fin de document.

---

## 4. Pipeline technique

### Étape A — Lookup grille

**Service :** `app/Services/ContractPricingService.php`

```text
calculate(Contract) → selon contract_type → ligne grille → tableau de montants
applyToContract(Contract) → calculate() puis contract->update($amounts)
```

Pour chaque type, après résolution de la ligne :

```text
primeNette_grille = rc + defence_appeal + person_transport
                  + optional_guarantees_amount

taxes_amount (provisoire) = round(primeNette_grille × 0.145)
```

Champs retournés :  
`base_amount`, `rc_amount`, `defence_appeal_amount`, `person_transport_amount`,  
`accessory_amount`, `taxes_amount`, `cedeao_amount`, `cp_amount`, `fga_amount`.

### Étape B — Preview (création)

- Route : `POST /contracts/preview`
- Contrôleur : `ContractController::preview`
- Front : `Create.vue` → `fetchPreview()` → remplit `recap.amounts`
- **Désactivé** en mode avenant (endorsement)

Le preview **n’applique pas** encore la logique complète de réductions / TTC final.

### Étape C — Sauvegarde

**Action :** `app/Actions/StoreContractAction.php`

1. `Contract::create(...)`
2. `pricingService->applyToContract($contract)` — écrit les montants grille
3. Applique les `*_override` éventuels (`cedeao_amount_override`, `cp_amount_override`, etc.)
4. `$contract->computeAndFillStoredAmounts()` — **TTC définitif**

### Étape D — Calcul final (source de vérité)

**Modèle :** `app/Models/Contract.php` → `computeAndFillStoredAmounts()`

---

## 5. Formules (à recopier à l’identique)

### 5.1 Ensemble des garanties

```text
garanties = RC + DR + TP (si > 0)
          + chaque optional_guarantees[].amount (metadata)
          + residual optional_guarantees_amount si besoin
```

### 5.2 Réductions (appliquées **sur chaque garantie**, puis somme)

Entrées :

- `reduction_bns` (%)
- `reduction_on_commission` (%)
- `reduction_on_profession_percent` (%)
- `reduction_on_profession_amount` (FCFA fixe, réparti au prorata)
- `reduction_amount` (« autre », soustraite **après** les %)

```text
totalPct = reduction_bns + reduction_on_commission + reduction_on_profession_percent

Pour chaque garantie G :
  reduced = G
  si totalPct > 0 :
    reduced = max(0, G − round(G × totalPct / 100))
  si profession_fixe > 0 :
    share = round((G / totalGaranties) × profession_fixe)
    reduced = max(0, reduced − share)

primeNette = Σ reduced
montantApresReduction = max(0, primeNette − reduction_amount)
```

### 5.3 Taxes 14,5 %

```text
taxes_amount = round((montantApresReduction + accessory_amount) × 0.145)
```

Constante : **`0.145`**. Écrase la taxe provisoire de la grille.

### 5.4 Taxe FGA 2 %

```text
hasReduction = (totalPct > 0) OU (profession_fixe > 0) OU (reduction_amount > 0)

si hasReduction ET rc_apres_reduction > 0 :
  fga_amount = round(rc_apres_reduction × 0.02)
sinon :
  fga_amount = valeur grille (inchangée)
```

Constante : **`0.02`**.

### 5.5 CEDEAO & Prime ASACI

- Issues de la **grille** (ou overrides)
- **Non** recalculées via pourcentages
- Valeurs typiques seedées : **1000** (VP/TPC/TPM), **0** (deux-roues)
- Libellé UI/PDF pour `cp_amount` : **« Prime ASACI »**

### 5.6 Prime TTC (`total_amount` / `prime_ttc`)

```text
total_amount =
    montantApresReduction
  + accessory_amount          // accessoire grille uniquement
  + taxes_amount
  + fga_amount
  + cedeao_amount             // CEDEAO
  + cp_amount                 // Prime ASACI

prime_ttc = total_amount
```

### 5.7 Accessoires compagnie / agence

`company_accessory` et `agency_accessory` sont stockés et affichés côté UI comme  
**Montant à payer = Prime TTC + company + agency**,  
mais **ne sont pas** ajoutés dans `computeAndFillStoredAmounts()` / `total_amount`.

---

## 6. Garanties optionnelles

Table : `optional_guarantees`  
Calcul UI : `amount = round((valeur_base × rate) / 100)`

| `base` | Champ véhicule |
|--------|----------------|
| `new` | `new_value` (valeur neuve) |
| `venale` | `replacement_value` (valeur vénale) |

Garanties seedées par défaut :

| Code | Taux | Base |
|------|------|------|
| `DOMMAGE_VEHICULE` | 6,5 % | new |
| `DOMMAGE_TOUT_ACCIDENT` | 7,5 % | new |
| `BRIS_GLACE` | 0,2 % | new |
| `INCENDIE` | 1,5 % | venale |
| `VOL_AGRESSION` | 1,5 % | venale |

Stockage contrat :

- `optional_guarantees_amount` (somme)
- `metadata.optional_guarantees` = `[{ code, label, rate, base, amount }, ...]`

Elles entrent dans la **prime nette** (donc dans l’assiette des taxes après réductions).

---

## 7. Cas particulier : avenant (endorsement)

Constantes :

- Backend : `Contract::PDF_ENDORSEMENT_CEDEAO_FCFA = 1000`
- Frontend : `ENDORSEMENT_CEDEAO_FIXED_FCFA = 1000`

Comportement Create :

- Tous les montants (RC, DR, TP, taxes, FGA, accessoire, **Prime ASACI**) → **0**
- Seule la **CEDEAO** reste à **1000 FCFA**
- Garanties optionnelles et réductions vidées
- Preview AJAX ignoré

**TTC avenant = 1000 FCFA** (CEDEAO seule).

---

## 8. Miroir frontend (`displayTotal`)

Fichier : `resources/js/Pages/Contracts/Create.vue`

```text
displayTotal =
    montantApresReductionCreate
  + accessoire grille
  + taxes (14,5 %)
  + FGA
  + CEDEAO
  + Prime ASACI (cp_amount)
```

Les overrides (`*_amount_override`) permettent de forcer une ligne (surtout en avenant / édition).

---

## 9. Fichiers clés à porter dans un autre projet

| Rôle | Chemin |
|------|--------|
| Service de pricing | `app/Services/ContractPricingService.php` |
| Calcul TTC / réductions | `app/Models/Contract.php` (`computeAndFillStoredAmounts`, `getDisplayAmounts`) |
| Store | `app/Actions/StoreContractAction.php` |
| Preview API | `app/Http/Controllers/ContractController.php` (`preview`) |
| Validation store | `app/Http/Requests/StoreContractRequest.php` |
| Admin grilles | `app/Http/Controllers/Settings/PricingGridController.php` |
| UI grilles | `resources/js/Pages/Settings/PricingGrids/Index.vue` |
| UI création | `resources/js/Pages/Contracts/Create.vue` |
| PDF contrat | `resources/views/contracts/pdf.blade.php` |
| Modèles grilles | `app/Models/{Vp,Tpc,Tpm,TwoWheeler}PricingGrid.php` |
| Enums | `app/Enums/{ContractDuration,VpPowerRange,TpcPayloadRange,TpmPayloadRange,TwoWheelerPowerRange}Enum.php` |
| Seeders | `database/seeders/{Vp,Tpc,Tpm,TwoWheeler}PricingGridSeeder.php` |
| Garanties optionnelles | `app/Models/OptionalGuarantee.php` + migration associée |

---

## 10. Checklist de réimplémentation

1. Créer les 4 tables de grilles + seed des montants (dont `cedeao_amount` et `cp_amount`).
2. Sur le véhicule : stocker `pricing_type`, `fiscal_power`, `energy_source_id`, `payload_capacity`, `engine_capacity`, `new_value`, `replacement_value`.
3. Implémenter le mapping critères → `power_range` / `payload_range` / durée.
4. Implémenter `ContractPricingService::calculate` + `applyToContract`.
5. Sur le contrat : colonnes de montants + `computeAndFillStoredAmounts` avec les constantes **0.145**, **0.02**.
6. Pipeline store : grille → overrides → `computeAndFillStoredAmounts`.
7. UI : preview + récap aligné sur le backend (`displayTotal`).
8. Afficher **CEDEAO** et **Prime ASACI** dans le récap, la fiche et le **PDF**.
9. Gérer l’avenant : CEDEAO seule à 1000 FCFA.
10. (Optionnel) Garanties optionnelles rate × valeur + réductions par garantie.

### Constantes à figer

| Constante | Valeur |
|-----------|--------|
| Taux taxe | `0.145` |
| Taux FGA | `0.02` |
| CEDEAO avenant | `1000` |
| CEDEAO / Prime ASACI (4 roues, défaut grille) | `1000` |
| CEDEAO / Prime ASACI (2 roues, défaut grille) | `0` |
| Code essence | `SEES` |
| Code diesel | `SEDI` |

---

## 11. Schéma résumé Prime TTC

```text
┌─────────────────────────────────────────────────────────────┐
│  RC + DR + TP + garanties optionnelles                      │
│            − réductions (% + fixe + autre)                  │
│  ─────────────────────────────                              │
│  = Montant après réduction                                  │
│       + Accessoire grille                                   │
│       + Taxes 14,5 % × (après réduction + accessoire)       │
│       + FGA (grille OU 2 % de la RC réduite)                │
│       + CEDEAO                                              │
│       + Prime ASACI (cp_amount)                             │
│  ─────────────────────────────                              │
│  = Prime TTC                                                │
│       (+ Accessoire compagnie / agence → « Montant à payer »│
│          côté UI uniquement)                                │
└─────────────────────────────────────────────────────────────┘
```

---

## 12. Annexes — Grilles tarifaires (montants seedés)

> Source : seeders `VpPricingGridSeeder`, `TpcPricingGridSeeder`, `TpmPricingGridSeeder`, `TwoWheelerPricingGridSeeder`.
> Montants en **FCFA**. Sur VP/TPC/TPM : CEDEAO = **1 000**, Prime ASACI = **1 000**. Sur deux-roues : les deux à **0**.
> Les `taxes_amount` ci-dessous sont les valeurs **seedées** ; à la finalisation du contrat elles sont **recalculées** (14,5 %).

### Annexe A — Grille VP Essence (`SEES`)

Durées présentes : **1, 2, 3, 6, 12 mois**.

| Durée | Tranche | Base | RC | DR | TP | Acc. | Taxes | CEDEAO | ASACI | FGA |
|-------|---------|------|----|----|----|------|-------|--------|-------|-----|
| 1 mois | 3 – 6 CV | 11 707 | 6 689 | 795 | 750 | 1 000 | 1 339 | 1 000 | 1 000 | 134 |
| 1 mois | 7 – 9 CV | 12 468 | 7 342 | 795 | 750 | 1 000 | 1 434 | 1 000 | 1 000 | 147 |
| 1 mois | 10 – 11 CV | 17 275 | 11 469 | 795 | 750 | 1 000 | 2 032 | 1 000 | 1 000 | 229 |
| 1 mois | ≥ 12 CV | 18 949 | 12 906 | 795 | 750 | 1 000 | 2 240 | 1 000 | 1 000 | 258 |
| 2 mois | 3 – 6 CV | 21 456 | 12 708 | 1 511 | 1 425 | 2 000 | 2 558 | 1 000 | 1 000 | 254 |
| 2 mois | 7 – 9 CV | 22 902 | 13 949 | 1 511 | 1 425 | 2 000 | 2 738 | 1 000 | 1 000 | 279 |
| 2 mois | 10 – 11 CV | 32 039 | 21 792 | 1 511 | 1 425 | 2 000 | 3 875 | 1 000 | 1 000 | 436 |
| 2 mois | ≥ 12 CV | 35 218 | 24 521 | 1 511 | 1 425 | 2 000 | 4 271 | 1 000 | 1 000 | 490 |
| 3 mois | 3 – 6 CV | 31 207 | 18 728 | 2 226 | 2 100 | 3 000 | 3 778 | 1 000 | 1 000 | 375 |
| 3 mois | 7 – 9 CV | 33 336 | 20 556 | 2 226 | 2 100 | 3 000 | 4 043 | 1 000 | 1 000 | 411 |
| 3 mois | 10 – 11 CV | 46 801 | 32 114 | 2 226 | 2 100 | 3 000 | 5 719 | 1 000 | 1 000 | 642 |
| 3 mois | ≥ 12 CV | 51 487 | 36 136 | 2 226 | 2 100 | 3 000 | 6 302 | 1 000 | 1 000 | 723 |
| 6 mois | 3 – 6 CV | 57 399 | 35 449 | 4 214 | 3 975 | 5 000 | 7 052 | 1 000 | 1 000 | 709 |
| 6 mois | 7 – 9 CV | 61 431 | 38 910 | 4 214 | 3 975 | 5 000 | 7 554 | 1 000 | 1 000 | 778 |
| 6 mois | 10 – 11 CV | 86 918 | 60 787 | 4 214 | 3 975 | 5 000 | 10 726 | 1 000 | 1 000 | 1 216 |
| 6 mois | ≥ 12 CV | 95 788 | 68 401 | 4 214 | 3 975 | 5 000 | 11 830 | 1 000 | 1 000 | 1 368 |
| 12 mois | 3 – 6 CV | 104 627 | 66 885 | 7 950 | 7 500 | 7 000 | 12 954 | 1 000 | 1 000 | 1 338 |
| 12 mois | 7 – 9 CV | 112 233 | 73 415 | 7 950 | 7 500 | 7 000 | 13 900 | 1 000 | 1 000 | 1 468 |
| 12 mois | 10 – 11 CV | 160 323 | 114 685 | 7 950 | 7 500 | 7 000 | 19 886 | 1 000 | 1 000 | 2 294 |
| 12 mois | ≥ 12 CV | 177 058 | 129 058 | 7 950 | 7 500 | 7 000 | 21 969 | 1 000 | 1 000 | 2 581 |

### Annexe B — Grille VP Diesel (`SEDI`)

Durées présentes : **1, 2, 3, 6, 12 mois**.

| Durée | Tranche | Base | RC | DR | TP | Acc. | Taxes | CEDEAO | ASACI | FGA |
|-------|---------|------|----|----|----|------|-------|--------|-------|-----|
| 1 mois | 2 – 4 CV | 11 707 | 6 689 | 795 | 750 | 1 000 | 1 339 | 1 000 | 1 000 | 134 |
| 1 mois | 5 – 6 CV | 12 468 | 7 342 | 795 | 750 | 1 000 | 1 434 | 1 000 | 1 000 | 147 |
| 1 mois | 7 – 8 CV | 17 275 | 11 469 | 795 | 750 | 1 000 | 2 032 | 1 000 | 1 000 | 229 |
| 1 mois | ≥ 9 CV | 18 949 | 12 906 | 795 | 750 | 1 000 | 2 240 | 1 000 | 1 000 | 258 |
| 2 mois | 2 – 4 CV | 21 456 | 12 708 | 1 511 | 1 425 | 2 000 | 2 558 | 1 000 | 1 000 | 254 |
| 2 mois | 5 – 6 CV | 22 902 | 13 949 | 1 511 | 1 425 | 2 000 | 2 738 | 1 000 | 1 000 | 279 |
| 2 mois | 7 – 8 CV | 32 039 | 21 792 | 1 511 | 1 425 | 2 000 | 3 875 | 1 000 | 1 000 | 436 |
| 2 mois | ≥ 9 CV | 35 218 | 24 521 | 1 511 | 1 425 | 2 000 | 4 271 | 1 000 | 1 000 | 490 |
| 3 mois | 2 – 4 CV | 31 207 | 18 728 | 2 226 | 2 100 | 3 000 | 3 778 | 1 000 | 1 000 | 375 |
| 3 mois | 5 – 6 CV | 33 336 | 20 556 | 2 226 | 2 100 | 3 000 | 4 043 | 1 000 | 1 000 | 411 |
| 3 mois | 7 – 8 CV | 46 801 | 32 114 | 2 226 | 2 100 | 3 000 | 5 719 | 1 000 | 1 000 | 642 |
| 3 mois | ≥ 9 CV | 51 487 | 36 136 | 2 226 | 2 100 | 3 000 | 6 302 | 1 000 | 1 000 | 723 |
| 6 mois | 2 – 4 CV | 57 399 | 35 449 | 4 214 | 3 975 | 5 000 | 7 052 | 1 000 | 1 000 | 709 |
| 6 mois | 5 – 6 CV | 61 431 | 38 910 | 4 214 | 3 975 | 5 000 | 7 554 | 1 000 | 1 000 | 778 |
| 6 mois | 7 – 8 CV | 86 918 | 60 787 | 4 214 | 3 975 | 5 000 | 10 726 | 1 000 | 1 000 | 1 216 |
| 6 mois | ≥ 9 CV | 95 788 | 68 401 | 4 214 | 3 975 | 5 000 | 11 830 | 1 000 | 1 000 | 1 368 |
| 12 mois | 2 – 4 CV | 104 627 | 66 885 | 7 950 | 7 500 | 7 000 | 12 954 | 1 000 | 1 000 | 1 338 |
| 12 mois | 5 – 6 CV | 112 233 | 73 415 | 7 950 | 7 500 | 7 000 | 13 900 | 1 000 | 1 000 | 1 468 |
| 12 mois | 7 – 8 CV | 160 323 | 114 685 | 7 950 | 7 500 | 7 000 | 19 886 | 1 000 | 1 000 | 2 294 |
| 12 mois | ≥ 9 CV | 177 058 | 129 058 | 7 950 | 7 500 | 7 000 | 21 969 | 1 000 | 1 000 | 2 581 |

### Annexe C — Grille TPC

Durées présentes : **1, 3, 6, 12 mois** (pas de 2 mois).

| Durée | Tranche | Base | RC | DR | TP | Acc. | Taxes | CEDEAO | ASACI | FGA |
|-------|---------|------|----|----|----|------|-------|--------|-------|-----|
| 1 mois | ≤ 1 t | 17 774 | 11 897 | 795 | 750 | 1 000 | 2 094 | 1 000 | 1 000 | 238 |
| 1 mois | 1 – 3 t | 21 993 | 15 519 | 795 | 750 | 1 000 | 2 619 | 1 000 | 1 000 | 310 |
| 1 mois | 3 – 5 t | 26 016 | 18 972 | 795 | 750 | 1 000 | 3 120 | 1 000 | 1 000 | 379 |
| 1 mois | 5 – 8 t | 32 412 | 24 462 | 795 | 750 | 1 000 | 3 916 | 1 000 | 1 000 | 489 |
| 1 mois | 9 – 12 t | 34 312 | 26 093 | 795 | 750 | 1 000 | 4 152 | 1 000 | 1 000 | 522 |
| 1 mois | 12 – 15 t | 36 555 | 28 000 | 795 | 750 | 1 000 | 4 450 | 1 000 | 1 000 | 560 |
| 1 mois | > 15 t | 38 915 | 30 000 | 795 | 750 | 1 000 | 4 770 | 1 000 | 1 000 | 600 |
| 3 mois | ≤ 1 t | 48 194 | 33 310 | 2 226 | 2 100 | 3 000 | 5 892 | 1 000 | 1 000 | 666 |
| 3 mois | 1 – 3 t | 60 011 | 43 453 | 2 226 | 2 100 | 3 000 | 7 363 | 1 000 | 1 000 | 869 |
| 3 mois | 3 – 5 t | 71 273 | 53 120 | 2 226 | 2 100 | 3 000 | 8 765 | 1 000 | 1 000 | 1 062 |
| 3 mois | 5 – 8 t | 89 183 | 68 493 | 2 226 | 2 100 | 3 000 | 10 994 | 1 000 | 1 000 | 1 370 |
| 3 mois | 9 – 12 t | 94 502 | 73 059 | 2 226 | 2 100 | 3 000 | 11 656 | 1 000 | 1 000 | 1 461 |
| 3 mois | 12 – 15 t | 100 759 | 78 400 | 2 226 | 2 100 | 3 000 | 12 465 | 1 000 | 1 000 | 1 568 |
| 3 mois | > 15 t | 107 344 | 84 000 | 2 226 | 2 100 | 3 000 | 13 338 | 1 000 | 1 000 | 1 680 |
| 6 mois | ≤ 1 t | 89 556 | 63 051 | 4 214 | 3 975 | 5 000 | 11 055 | 1 000 | 1 000 | 1 261 |
| 6 mois | 1 – 3 t | 111 924 | 82 251 | 4 214 | 3 975 | 5 000 | 13 839 | 1 000 | 1 000 | 1 645 |
| 6 mois | 3 – 5 t | 133 241 | 100 549 | 4 214 | 3 975 | 5 000 | 16 492 | 1 000 | 1 000 | 2 011 |
| 6 mois | 5 – 8 t | 167 140 | 129 647 | 4 214 | 3 975 | 5 000 | 20 711 | 1 000 | 1 000 | 2 593 |
| 6 mois | 9 – 12 t | 177 209 | 138 290 | 4 214 | 3 975 | 5 000 | 21 964 | 1 000 | 1 000 | 2 766 |
| 6 mois | 12 – 15 t | 189 853 | 148 200 | 4 214 | 3 975 | 5 000 | 23 500 | 1 000 | 1 000 | 2 964 |
| 6 mois | > 15 t | 203 305 | 158 800 | 4 214 | 3 975 | 5 000 | 25 150 | 1 000 | 1 000 | 3 176 |
| 12 mois | ≤ 1 t | 165 299 | 118 965 | 7 950 | 7 500 | 7 000 | 20 505 | 1 000 | 1 000 | 2 379 |
| 12 mois | 1 – 3 t | 207 502 | 155 190 | 7 950 | 7 500 | 7 000 | 25 758 | 1 000 | 1 000 | 3 104 |
| 12 mois | 3 – 5 t | 247 723 | 189 715 | 7 950 | 7 500 | 7 000 | 30 764 | 1 000 | 1 000 | 3 794 |
| 12 mois | 5 – 8 t | 311 684 | 244 617 | 7 950 | 7 500 | 7 000 | 38 725 | 1 000 | 1 000 | 4 892 |
| 12 mois | 9 – 12 t | 330 683 | 260 925 | 7 950 | 7 500 | 7 000 | 41 089 | 1 000 | 1 000 | 5 219 |
| 12 mois | 12 – 15 t | 354 642 | 279 600 | 7 950 | 7 500 | 7 000 | 44 000 | 1 000 | 1 000 | 5 592 |
| 12 mois | > 15 t | 380 142 | 299 600 | 7 950 | 7 500 | 7 000 | 47 100 | 1 000 | 1 000 | 5 992 |

### Annexe D — Grille TPM

Durées présentes : **1, 3, 6, 12 mois** (pas de 2 mois). TP = **0** sur toute la grille.

| Durée | Tranche | Base | RC | DR | TP | Acc. | Taxes | CEDEAO | ASACI | FGA |
|-------|---------|------|----|----|----|------|-------|--------|-------|-----|
| 1 mois | ≤ 1 t | 18 608 | 13 350 | 795 | 0 | 1 000 | 2 196 | 1 000 | 1 000 | 267 |
| 1 mois | 1 – 3 t | 23 012 | 17 130 | 795 | 0 | 1 000 | 2 744 | 1 000 | 1 000 | 343 |
| 1 mois | 3 – 5 t | 28 103 | 21 500 | 795 | 0 | 1 000 | 3 378 | 1 000 | 1 000 | 430 |
| 1 mois | 5 – 8 t | 34 475 | 26 970 | 795 | 0 | 1 000 | 4 171 | 1 000 | 1 000 | 539 |
| 1 mois | 9 – 12 t | 42 596 | 33 940 | 795 | 0 | 1 000 | 5 182 | 1 000 | 1 000 | 679 |
| 1 mois | 12 – 15 t | 54 840 | 44 450 | 795 | 0 | 1 000 | 6 706 | 1 000 | 1 000 | 889 |
| 1 mois | ≥ 15 t | 60 024 | 48 900 | 795 | 0 | 1 000 | 7 351 | 1 000 | 1 000 | 978 |
| 3 mois | ≤ 1 t | 50 532 | 37 380 | 2 226 | 0 | 3 000 | 6 178 | 1 000 | 1 000 | 748 |
| 3 mois | 1 – 3 t | 62 862 | 47 964 | 2 226 | 0 | 3 000 | 7 713 | 1 000 | 1 000 | 959 |
| 3 mois | 3 – 5 t | 77 117 | 60 200 | 2 226 | 0 | 3 000 | 9 487 | 1 000 | 1 000 | 1 204 |
| 3 mois | 5 – 8 t | 94 960 | 75 516 | 2 226 | 0 | 3 000 | 11 708 | 1 000 | 1 000 | 1 510 |
| 3 mois | 9 – 12 t | 117 696 | 95 032 | 2 226 | 0 | 3 000 | 14 537 | 1 000 | 1 000 | 1 901 |
| 3 mois | 12 – 15 t | 151 979 | 124 460 | 2 226 | 0 | 3 000 | 18 804 | 1 000 | 1 000 | 2 489 |
| 3 mois | ≥ 15 t | 166 495 | 136 929 | 2 226 | 0 | 3 000 | 20 611 | 1 000 | 1 000 | 2 738 |
| 6 mois | ≤ 1 t | 93 979 | 70 755 | 4 214 | 0 | 5 000 | 11 595 | 1 000 | 1 000 | 1 415 |
| 6 mois | 1 – 3 t | 117 319 | 90 789 | 4 214 | 0 | 5 000 | 14 500 | 1 000 | 1 000 | 1 816 |
| 6 mois | 3 – 5 t | 144 302 | 113 950 | 4 214 | 0 | 5 000 | 17 859 | 1 000 | 1 000 | 2 279 |
| 6 mois | 5 – 8 t | 178 076 | 142 941 | 4 214 | 0 | 5 000 | 22 062 | 1 000 | 1 000 | 2 859 |
| 6 mois | 9 – 12 t | 221 113 | 179 882 | 4 214 | 0 | 5 000 | 27 419 | 1 000 | 1 000 | 3 598 |
| 6 mois | 12 – 15 t | 286 007 | 235 585 | 4 214 | 0 | 5 000 | 35 496 | 1 000 | 1 000 | 4 712 |
| 6 mois | ≥ 15 t | 313 483 | 259 170 | 4 214 | 0 | 5 000 | 38 916 | 1 000 | 1 000 | 5 183 |
| 12 mois | ≤ 1 t | 173 645 | 133 500 | 7 950 | 0 | 7 000 | 21 525 | 1 000 | 1 000 | 2 670 |
| 12 mois | 1 – 3 t | 217 682 | 171 300 | 7 950 | 0 | 7 000 | 27 006 | 1 000 | 1 000 | 3 426 |
| 12 mois | 3 – 5 t | 268 593 | 215 000 | 7 950 | 0 | 7 000 | 33 343 | 1 000 | 1 000 | 4 300 |
| 12 mois | 5 – 8 t | 332 318 | 269 700 | 7 950 | 0 | 7 000 | 41 274 | 1 000 | 1 000 | 5 394 |
| 12 mois | 9 – 12 t | 413 519 | 339 400 | 7 950 | 0 | 7 000 | 51 381 | 1 000 | 1 000 | 6 788 |
| 12 mois | 12 – 15 t | 535 960 | 444 500 | 795 | 0 | 7 000 | 66 620 | 1 000 | 1 000 | 8 890 |
| 12 mois | ≥ 15 t | 587 803 | 489 000 | 7 950 | 0 | 7 000 | 73 073 | 1 000 | 1 000 | 9 780 |

### Annexe E — Grille Deux-roues

Pas de durée en grille (tarif unique par cylindrée). CEDEAO / Prime ASACI = **0**.

| Cylindrée | Base | RC | DR | TP | Acc. | Taxes | CEDEAO | ASACI | FGA |
|-----------|------|----|----|----|------|-------|--------|-------|-----|
| < 50 cm³ | 15 349 | 11 642 | 1 060 | 0 | 500 | 1 914 | 0 | 0 | 233 |
| 51 – 99 cm³ | 20 769 | 15 294 | 1 060 | 0 | 500 | 2 589 | 0 | 0 | 326 |
| 100 – 175 cm³ | 29 567 | 23 846 | 1 060 | 0 | 500 | 3 684 | 0 | 0 | 477 |
| 176 – 350 cm³ | 37 503 | 30 658 | 1 060 | 0 | 500 | 4 673 | 0 | 0 | 613 |
| > 350 cm³ | 49 412 | 40 880 | 1 060 | 0 | 500 | 6 154 | 0 | 0 | 818 |

> **Anomalie seed connue (TPM)** : pour **12 mois / 12 – 15 t**, `defence_appeal_amount` = **795** au lieu de **7 950** (valeur du seeder telle quelle).

---

*Document généré à partir de l’implémentation Certy / kissappmetier. À garder synchronisé si les formules évoluent.*
