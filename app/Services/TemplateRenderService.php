<?php

namespace App\Services;

use App\Models\ContractTemplate;
use Illuminate\Support\Str;
use Liquid\Template as LiquidTemplate;
use Liquid\Liquid;
use Liquid\Exception\ParseException;

class TemplateRenderService
{
    public function __construct()
    {
        // Tùy chỉnh Liquid nếu cần
        Liquid::set('INCLUDE_ALLOW_EXT', false);
    }

    /**
     * Render HTML từ ContractTemplate và $data.
     * - LIQUID: render từ $template->content
     * - BLADE : render blade_view (fallback legacy)
     */
    public function renderContractTemplate(ContractTemplate $template, array $data): string
    {
        if ($template->isLiquid()) {
            return $this->renderLiquid($template->content ?? '', $data);
        }

        // Fallback Blade cho legacy (không chỉnh sửa ở UI)
        return view($template->blade_view, $data)->render();
    }

    public function renderLiquid(string $content, array $data): string
    {
        // Cấm một số chuỗi nguy hiểm (phòng trừ)
        $forbidden = ['{% include', '{% render', '{% layout', '{% assign', '{% capture']; // vẫn cho assign/capture? -> tùy chính sách
        foreach ($forbidden as $bad) {
            if (Str::contains($content, $bad)) {
                throw new \RuntimeException("Template có thẻ bị chặn: $bad");
            }
        }

        // Thêm filter mặc định
        $data['filters'] = [
            'date_vn' => function ($val, $fmt = 'd/m/Y') {
                try {
                    if (empty($val)) return '';
                    $dt = $val instanceof \DateTimeInterface ? $val : new \DateTime($val);
                    return $dt->format($fmt);
                } catch (\Throwable $e) {
                    return $val;
                }
            },
            'currency_vnd' => function ($val) {
                if ($val === null || $val === '') return '';
                return number_format((float)$val, 0, ',', '.') . ' VND';
            },
            'number' => function ($val, $dec = 0, $decPoint = ',', $thousandSep = '.') {
                if ($val === null || $val === '') return '';
                return number_format((float)$val, (int)$dec, $decPoint, $thousandSep);
            },
            'upper' => fn($val) => mb_strtoupper((string)$val, 'UTF-8'),
            'lower' => fn($val) => mb_strtolower((string)$val, 'UTF-8'),
            'json'  => fn($val) => is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val,
        ];

        // Liquid render
        $tpl = new LiquidTemplate();
        try {
            $tpl->parse($content);
        } catch (ParseException $e) {
            throw new \RuntimeException('Template không hợp lệ: '.$e->getMessage());
        }

        // Merge helpers level-1 (Liquid không hỗ trợ filter PHP như Blade; ta đẩy vào data)
        $safe = $data;
        return $tpl->render($safe);
    }
}
