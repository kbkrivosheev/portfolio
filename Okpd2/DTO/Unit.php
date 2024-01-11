<?php

declare(strict_types=1);

namespace app\models\Okpd2\DTO;

final class Unit
{
    public int $id;
    public string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}