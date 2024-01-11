<?php

namespace app\models\sources\Licard\Get\FuelTypes;

use app\models\SourceSaver;

class Handler
{
    public function handle($data, $sourceType): int
    {
        foreach ($data as $item) {
            $key = $item['service'] ?? '';
            $name = $services[$key] = $item['service'] ?? '';
            $units[$key] = isset($item['unit']) && $item['unit'] === 'л' ? 'Литры' : 'Рубли';
            $fuel_types[] = [
                'short_title' => $name,
                'title' => $name,
            ];
        }
        $saver = new SourceSaver();
        $saver->type = $saver->source = $sourceType;
        $saver->saveServiceGoodsСat($services);
        $saver->saveUnits($units);
        $saver->saveFuelTypes($fuel_types);
        return count($fuel_types);
    }
}