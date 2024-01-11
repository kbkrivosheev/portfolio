<?php

declare(strict_types=1);

namespace app\models\Okpd2\DTO;

use app\models\Okpd2\Forms\TagForm;

final class Okpd2
{
    public int $id;
    public string $name;
    public string $code;
    public ?string $okpd2 = null;
    public ?string $ktru = null;

    /**
     * @var TagForm[]
     */
    public array $contractLinks = [];

    /**
     * @var TagForm[]
     */
    public array $nationalRegimeTags = [];

    /**
     * @var TagForm[]
     */
    public array $preferenceTags = [];

    /**
     * @var TagForm[]
     */
    public array $otherTags = [];

    public function __construct(
        int    $id,
        string $name,
        string $code,
        array  $contractLinks = []
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->contractLinks = $contractLinks;
    }

    public function addNationalRegimeTag(TagForm $tagForm): void
    {
        $this->nationalRegimeTags[] = $tagForm;
    }

    public function addPreferenceTag(TagForm $tagForm): void
    {
        $this->preferenceTags[] = $tagForm;
    }

    public function addOtherTag(TagForm $tagForm): void
    {
        $this->otherTags[] = $tagForm;
    }


    public function addOkpd2Ktru(string $okpd2, ?string $ktru = null): void
    {
        $this->okpd2 = $okpd2;
        $this->ktru = $ktru;
    }
}
