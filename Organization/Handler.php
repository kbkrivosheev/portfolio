<?php

declare(strict_types=1);

namespace app\models\Organization;

use app\helpers\XMLHelper;
use app\models\Organization\Helpers\Labels;
use app\models\Organization\Objects\Entities\Organizations;
use SimpleXMLElement;
use XMLReader;
use Yii;
use app\models\Organization\WriteModel\Organizations as OrganizationsWriteModel;

class Handler
{
    /**
     * @var OrganizationsWriteModel
     */
    private OrganizationsWriteModel $organizationsWriteModel;

    public function __construct(OrganizationsWriteModel $organizationsWriteModel)
    {
        $this->organizationsWriteModel = $organizationsWriteModel;
    }


    /**
     * @throws \Exception
     */
    public function handle($file): bool
    {
        /**
         * @var  $xml XMLReader
         */
        $xml = XMLReader::open($file);
        if (!$xml || !$xml->read() || $xml->nodeType !== XMLReader::ELEMENT) {
            return false;
        }
            $xmlData = preg_replace('/(<\/?)oos:/', '$1', $xml->readOuterXml());
            $xmlObj = new SimpleXMLElement($xmlData);
            $i=0;
            $organizations= $xmlObj->nsiOrganizationList->nsiOrganization;
            foreach ($organizations as $organization) {
                if($this->parseBlock($organization))
                {
                    $i++;
                }
            }
            return $i == count($organizations);

    }

    /**
     * @param SimpleXMLElement $xml
     * @return bool
     */
    private function parseBlock(SimpleXMLElement $xml)
    {
        $fullName = XMLHelper::getAttributeValue($xml, ['fullName']);
        $data = [
            'full_name_hash' => $this->getHash($fullName),
            'full_name' => $fullName,
            'short_name' => XMLHelper::getAttributeValue($xml, ['shortName']),
            'inn' => XMLHelper::getAttributeValue($xml, ['INN']),
            'kpp' => XMLHelper::getAttributeValue($xml, ['KPP']),
            'ogrn' => XMLHelper::getAttributeValue($xml, ['OGRN']),
            'oktmo' => XMLHelper::getAttributeValue($xml, ['OKTMO', 'code']),
            'address' => XMLHelper::getAttributeValue($xml, ['postalAddress']),
            'phone' => XMLHelper::getAttributeValue($xml, ['phone']),
            'fax' => XMLHelper::getAttributeValue($xml, ['fax']),
            'email' => XMLHelper::getAttributeValue($xml, ['email']),
            'website' => XMLHelper::getAttributeValue($xml, ['url']),
            'role' => Labels::getRoles()[XMLHelper::getAttributeValue($xml, ['organizationRoles', 'organizationRoleItem', 'organizationRole'])]??'',
            'region' => XMLHelper::getAttributeValue($xml, ['factualAddress', 'region', 'fullName']),
            'status' => $this->stringToInt(XMLHelper::getAttributeValue($xml, ['actual'])),
        ];
        $model = $this->organizationsWriteModel->findByFullNameHash($data['full_name_hash']) ?? new Organizations();
        $model->setAttributes($data);
        if (!$model->validate()) {
            Yii::warning($model->getErrors());
            return false;
        }
       return $model->save(false);

    }

    private function stringToInt(string $string): int
    {
        return $string === 'true' || $string === '1' ? 1 : 0;
    }

    private function getHash(string $fullName):string
    {
        return  hash('md5', $fullName);
    }

}