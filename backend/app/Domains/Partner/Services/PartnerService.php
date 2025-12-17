<?php

namespace App\Domains\Partner\Services;

use App\Domains\Partner\Models\Partner;
use App\Domains\Partner\Models\PartnerContact;
use App\Domains\Partner\Models\PartnerInteraction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    /**
     * Get paginated partners with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 10)
    {
        $query = Partner::with(['category', 'contacts' => function ($q) {
            $q->where('is_primary', true);
        }]);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Create a new partner.
     */
    public function create(array $data): Partner
    {
        return DB::transaction(function () use ($data) {
            // Create partner
            $partner = Partner::create($data);

            // Create primary contact if provided
            if (!empty($data['primary_contact'])) {
                $this->createContact($partner, array_merge(
                    $data['primary_contact'],
                    ['is_primary' => true]
                ));
            }

            return $partner->fresh(['category', 'contacts']);
        });
    }

    /**
     * Update a partner.
     */
    public function update(Partner $partner, array $data): Partner
    {
        return DB::transaction(function () use ($partner, $data) {
            $partner->update($data);

            // Update primary contact if provided
            if (!empty($data['primary_contact'])) {
                $primaryContact = $partner->primaryContact();
                
                if ($primaryContact) {
                    $primaryContact->update($data['primary_contact']);
                } else {
                    $this->createContact($partner, array_merge(
                        $data['primary_contact'],
                        ['is_primary' => true]
                    ));
                }
            }

            return $partner->fresh(['category', 'contacts']);
        });
    }

    /**
     * Create a contact for a partner.
     */
    public function createContact(Partner $partner, array $data): PartnerContact
    {
        $contact = $partner->contacts()->create($data);

        if ($data['is_primary'] ?? false) {
            $contact->makePrimary();
        }

        return $contact;
    }

    /**
     * Create an interaction for a partner.
     */
    public function createInteraction(Partner $partner, array $data): PartnerInteraction
    {
        $data['user_id'] = $data['user_id'] ?? auth()->id();
        $data['interaction_date'] = $data['interaction_date'] ?? now();

        return $partner->interactions()->create($data);
    }

    /**
     * Get partner statistics.
     */
    public function getStatistics(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'total' => Partner::where('tenant_id', $tenantId)->count(),
            'active' => Partner::where('tenant_id', $tenantId)->active()->count(),
            'by_category' => Partner::where('tenant_id', $tenantId)
                ->select('category_id', DB::raw('count(*) as count'))
                ->with('category:id,name')
                ->groupBy('category_id')
                ->get()
                ->map(fn($item) => [
                    'category' => $item->category->name ?? 'Ohne Kategorie',
                    'count' => $item->count
                ]),
            'recent_interactions' => PartnerInteraction::whereHas('partner', function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                })
                ->where('interaction_date', '>=', now()->subDays(30))
                ->count(),
            'overdue_followups' => PartnerInteraction::whereHas('partner', function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                })
                ->overdueFollowUps()
                ->count(),
        ];
    }

    /**
     * Search partners by various criteria.
     */
    public function search(string $query, int $limit = 10): Collection
    {
        return Partner::with(['category', 'primaryContact'])
            ->search($query)
            ->limit($limit)
            ->get();
    }

    /**
     * Get partners that need attention (no interaction in X days).
     */
    public function getPartnersNeedingAttention(int $days = 90): Collection
    {
        return Partner::with(['category', 'interactions' => function ($q) {
                $q->latest('interaction_date')->limit(1);
            }])
            ->active()
            ->whereDoesntHave('interactions', function ($q) use ($days) {
                $q->where('interaction_date', '>=', now()->subDays($days));
            })
            ->get();
    }
}