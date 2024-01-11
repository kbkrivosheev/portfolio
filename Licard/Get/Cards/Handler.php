<?php

namespace app\models\sources\Licard\Get\Cards;

use app\models\SourceSaver;

class Handler
{

    public function handle(array $data, int $sourceId, array $drivers): int
    {
        $cards = [];
        foreach ($data as $card) {
            $owner = '';
            $cardNumber = isset($card['cardNumber']) ? trim($card['cardNumber']) : '';
            if (array_key_exists($cardNumber, $drivers)) {
                $owner = $drivers[$cardNumber];
            }
            $cards[] = [
                'number' => $cardNumber,
                'owner' => $owner,
                'status' => isset($card['trackingStatusName']) && trim($card['trackingStatusName']) == 'Активна' ? 'В работе' : 'Заблокирована',
            ];
        }
        $saver = new SourceSaver();
        $saver->source = $sourceId;
        $saver->saveCards($cards);
        return count($cards);
    }


}