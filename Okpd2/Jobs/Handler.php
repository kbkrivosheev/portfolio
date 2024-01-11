<?php

declare(strict_types=1);

namespace app\models\Okpd2\Jobs;

use app\helpers\XMLHelper;
use app\models\Okpd2\Objects\Entities\Okpd2Catalog;
use app\Repository\Okpd2CatalogRepository;
use SimpleXMLElement;
use XMLReader;
use Yii;

class Handler
{
    /**
     * @var Okpd2CatalogRepository
     */
    private Okpd2CatalogRepository $okpd2CatalogRepository;


    public function __construct(Okpd2CatalogRepository $okpd2CatalogRepository)
    {
        $this->okpd2CatalogRepository = $okpd2CatalogRepository;
    }


    /**
     * @throws \Exception
     */
    public function handle($file): bool
    {
        $i = 0;
        /**
         * @var  $xml SimpleXMLElement
         */
        $xmlObj = new SimpleXMLElement($file, 0, true);
        if (!$xmlObj->nsiOKPD2List || !$okpdList = $xmlObj->nsiOKPD2List->nsiOKPD2) {
            return false;
        }
        $okpd2CatalogCodes = $this->okpd2CatalogRepository->getCodesArray();
        foreach ($okpdList as $xml) {
            if (!$elem = $xml->children('oos', true)) {
                continue;
            }
            if ($this->parseBlock($elem, $okpd2CatalogCodes)) {
                $i++;
            }
        }
        return $i === count($okpdList);
    }

    /**
     * @param SimpleXMLElement $xml
     * @return bool
     */
    private function parseBlock(SimpleXMLElement $xml, array $okpd2CatalogCodes): bool
    {
        $code = XMLHelper::getAttributeValue($xml, ['code']);
        $data = [
            'name' => XMLHelper::getAttributeValue($xml, ['name']),
            'code' => $code,
            'parent_code' => XMLHelper::getAttributeValue($xml, ['parentCode']),
            'actual' => $this->stringToInt(XMLHelper::getAttributeValue($xml, ['actual']))
        ];

        if (!$okpd2CatalogCodes
            || !array_key_exists($code, $okpd2CatalogCodes)
            || !$model = $this->okpd2CatalogRepository->findByCode($code)) {
            $model = new Okpd2Catalog();
        }
        $model->setAttributes($data);
        if (!$model->validate()) {
            Yii::warning($model->getErrors());
            return false;
        }
        return $model->save(false);
    }

    private function stringToInt(?string $string): int
    {
        return (int)in_array($string, ['true', '1', true, 1], true);
    }

}