<?php

namespace app\models\sources\Licard\Get;


use app\Helpers\DateTimeHelper;
use app\models\CronLogs;
use Yii;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\httpclient\Response;


class Parser
{
    const BASE_API_URL = 'https://91.234.16.57:443/solar-bridge-ext/ext/json-services/';
    const CARD_LIST_URL = 'getContractCards';
    const TRANSACTIONS_LIST_URL = 'getContractTransactions';

    const CONTENT_TYPE = 'application/json';
    const FAILED_GET_DATA_MES = 'Не удалось получить данные из источника ';
    const CERT_PATH = '@app/web/sources/licard/avtomirrostov.pem';

    const DATA_TIME_FORMAT = 'Y-m-d\TH:i:s';

    public $cronId;


    public function parseCards($contractId): array
    {
        $jsonContent = Json::encode(['parentId' => $contractId]);
        $response = $this->getResponse(self::CARD_LIST_URL, $jsonContent);
        $data = $this->getResponseData($response);
        if (!$data['getContractCardsRs'] || !($result = $data['getContractCardsRs']['getContractCardsPayload'])) {
            return [];
        }
        return $result;
    }

    public function parseStats($contractId, $year = null, $month = null): array
    {
        $dateFrom = DateTimeHelper::getDateFrom($year, $month, 'Y-m-d\T00:00:00');
        $dateTo = DateTimeHelper::getDateTo($year, $month, self::DATA_TIME_FORMAT);

        $jsonContent = Json::encode(
            [
                'contractId' => $contractId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]);

        $response = $this->getResponse(self::TRANSACTIONS_LIST_URL, $jsonContent);
        $data = $this->getResponseData($response);
        if (
            !$data['getContractTransactionsRs']
            || !$data['getContractTransactionsRs']['getContractTransactionsPayload']
            || !($result = $data['getContractTransactionsRs']['getContractTransactionsPayload']['contractTransactions'])
        ) {
            return [];
        }
        return $result;
    }


    public function parseDrivers($contractId): array
    {
        $array =  $this->parseStats($contractId);
        foreach ($array as $item) {
            if (isset($item['vehicleNumberOut'])) {
                $result[$item['cardNumberOut']] =$item['vehicleNumberOut'];
            }
        }
        return $result??[];
    }

    public function parseFuelTypes($contractId)
    {
        $array =  $this->parseStats($contractId);
        $services = [];
        foreach ($array as $item) {
            $services[] = [
                'service' => $item['goodsName'] ?? '',
                'unit' => $item['measureUnit'] ?? '',
            ];
        }
        $result = array_values(
            array_reduce($services, static function($carry, $item) {
                $carry[md5(serialize($item))] = $item;
                return $carry;
            }, [])
        );
        return $result??[];
    }

    private function getResponse(string $path, string $jsonContent): Response
    {
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
        ]);

        return  $client->createRequest()
            ->setMethod('POST')
            ->setUrl(self::BASE_API_URL . $path)
            ->setHeaders([
                'Content-Type' => self::CONTENT_TYPE
            ])
            ->setOptions([
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSLCERT => Yii::getAlias(self::CERT_PATH),
            ])
            ->setContent($jsonContent)
            ->send();

    }

    /**
     * @param $mes
     * @return void
     */
    private function setErrorLog(string $mes)
    {
        CronLogs::end($this->cronId, 0, 'Отчет пуст', $mes);
    }


    private function getResponseData(Response $response): array
    {
        if (!$response->isOk || $response->statusCode !== '200' || !($data = $response->data)) {
            $mes = str_replace(array("\r\n", "\r", "\n"), '', strip_tags($response->content) . 'statusCode: ' . $response->statusCode);
            $this->setErrorLog(self::FAILED_GET_DATA_MES . $mes);
            return [];
        }
        return $data;
    }




}