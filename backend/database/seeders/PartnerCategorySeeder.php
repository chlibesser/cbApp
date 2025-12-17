<?php

namespace Database\Seeders;

use App\Core\Category\Enums\SelectionType;
use App\Core\Category\Models\CategoryGroup;
use App\Core\Category\Models\Category;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PartnerCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();
        
        if (!$tenant) {
            $this->command->warn('No tenant found. Please run TenantSeeder first.');
            return;
        }

        $this->command->info("Seeding partner categories for tenant: {$tenant->name}");

        DB::transaction(function () use ($tenant) {
            $this->seedPartnerTypeGroup($tenant);
        });

        $this->command->info('Partner category seeding completed successfully!');
    }

    private function seedPartnerTypeGroup(Tenant $tenant): void
    {
        // Check if partner type group already exists
        $existingGroup = CategoryGroup::where('tenant_id', $tenant->id)
            ->where('slug', 'partner_type')
            ->first();

        if ($existingGroup) {
            $this->command->info('Partner type category group already exists, skipping.');
            return;
        }

        $group = CategoryGroup::create([
            'tenant_id' => $tenant->id,
            'name' => 'Partnertyp',
            'slug' => 'partner_type',
            'description' => 'Klassifizierung der Partner nach ihrer Beziehungsart',
            'selection_type' => SelectionType::SINGLE,
            'is_required' => true,
            'ai_enabled' => false,
            'display_order' => 3,
            'icon' => 'mdi-handshake',
            'color' => 'indigo',
            'is_active' => true,
        ]);

        // Lieferanten
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Lieferanten',
            'slug' => 'suppliers',
            'description' => 'Unternehmen, die Produkte oder Materialien liefern',
            'ai_positive_description' => '',
            'ai_negative_description' => '',
            'ai_keywords' => [],
            'ai_examples' => [],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 0,
            'icon' => 'mdi-truck-delivery',
            'color' => 'blue',
            'is_active' => true,
        ]);

        // Kunden
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Kunden',
            'slug' => 'customers',
            'description' => 'Unternehmen oder Personen, die unsere Produkte/Dienstleistungen kaufen',
            'ai_positive_description' => '',
            'ai_negative_description' => '',
            'ai_keywords' => [],
            'ai_examples' => [],
            'is_default' => true,
            'requires_approval' => false,
            'display_order' => 1,
            'icon' => 'mdi-account-heart',
            'color' => 'green',
            'is_active' => true,
        ]);

        // Dienstleister
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Dienstleister',
            'slug' => 'service_providers',
            'description' => 'Unternehmen, die Dienstleistungen für uns erbringen',
            'ai_positive_description' => '',
            'ai_negative_description' => '',
            'ai_keywords' => [],
            'ai_examples' => [],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 2,
            'icon' => 'mdi-tools',
            'color' => 'orange',
            'is_active' => true,
        ]);

        // Partner
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Partner',
            'slug' => 'business_partners',
            'description' => 'Strategische Geschäftspartner und Kooperationen',
            'ai_positive_description' => '',
            'ai_negative_description' => '',
            'ai_keywords' => [],
            'ai_examples' => [],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 3,
            'icon' => 'mdi-handshake',
            'color' => 'purple',
            'is_active' => true,
        ]);
    }
}