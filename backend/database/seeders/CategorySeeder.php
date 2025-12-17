<?php

namespace Database\Seeders;

use App\Core\Category\Enums\SelectionType;
use App\Core\Category\Models\CategoryGroup;
use App\Core\Category\Models\Category;
use App\Core\Tenant\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first tenant for seeding (in real app this would be handled differently)
        $tenant = Tenant::first();
        
        if (!$tenant) {
            $this->command->warn('No tenant found. Please run TenantSeeder first.');
            return;
        }

        $this->command->info("Seeding categories for tenant: {$tenant->name}");

        DB::transaction(function () use ($tenant) {
            $this->seedDocumentTypeGroup($tenant);
            $this->seedPriorityGroup($tenant);
            $this->seedDepartmentGroup($tenant);
            $this->seedPartnerTypeGroup($tenant);
        });

        $this->command->info('Category seeding completed successfully!');
    }

    private function seedDocumentTypeGroup(Tenant $tenant): void
    {
        $group = CategoryGroup::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dokumenttyp',
            'slug' => 'document_type',
            'description' => 'Klassifizierung der Dokumente nach ihrer Art und ihrem Zweck',
            'selection_type' => SelectionType::SINGLE,
            'is_required' => true,
            'ai_enabled' => true,
            'ai_prompt_context' => 'Klassifiziere das Dokument basierend auf seinem Inhalt und Zweck.',
            'ai_confidence_threshold' => 0.8,
            'display_order' => 0,
            'icon' => 'mdi-file-document',
            'color' => 'primary',
            'is_active' => true,
        ]);

        // Rechnung
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Rechnung',
            'slug' => 'invoice',
            'description' => 'Zahlungsaufforderungen von Lieferanten und Dienstleistern',
            'ai_positive_description' => 'Dokumente mit Rechnungsnummer, Betrag, Zahlungsziel und MwSt-Ausweis. Enthält Zahlungsaufforderungen von Lieferanten oder Dienstleistern mit konkreten Beträgen und Zahlungszielen.',
            'ai_negative_description' => 'Keine Angebote, Mahnungen, Gutschriften oder internen Kostennotizen. Keine Dokumente ohne konkreten Zahlungsauftrag.',
            'ai_keywords' => ['Rechnung', 'Invoice', 'Betrag', 'Zahlungsziel', 'MwSt', 'Steuer', 'Zahlung fällig', 'Rechnungsnummer'],
            'ai_examples' => ['Rechnung RE-2024-001 über €1.500', 'Invoice INV-240312 with payment terms', 'Dienstleistungsrechnung mit MwSt-Ausweis'],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 0,
            'icon' => 'mdi-receipt',
            'color' => 'success',
            'is_active' => true,
        ]);

        // Vertrag
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Vertrag',
            'slug' => 'contract',
            'description' => 'Rechtlich bindende Vereinbarungen zwischen Parteien',
            'ai_positive_description' => 'Rechtlich bindende Vereinbarungen zwischen Parteien mit Unterschriften, Laufzeiten und vertraglichen Bedingungen. Enthält Verpflichtungen und Rechte beider Parteien.',
            'ai_negative_description' => 'Keine Angebote, Protokolle, einfache Korrespondenz oder Absichtserklärungen ohne rechtliche Bindung.',
            'ai_keywords' => ['Vertrag', 'Agreement', 'Contract', 'Unterschrift', 'Laufzeit', 'Kündigung', 'Vereinbarung', 'Verpflichtung'],
            'ai_examples' => ['Mietvertrag mit 2 Jahren Laufzeit', 'Dienstleistungsvertrag mit monatlicher Kündigung', 'Kaufvertrag für Equipment'],
            'is_default' => false,
            'requires_approval' => true,
            'display_order' => 1,
            'icon' => 'mdi-file-sign',
            'color' => 'info',
            'is_active' => true,
        ]);

        // Angebot
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Angebot',
            'slug' => 'quote',
            'description' => 'Preisvorschläge ohne rechtliche Bindung',
            'ai_positive_description' => 'Preisvorschläge ohne Zahlungsverpflichtung, oft mit Gültigkeitsdauer und unverbindlichen Konditionen. Enthält Preise für Produkte oder Dienstleistungen.',
            'ai_negative_description' => 'Keine bereits akzeptierten Verträge, bestätigte Bestellungen oder Rechnungen. Keine bindenden Zusagen.',
            'ai_keywords' => ['Angebot', 'Quote', 'Kostenvoranschlag', 'Gültigkeit', 'unverbindlich', 'Preisvorschlag', 'Offerte'],
            'ai_examples' => ['Angebot A-2024-015 gültig bis 31.12.2024', 'Kostenvoranschlag für Website-Entwicklung', 'Unverbindliche Preisliste'],
            'is_default' => true,
            'requires_approval' => false,
            'display_order' => 2,
            'icon' => 'mdi-file-outline',
            'color' => 'warning',
            'is_active' => true,
        ]);
    }

    private function seedPriorityGroup(Tenant $tenant): void
    {
        $group = CategoryGroup::create([
            'tenant_id' => $tenant->id,
            'name' => 'Priorität',
            'slug' => 'priority',
            'description' => 'Bearbeitungspriorität basierend auf Dringlichkeit und Geschäftsauswirkung',
            'selection_type' => SelectionType::SINGLE,
            'is_required' => false,
            'ai_enabled' => true,
            'ai_prompt_context' => 'Bewerte die Priorität basierend auf Dringlichkeit, Fristen und Geschäftsauswirkung.',
            'ai_confidence_threshold' => 0.7,
            'display_order' => 1,
            'icon' => 'mdi-flag',
            'color' => 'orange',
            'is_active' => true,
        ]);

        // Kritisch
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Kritisch',
            'slug' => 'critical',
            'description' => 'Sofortige Bearbeitung erforderlich',
            'ai_positive_description' => 'Rechtliche Fristen, Zahlungsaufforderungen mit kurzen Zahlungszielen, Notfälle, Compliance-relevante Dokumente mit sofortigem Handlungsbedarf.',
            'ai_negative_description' => 'Routine-Dokumente, Informationsmaterial ohne Fristen, oder Dokumente ohne zeitkritische Aspekte.',
            'ai_keywords' => ['Frist', 'sofort', 'dringend', 'Mahnung', 'Deadline', 'kritisch', 'umgehend', 'Eilsache'],
            'ai_examples' => ['Mahnung mit 7 Tagen Zahlungsfrist', 'Gerichtliche Vorladung', 'Kritische Sicherheitswarnung'],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 0,
            'icon' => 'mdi-alert',
            'color' => 'error',
            'is_active' => true,
        ]);

        // Hoch
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Hoch',
            'slug' => 'high',
            'description' => 'Wichtige Geschäftsdokumente mit zeitnaher Bearbeitung',
            'ai_positive_description' => 'Wichtige Geschäftsdokumente mit zeitnaher Bearbeitung, Kundenprojekte, wichtige Verträge oder Dokumente mit mittelfristigen Fristen.',
            'ai_negative_description' => 'Routine-Dokumente, reine Informationen oder Dokumente ohne besondere Dringlichkeit.',
            'ai_keywords' => ['wichtig', 'zeitnah', 'Projekt', 'Kunde', 'Termin', 'Frist'],
            'ai_examples' => ['Kundenprojekt mit Deadline', 'Wichtiger Vertrag zur Prüfung', 'Projektdokumentation'],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 1,
            'icon' => 'mdi-chevron-up',
            'color' => 'warning',
            'is_active' => true,
        ]);

        // Normal
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Normal',
            'slug' => 'normal',
            'description' => 'Standard-Geschäftsdokumente ohne besonderen Zeitdruck',
            'ai_positive_description' => 'Standard-Geschäftsdokumente ohne besonderen Zeitdruck oder spezielle Dringlichkeit. Routine-Abläufe und allgemeine Geschäftskorrespondenz.',
            'ai_negative_description' => 'Dokumente mit Fristen, dringenden Anfragen oder kritischen Inhalten.',
            'ai_keywords' => ['Information', 'Routine', 'Standard', 'normal', 'allgemein'],
            'ai_examples' => ['Allgemeine Geschäftskorrespondenz', 'Informationsmaterial', 'Routine-Dokumentation'],
            'is_default' => true,
            'requires_approval' => false,
            'display_order' => 2,
            'icon' => 'mdi-circle',
            'color' => 'grey',
            'is_active' => true,
        ]);
    }

    private function seedDepartmentGroup(Tenant $tenant): void
    {
        $group = CategoryGroup::create([
            'tenant_id' => $tenant->id,
            'name' => 'Abteilung',
            'slug' => 'department',
            'description' => 'Zuständige Abteilung(en) für Dokumentenbearbeitung',
            'selection_type' => SelectionType::MULTI,
            'is_required' => false,
            'ai_enabled' => true,
            'ai_prompt_context' => 'Identifiziere die zuständigen Abteilungen basierend auf Dokumenteninhalt und -typ.',
            'ai_confidence_threshold' => 0.6,
            'display_order' => 2,
            'icon' => 'mdi-office-building',
            'color' => 'blue',
            'is_active' => true,
        ]);

        // Finance
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'Finance',
            'slug' => 'finance',
            'description' => 'Finanz- und Buchhaltungsabteilung',
            'ai_positive_description' => 'Rechnungen, Verträge mit Finanzauswirkung, Budgetdokumente, Steuerunterlagen, Zahlungen und alle finanziellen Aspekte.',
            'ai_negative_description' => 'Reine HR-Dokumente ohne Gehaltsbezug, technische Dokumentation oder Marketing-Material.',
            'ai_keywords' => ['€', '$', 'Betrag', 'Steuer', 'Budget', 'Kosten', 'Rechnung', 'Zahlung', 'Buchhaltung'],
            'ai_examples' => ['Rechnung über €2.500', 'Steuerberatung Unterlagen', 'Budgetplanung Q1'],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 0,
            'icon' => 'mdi-currency-eur',
            'color' => 'green',
            'is_active' => true,
        ]);

        // HR
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'HR',
            'slug' => 'hr',
            'description' => 'Personalabteilung',
            'ai_positive_description' => 'Personalunterlagen, Arbeitsverträge, Gehaltsabrechnungen, Bewerbungen, Zeugnisse und alle personalbezogenen Dokumente.',
            'ai_negative_description' => 'Technische Dokumentation, Rechnungen ohne Personalbezug oder allgemeine Geschäftskorrespondenz.',
            'ai_keywords' => ['Personal', 'Mitarbeiter', 'Gehalt', 'Arbeitsvertrag', 'Bewerbung', 'Zeugnis', 'Urlaub'],
            'ai_examples' => ['Arbeitsvertrag für neue Stelle', 'Bewerbungsunterlagen', 'Gehaltsabrechnung März'],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 1,
            'icon' => 'mdi-account-group',
            'color' => 'purple',
            'is_active' => true,
        ]);

        // IT
        Category::create([
            'category_group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'name' => 'IT',
            'slug' => 'it',
            'description' => 'IT-Abteilung',
            'ai_positive_description' => 'Software-Lizenzen, technische Dokumentation, IT-Verträge, Systemdokumentation und alle technologiebezogenen Inhalte.',
            'ai_negative_description' => 'Personalunterlagen, Finanzberichte ohne IT-Bezug oder allgemeine Geschäftskorrespondenz.',
            'ai_keywords' => ['Software', 'Lizenz', 'technisch', 'Server', 'IT', 'System', 'Hardware', 'Netzwerk'],
            'ai_examples' => ['Software-Lizenzvertrag', 'Server-Dokumentation', 'IT-Hardware Angebot'],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 2,
            'icon' => 'mdi-laptop',
            'color' => 'teal',
            'is_active' => true,
        ]);
    }

    private function seedPartnerTypeGroup(Tenant $tenant): void
    {
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
            'ai_positive_description' => 'Unternehmen, die Produkte, Materialien oder Waren liefern.',
            'ai_negative_description' => 'Keine Kunden oder Dienstleister.',
            'ai_keywords' => ['Lieferant', 'Supplier', 'Ware', 'Material', 'Produkt', 'Lieferung'],
            'ai_examples' => ['Materiallieferant', 'Produkthersteller', 'Großhändler'],
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
            'ai_positive_description' => 'Unternehmen oder Personen, die unsere Produkte oder Dienstleistungen kaufen.',
            'ai_negative_description' => 'Keine Lieferanten oder reinen Dienstleister.',
            'ai_keywords' => ['Kunde', 'Customer', 'Client', 'Käufer', 'Auftraggeber'],
            'ai_examples' => ['Geschäftskunde', 'Privatkunde', 'Stammkunde'],
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
            'ai_positive_description' => 'Unternehmen, die Dienstleistungen für uns erbringen.',
            'ai_negative_description' => 'Keine Produktlieferanten oder Kunden.',
            'ai_keywords' => ['Dienstleister', 'Service', 'Beratung', 'Wartung', 'Support'],
            'ai_examples' => ['IT-Dienstleister', 'Steuerberater', 'Wartungsfirma'],
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
            'ai_positive_description' => 'Strategische Geschäftspartner und Kooperationen.',
            'ai_negative_description' => 'Keine einfachen Kunden oder Lieferanten.',
            'ai_keywords' => ['Partner', 'Kooperation', 'Allianz', 'Joint Venture', 'strategisch'],
            'ai_examples' => ['Technologiepartner', 'Vertriebspartner', 'Strategische Allianz'],
            'is_default' => false,
            'requires_approval' => false,
            'display_order' => 3,
            'icon' => 'mdi-handshake',
            'color' => 'purple',
            'is_active' => true,
        ]);
    }
}
