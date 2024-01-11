<?php

namespace app\models\sources\Licard\Get\Stats;

use app\models\SourceSaver;


class Handler
{

    public function handle($data, $sourceId, $sourceType): int
    {
        $stats = [];
        $date = new \DateTimeImmutable();
        foreach ($data as $item) {
            if (!empty($requestCategory = $item['requestCategory'])) {
                $action = in_array($requestCategory, ['J', 'R']) ? 'Возврат на карту' : 'Обслуживание';
            }
            $priceAzs = isset($item['stelaPrice']) ? trim($item['stelaPrice']) : '';
            $sum = isset($item['stelaAmount']) ? trim($item['stelaAmount']) : '';
            $qty = isset($item['quantity']) ? trim($item['quantity']) : '';
            if (!empty($transDate = $item['transDate'])) {
                $createdDate = $date::createFromFormat('Y-m-d\TH:i:s', $transDate);
                if (!$createdDate) {
                    $createdDate = $date::createFromFormat('Y-m-d\TH:i', $transDate);
                }
                if (!$createdDate) {
                    continue;
                }
                $created = $createdDate->format('Y-m-d H:i:s');
            }
            $stats[] = [
                'transaction_id' => isset($item['transId']) ? md5($item['transId']) : '',
                'card' => isset($item['transId']) ? trim($item['cardNumberOut']): '',
                'owner' => isset($item['vehicleNumberOut']) ? trim($item['vehicleNumberOut']): '',
                'store' => isset($item['partnerName']) ? trim($item['partnerName']): '',
                'store_address' => isset($item['streetAddress']) ? trim($item['streetAddress']): '',
                'created' => $created ?? '',
                'action' => $action ?? '',
                'service' => isset($item['goodsName']) ? trim($item['goodsName']): '',
                'qty' => $qty,
                'price' => $priceAzs,
                'sum' => $sum,
                'priceAzs' => $priceAzs,
            ];
        }
        $saver = new SourceSaver();
        $saver->source = $sourceId;
        $saver->year = $date->format('Y') ?: date('Y');
        $saver->month = $date->format('m') ?: date('n');
        $saver->type = $sourceType;
        $saver->saveStats($stats);
        return count($stats);
    }
}