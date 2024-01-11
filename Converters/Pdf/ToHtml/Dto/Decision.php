<?php

declare(strict_types=1);

namespace app\Features\Converters\Pdf\ToHtml\Dto;

class Decision
{
    public string $html;
    public string $style;
    public int $pdfImage;

    public function __construct(string $html, string $style, int $pdfImage)
    {
        $this->html = $html;
        $this->style = $style;
        $this->pdfImage = $pdfImage;
    }
}
