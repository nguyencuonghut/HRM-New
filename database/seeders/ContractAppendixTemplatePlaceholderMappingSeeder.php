<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractAppendixTemplate;
use App\Services\TemplateUploadService;
use Illuminate\Support\Facades\Storage;

class ContractAppendixTemplatePlaceholderMappingSeeder extends Seeder
{
    /**
     * Seed placeholder mappings cho các appendix templates DOCX_MERGE hiện có
     */
    public function run(): void
    {
        $templates = ContractAppendixTemplate::where('engine', 'DOCX_MERGE')
            ->whereNotNull('body_path')
            ->get();

        if ($templates->isEmpty()) {
            $this->command->info('No appendix templates found. Run ContractAppendixTemplateSeeder first.');
            return;
        }

        foreach ($templates as $template) {
            $docxPath = Storage::disk('public')->path($template->body_path);

            if (!file_exists($docxPath)) {
                $this->command->warn("⚠ DOCX file not found for template: {$template->name}");
                $this->command->warn("  Expected path: {$docxPath}");
                $this->command->info("  → Skipping placeholder mapping for this template");
                continue;
            }

            // Xóa mappings cũ nếu có
            $template->placeholderMappings()->delete();

            // Tạo mappings mới - reuse service với appendix model
            $result = TemplateUploadService::createAppendixPlaceholderMappings($template, $docxPath);

            $this->command->info("✓ Template: {$template->name} ({$template->code})");
            $this->command->info("  - Detected: {$result['detected']} placeholders");
            $this->command->info("  - Auto-mapped: {$result['mapped']}");
            $this->command->info("  - Needs config: {$result['unmapped']}");
        }

        $this->command->info("\n✅ Appendix template placeholder mappings seeded successfully!");
    }
}
