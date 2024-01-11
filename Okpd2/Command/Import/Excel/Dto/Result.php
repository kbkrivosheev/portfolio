<?php

declare(strict_types=1);

namespace app\models\Okpd2\Command\Import\Excel\Dto;

use app\models\Okpd2\DTO\Ktru;
use app\models\Okpd2\DTO\Okpd2;

final class Result
{
    public ?string $name = null;
    public ?Okpd2 $okpd2 = null;
    public ?Ktru $ktru = null;

    public function __construct(?string $name, ?Okpd2 $okpd2, ?Ktru $ktru)
    {
        $this->name = $name;
        $this->okpd2 = $okpd2;
        $this->ktru = $ktru;
    }
}
