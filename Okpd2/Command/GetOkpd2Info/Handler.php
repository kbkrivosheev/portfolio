<?php

declare(strict_types=1);

namespace app\models\Okpd2\Command\GetOkpd2Info;

use app\models\Okpd2\DTO\Okpd2;
use app\models\Okpd2\DTO\Okpd2Builder;

final class Handler
{
    private Okpd2Builder $okpd2Builder;

    public function __construct(Okpd2Builder $okpd2Builder)
    {
        $this->okpd2Builder = $okpd2Builder;
    }

    /**
     * @param Form $form
     * @return Okpd2[]
     */
    public function handle(Form $form): array
    {
        $results = [];
        foreach ($form->getCodes() as $code) {
            $results[] = $this->okpd2Builder->buildFromCode($code->okpd2, $code->ktru);
        }
        return $results;
    }


}
