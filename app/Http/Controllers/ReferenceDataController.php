<?php

namespace App\Http\Controllers;

use App\Models\BusinessSector;
use App\Models\EmployeeCount;
use Illuminate\Http\Request;

class ReferenceDataController extends Controller
{
    /**
     * Liste des secteurs d'activité disponibles
     */
    public function businessSectors()
    {
        try {
            $sectors = BusinessSector::active()
                ->ordered()
                ->get(['id', 'code', 'name', 'description', 'color', 'icon']);

            return response()->json([
                'success' => true,
                'data' => $sectors,
                'message' => 'Secteurs d\'activité récupérés avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des secteurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des tranches d'employés disponibles
     */
    public function employeeCounts()
    {
        try {
            $counts = EmployeeCount::active()
                ->ordered()
                ->get(['id', 'code', 'name', 'display_label', 'min_count', 'max_count', 'color', 'icon']);

            return response()->json([
                'success' => true,
                'data' => $counts,
                'message' => 'Tranches d\'employés récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tranches d\'employés',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toutes les données de référence en une seule requête
     */
    public function all()
    {
        try {
            $businessSectors = BusinessSector::active()
                ->ordered()
                ->get(['id', 'code', 'name', 'description', 'color', 'icon']);

            $employeeCounts = EmployeeCount::active()
                ->ordered()
                ->get(['id', 'code', 'name', 'display_label', 'min_count', 'max_count', 'color', 'icon']);

            return response()->json([
                'success' => true,
                'data' => [
                    'business_sectors' => $businessSectors,
                    'employee_counts' => $employeeCounts
                ],
                'message' => 'Données de référence récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données de référence',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}