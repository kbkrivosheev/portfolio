<?php

declare(strict_types=1);

namespace app\models\Okpd2\Command\Import\Excel\Dto;

use app\models\AbstractModelLoader;

final class Excel extends AbstractModelLoader
{
    public ?string $name = null;
    public ?string $okpd2 = null;
    public ?string $ktru = null;

    private const NAME_INDEX = 0;
    private const OKPD2_INDEX = 1;
    private const KTRU_INDEX = 2;

    public function rules(): array
    {
        return [
            [['name', 'okpd2', 'ktru'], 'string'],
        ];
    }

    protected function matchingRules(): array
    {
        return [
            self::NAME_INDEX => 'name',
            self::OKPD2_INDEX => 'okpd2',
            self::KTRU_INDEX => 'ktru',
        ];
    }
}
