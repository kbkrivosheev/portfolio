<?php

declare(strict_types=1);

namespace app\models\Okpd2\DTO;

final class Ktru
{
    public int $id;
    public string $name;
    public string $code;
    public ?Unit $unit;

    public function __construct(int $id, string $name, string $code, ?Unit $unit)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->unit = $unit;
    }
}
