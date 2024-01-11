<?php

declare(strict_types=1);

namespace app\Features\Converters\Pdf\ToHtml;

final class Command
{
    /**
     * Количество файлов, которые обработаются за 1 запрос
     */
    private const FILES_COUNT_LIMIT = 10;

    public int $limit;

    public function __construct()
    {
        $this->limit = self::FILES_COUNT_LIMIT;
    }
}
