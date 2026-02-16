# Schéma de la base de données – Certy (assurance auto)

## Vue d’ensemble

- **Users** : authentification + `external_token` (SSO externe).
- **Organizations** : cabinets courtiers (existant).
- **Clients** : assurés, rattachés à une organisation.
- **Vehicles** : véhicules des clients, avec références (marque, modèle, zone, énergie, etc.).
- **Contracts** : contrats d’assurance (client + véhicule + compagnie).
- **Companies** : compagnies d’assurance.
- **Bordereaux** : bordereaux de production (organisation + compagnie).
- **Tables de référence** : marques, modèles, zones de circulation, énergies, usages, types, catégories, genres véhicule, professions, couleurs.

## Tables

### Existantes (inchangées)

| Table            | Rôle                          |
|------------------|--------------------------------|
| users            | Auth + external_token          |
| organizations    | Cabinets courtiers             |
| organization_user| Liaison user ↔ organisation   |
| plans            | Offres d’abonnement            |
| subscriptions    | Abonnement par organisation    |
| organization_invitations | Invitations équipe   |

### Nouvelles – Références

| Table              | Champs principaux   | Rôle                    |
|--------------------|---------------------|-------------------------|
| vehicle_brands     | name                | Marques véhicules       |
| vehicle_models     | vehicle_brand_id, name | Modèles (par marque) |
| circulation_zones  | name, code          | Zones (ex. Abidjan, Intérieur) |
| energy_sources     | name, code          | Essence, Diesel, GPL, Électrique |
| vehicle_usages     | name, code          | Particulier, Commercial |
| vehicle_types      | name, code          | VP, 2 roues             |
| vehicle_categories | name, code          | Catégorie véhicule      |
| vehicle_genders    | name, code          | Genre (véhicule)        |
| professions        | name                | Profession de l’assuré  |
| colors             | name, code          | Couleur véhicule        |

### Nouvelles – Métier

| Table             | Champs principaux | Rôle |
|-------------------|-------------------|------|
| companies         | name, code, logo_path, is_active | Compagnies d’assurance |
| clients           | organization_id, owner_id, full_name, email, phone, address, postal_address, profession_id, type_assure (TAPP/TAPM) | Assurés du cabinet |
| vehicles          | client_id, vehicle_brand_id, vehicle_model_id, registration_number, circulation_zone_id, energy_source_id, vehicle_usage_id, vehicle_type_id, vehicle_category_id, vehicle_gender_id, color_id, fiscal_power, year_of_first_registration | Véhicules des clients |
| contracts         | organization_id, client_id, vehicle_id, company_id, contract_type (VP, TPC, TPM, TWO_WHEELER), status (draft, validated, active, cancelled, expired), start_date, end_date, metadata | Contrats d’assurance |
| contract_histories| contract_id, event, payload, user_id | Historique des contrats |
| bordereaux        | organization_id, company_id, reference, status (draft, submitted, approved, rejected, paid), period_start, period_end, total_amount, submitted_at, approved_at, paid_at | Bordereaux de production |

## Relations principales

- **Organization** → hasMany Client, Contract, Bordereau  
- **Client** → belongsTo Organization, User (owner), Profession ; hasMany Vehicle, Contract  
- **Vehicle** → belongsTo Client, VehicleBrand, VehicleModel, CirculationZone, EnergySource, VehicleUsage, VehicleType, VehicleCategory, VehicleGender, Color ; hasMany Contract  
- **Contract** → belongsTo Organization, Client, Vehicle, Company ; hasMany ContractHistory  
- **Company** → hasMany Contract, Bordereau  
- **VehicleBrand** → hasMany VehicleModel  
- **VehicleModel** → belongsTo VehicleBrand ; hasMany Vehicle  

## Lancer les migrations et les seeders

```bash
ddev php artisan migrate
ddev php artisan db:seed --class=ReferenceDataSeeder
```

Les marques et modèles véhicules peuvent être ajoutés via un seeder dédié ou importés depuis l’ASACI plus tard.
