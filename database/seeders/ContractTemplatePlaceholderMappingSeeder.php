<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractTemplate;
use App\Services\TemplateUploadService;
use Illuminate\Support\Facades\Storage;

class ContractTemplatePlaceholderMappingSeeder extends Seeder
{
    /**
     * Seed placeholder mappings cho các templates DOCX_MERGE hiện có
     */
    public function run(): void
    {
        $templates = ContractTemplate::where('engine', 'DOCX_MERGE')
            ->whereNotNull('body_path')
            ->get();

        foreach ($templates as $template) {
            $docxPath = Storage::disk('public')->path($template->body_path);

            if (!file_exists($docxPath)) {
                $this->command->warn("DOCX file not found for template: {$template->name}");
                continue;
            }

            // Xóa mappings cũ nếu có
            $template->placeholderMappings()->delete();

            // Tạo mappings mới
            $result = TemplateUploadService::createPlaceholderMappings($template, $docxPath);

            $this->command->info("Template: {$template->name}");
            $this->command->info("  - Detected: {$result['detected']} placeholders");
            $this->command->info("  - Auto-mapped: {$result['mapped']}");
            $this->command->info("  - Needs config: {$result['unmapped']}");
        }

        $this->command->info("\n✅ Placeholder mappings seeded successfully!");
    }
}
