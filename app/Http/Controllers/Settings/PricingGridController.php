<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TpcPricingGrid;
use App\Models\TpmPricingGrid;
use App\Models\TwoWheelerPricingGrid;
use App\Models\VpPricingGrid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingGridController extends Controller
{
    /**
     * Types de grilles à afficher (configurable).
     */
    public static function enabledTypes(): array
    {
        $config = config('app.pricing_grid_types', ['VP', 'TPC', 'TPM', 'TWO_WHEELER']);

        return is_array($config) ? $config : ['VP', 'TPC', 'TPM', 'TWO_WHEELER'];
    }

    /**
     * Données des grilles (partagé dashboard + page dédiée).
     */
    public static function getGridsData(): array
    {
        $enabledTypes = self::enabledTypes();
        $grids = [];

        if (in_array('VP', $enabledTypes)) {
            $grids['VP'] = VpPricingGrid::with('energySource:id,code,name')
                ->orderBy('energy_source_id')->orderBy('duration')->orderBy('power_range')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'energy_source' => $r->energySource?->code ?? '—',
                    'energy_source_name' => $r->energySource?->name ?? '—',
                    'duration' => $r->duration,
                    'power_range' => $r->power_range,
                    'base_amount' => (int) $r->base_amount,
                    'rc_amount' => (int) $r->rc_amount,
                    'defence_appeal_amount' => (int) $r->defence_appeal_amount,
                    'person_transport_amount' => (int) $r->person_transport_amount,
                    'accessory_amount' => (int) $r->accessory_amount,
                    'taxes_amount' => (int) $r->taxes_amount,
                    'cedeao_amount' => (int) $r->cedeao_amount,
                    'cp_amount' => (int) ($r->cp_amount ?? 0),
                    'fga_amount' => (int) $r->fga_amount,
                    'is_active' => (bool) $r->is_active,
                ])->values()->all();
        }
        if (in_array('TPC', $enabledTypes)) {
            $grids['TPC'] = TpcPricingGrid::orderBy('duration')->orderBy('payload_range')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id, 'duration' => $r->duration, 'payload_range' => $r->payload_range,
                    'base_amount' => (int) $r->base_amount, 'rc_amount' => (int) $r->rc_amount,
                    'defence_appeal_amount' => (int) $r->defence_appeal_amount, 'person_transport_amount' => (int) $r->person_transport_amount,
                    'accessory_amount' => (int) $r->accessory_amount, 'taxes_amount' => (int) $r->taxes_amount,
                    'cedeao_amount' => (int) $r->cedeao_amount, 'cp_amount' => (int) ($r->cp_amount ?? 0), 'fga_amount' => (int) $r->fga_amount,
                    'is_active' => (bool) $r->is_active,
                ])->values()->all();
        }
        if (in_array('TPM', $enabledTypes)) {
            $grids['TPM'] = TpmPricingGrid::orderBy('duration')->orderBy('payload_range')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id, 'duration' => $r->duration, 'payload_range' => $r->payload_range,
                    'base_amount' => (int) $r->base_amount, 'rc_amount' => (int) $r->rc_amount,
                    'defence_appeal_amount' => (int) $r->defence_appeal_amount, 'person_transport_amount' => (int) $r->person_transport_amount,
                    'accessory_amount' => (int) $r->accessory_amount, 'taxes_amount' => (int) $r->taxes_amount,
                    'cedeao_amount' => (int) $r->cedeao_amount, 'cp_amount' => (int) ($r->cp_amount ?? 0), 'fga_amount' => (int) $r->fga_amount,
                    'is_active' => (bool) $r->is_active,
                ])->values()->all();
        }
        if (in_array('TWO_WHEELER', $enabledTypes)) {
            $grids['TWO_WHEELER'] = TwoWheelerPricingGrid::orderBy('power_range')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id, 'power_range' => $r->power_range,
                    'base_amount' => (int) $r->base_amount, 'rc_amount' => (int) $r->rc_amount,
                    'defence_appeal_amount' => (int) $r->defence_appeal_amount, 'person_transport_amount' => (int) $r->person_transport_amount,
                    'accessory_amount' => (int) $r->accessory_amount, 'taxes_amount' => (int) $r->taxes_amount,
                    'cedeao_amount' => (int) $r->cedeao_amount, 'cp_amount' => (int) ($r->cp_amount ?? 0), 'fga_amount' => (int) $r->fga_amount,
                    'is_active' => (bool) $r->is_active,
                ])->values()->all();
        }

        return $grids;
    }

    /**
     * Données des grilles pour le dashboard (root).
     */
    public function index(Request $request): Response|JsonResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isRoot() && ! $user->isOfficeAdmin())) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return redirect()->route('dashboard');
        }

        $enabledTypes = self::enabledTypes();
        $grids = self::getGridsData();

        if ($request->wantsJson()) {
            return response()->json(['grids' => $grids, 'enabledTypes' => $enabledTypes]);
        }

        return Inertia::render('Settings/PricingGrids/Index', [
            'grids' => $grids,
            'enabledTypes' => $enabledTypes,
        ]);
    }

    /**
     * Mise à jour d'une ligne de grille.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isRoot() && ! $user->isOfficeAdmin())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:VP,TPC,TPM,TWO_WHEELER'],
            'id' => ['required', 'integer', 'min:1'],
            'base_amount' => ['nullable', 'integer', 'min:0'],
            'rc_amount' => ['nullable', 'integer', 'min:0'],
            'defence_appeal_amount' => ['nullable', 'integer', 'min:0'],
            'person_transport_amount' => ['nullable', 'integer', 'min:0'],
            'accessory_amount' => ['nullable', 'integer', 'min:0'],
            'taxes_amount' => ['nullable', 'integer', 'min:0'],
            'cedeao_amount' => ['nullable', 'integer', 'min:0'],
            'cp_amount' => ['nullable', 'integer', 'min:0'],
            'fga_amount' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $type = $validated['type'];
        $id = (int) $validated['id'];

        $amountFields = [
            'base_amount', 'rc_amount', 'defence_appeal_amount', 'person_transport_amount',
            'accessory_amount', 'taxes_amount', 'cedeao_amount', 'cp_amount', 'fga_amount',
        ];

        $data = [];
        foreach ($amountFields as $f) {
            if (array_key_exists($f, $validated) && $validated[$f] !== null) {
                $data[$f] = (int) $validated[$f];
            }
        }
        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $data['is_active'] = (bool) $validated['is_active'];
        }

        if (empty($data)) {
            return response()->json(['error' => 'Aucune donnée à mettre à jour'], 422);
        }

        $model = match ($type) {
            'VP' => VpPricingGrid::find($id),
            'TPC' => TpcPricingGrid::find($id),
            'TPM' => TpmPricingGrid::find($id),
            'TWO_WHEELER' => TwoWheelerPricingGrid::find($id),
        };

        if (! $model) {
            return response()->json(['error' => 'Ligne introuvable'], 404);
        }

        $model->update($data);

        return response()->json(['success' => true, 'row' => $model->fresh()]);
    }
}
