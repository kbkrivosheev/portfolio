<?php

namespace app\models\Okpd2\Command\SplitByLot;

use app\models\Okpd as OkpdModel;
use app\models\Okpd2\Forms\TagForm;

final class Handler
{
    /**
     * @param Form[] $forms
     */
    public function handle(array $forms): array
    {
        $grouped = $this->groupByTagIds($forms);
        return $this->getLotIndexes($grouped);
    }

    /**
     * @param Form[] $forms
     * @return Form[][]
     */
    private function groupByTagIds(array $forms): array
    {
        $grouped = [];

        foreach ($forms as $form) {
            $preferencesTagIds = $this->getActiveTagIds($form->getPreferenceTags());
            $nationalRegimeTagIds = $this->getActiveTagIds($form->getNationalRegimeTags());

            if (in_array(OkpdModel::TAG_P1, $nationalRegimeTagIds)) {
                $nationalRegimeTagIds = [OkpdModel::TAG_P1];
            }

            $key = implode('+', [...$nationalRegimeTagIds, ...$preferencesTagIds]);
            $grouped[$key][] = $form;
        }

        return $grouped;
    }

    /**
     * @param TagForm[] $tags
     * @return string[]
     */
    private function getActiveTagIds(array $tags): array
    {
        $result = [];
        foreach ($tags as $tag) {
            if ($tag->isActive) {
                $result[] = $tag->id;
            }
        }
        sort($result);
        return $result;
    }

    /**
     * @param Form[][] $groupedForms
     * @return array
     */
    private function getLotIndexes(array $groupedForms): array
    {
        $result = [];
        foreach (array_values($groupedForms) as $index => $forms) {
            foreach ($forms as $form) {
                $result[$form->id] = $index + 1;
            }
        }
        return $result;
    }
}