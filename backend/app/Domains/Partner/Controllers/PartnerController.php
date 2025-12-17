<?php

namespace App\Domains\Partner\Controllers;

use App\Domains\Partner\Models\Partner;
use App\Domains\Partner\Requests\StorePartnerRequest;
use App\Domains\Partner\Requests\UpdatePartnerRequest;
use App\Domains\Partner\Services\PartnerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function __construct(
        private PartnerService $partnerService
    ) {}

    /**
     * Display a listing of partners.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 
            'status', 
            'category_id', 
            'tags',
            'sort_by',
            'sort_order'
        ]);
        
        $perPage = $request->input('per_page', 10);
        
        $partners = $this->partnerService->getPaginated($filters, $perPage);

        return response()->json($partners);
    }

    /**
     * Store a newly created partner.
     */
    public function store(StorePartnerRequest $request): JsonResponse
    {
        $partner = $this->partnerService->create($request->validated());

        return response()->json([
            'message' => 'Partner erfolgreich erstellt',
            'partner' => $partner
        ], 201);
    }

    /**
     * Display the specified partner.
     */
    public function show(Partner $partner): JsonResponse
    {
        $partner->load(['category', 'contacts', 'interactions.user']);

        return response()->json($partner);
    }

    /**
     * Update the specified partner.
     */
    public function update(UpdatePartnerRequest $request, Partner $partner): JsonResponse
    {
        $partner = $this->partnerService->update($partner, $request->validated());

        return response()->json([
            'message' => 'Partner erfolgreich aktualisiert',
            'partner' => $partner
        ]);
    }

    /**
     * Remove the specified partner.
     */
    public function destroy(Partner $partner): JsonResponse
    {
        // Check if user is admin
        if (!auth()->user()->hasSystemRole('global_admin') && 
            !auth()->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Nur Administratoren können Partner löschen'
            ], 403);
        }

        $partner->delete();

        return response()->json([
            'message' => 'Partner erfolgreich gelöscht'
        ]);
    }

    /**
     * Search partners.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $results = $this->partnerService->search(
            $request->input('query'),
            $request->input('limit', 10)
        );

        return response()->json($results);
    }

    /**
     * Get partner statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->partnerService->getStatistics();

        return response()->json($stats);
    }

    /**
     * Get partners needing attention.
     */
    public function needsAttention(Request $request): JsonResponse
    {
        $days = $request->input('days', 90);
        $partners = $this->partnerService->getPartnersNeedingAttention($days);

        return response()->json($partners);
    }
}