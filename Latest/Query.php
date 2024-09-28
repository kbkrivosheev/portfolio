<?php

declare(strict_types=1);

namespace app\ReadModels\Ktru\Search;

use app\helpers\Str;
use app\models\NsiKtru;
use app\models\Okpd2\Objects\Entities\Okpd2Catalog;
use app\modules\api\models\products\ProductsSearch;
use app\ReadModels\Ktru\Search\Dto\View;
use app\ReadModels\Ktru\Search\Dto\ViewBuilder;
use DomainException;
use Generator;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\sphinx\MatchExpression;
use yii\sphinx\Query as SphinxQuery;

final class Query
{
    private const MIN_CODE_LENGTH = 12;

    public function findByQuery(Filter $filter): ActiveDataProvider
    {
        $match = $this->getMatch($filter->query);
        $ids = $this->getIds($match);
        $query = $this->getQuery($ids);
        $dataProvider = $this->getDataProvider($query, $filter->limit, $filter->page);
        $dataProvider->setModels(ViewBuilder::buildFromModels($dataProvider->getModels(), []));
        return $dataProvider;
    }

    public function findByParent(Filter $filter): ActiveDataProvider
    {
        $q = NsiKtru::find()
            ->select([
                'nsi_ktru.id',
                'nsi_ktru.name',
                'nsi_ktru.code',
                'nsi_ktru.unit',
                'nsi_ktru.characteristics',
                'nsi_ktru.is_template',
                'nsi_ktru.application_date_start',
                'nsi_ktru.application_date_end',
                'nsi_ktru.publish_date',
                'nsi_ktru.inclusion_date',
                'nsi_ktru.description',
                'nsi_ktru.classifiers_code',
                'nsi_ktru.classifiers_name',
                'nsi_ktru.classifiers_description',
            ])
            ->where(['nsi_ktru.parent_code' => $filter->query])
            ->andWhere(['nsi_ktru.actual' => NsiKtru::ACTUAL]);
        if ($filter->filters) {
            foreach ($this->filterByCharacteristics($filter->filters) as $item) {
                if (empty($item) || !isset($item['condition'], $item['params'])) {
                    continue;
                }
                $q->andWhere($item['condition'], $item['params']);
            }
        }
        $models = $q
            ->joinWith(['units'])
            ->groupBy(['nsi_ktru.id'])
            ->distinct()
            ->orderBy(['nsi_ktru.id' => SORT_DESC]);

        $dataProvider = $this->getDataProvider($models, $filter->limit, $filter->page);
        $dataProvider->setModels(ViewBuilder::buildFromModels($dataProvider->getModels(), []));
        return $dataProvider;
    }

    private function filterByCharacteristics(array $characteristicFilters)
    {
        if (empty($characteristicFilters)) {
            return;
        }
        $i = 0;
        foreach ($characteristicFilters as $item) {
            if (empty($item)) {
                continue;
            }
            $conditions = $queries = [];
            foreach ($item as $value) {
                $name = ':jsonCondition' . ++$i;
                $conditions[$name] = $value;
                $queries[] = 'JSON_CONTAINS(characteristics, ' . $name . ', \'$\')';
            }
            yield ['condition' => implode(' OR ', $queries), 'params' => $conditions];
        }
    }

    /**
     * @throws Exception
     */
    public function findByOkpd(ByOkpdFilter $filter): ActiveDataProvider
    {

        $query = <<<SQL
        WITH RECURSIVE parent_records AS (
          SELECT code, id, name, parent_code
          FROM okpd2_catalog
          WHERE code = '{$filter->okpd2}'
          UNION ALL
          SELECT d.code, d.id, d.name, d.parent_code
          FROM okpd2_catalog AS d
          INNER JOIN parent_records AS pr ON pr.parent_code = d.code
        )
        SELECT code FROM parent_records;
        SQL;
        $results = array_column(Yii::$app->db->createCommand($query)->queryAll(), 'code');

        $ids = NsiKtru::find()
            ->select(['nsi_ktru.id'])
            ->innerJoinWith([
                'okpd' => static function (ActiveQuery $q) use ($results) {
                    return $q->andOnCondition(['IN', 'okpd2_catalog.code', $results]);
                }
            ], false)
            ->column();

        if ($filter->query) {
            $ktruQueryIds = $this->getIds($this->getMatch($filter->query));
            $ids = array_intersect($ktruQueryIds, $ids);
        }
        $dataProvider = $this->getDataProvider($this->getQuery($ids)->joinWith(['okpd']), $filter->limit, $filter->page);
        $kpd2CatalogModels = $this->getOkpd2CatalogModels($dataProvider->getModels(), $filter->okpd2);
        $dataProvider->setModels(ViewBuilder::buildFromModels($dataProvider->getModels(), [], $kpd2CatalogModels));
        return $dataProvider;
    }

    private function getQuery(array $ids): ActiveQuery
    {
        return NsiKtru::find()
            ->select([
                'nsi_ktru.id',
                'nsi_ktru.name',
                'nsi_ktru.code',
                'nsi_ktru.unit',
                'nsi_ktru.characteristics',
                'nsi_ktru.is_template',
                'nsi_ktru.okpd2_code',
                'nsi_ktru.application_date_start',
                'nsi_ktru.application_date_end',
                'nsi_ktru.publish_date',
                'nsi_ktru.inclusion_date',
                'nsi_ktru.description',
                'nsi_ktru.classifiers_code',
                'nsi_ktru.classifiers_name',
                'nsi_ktru.classifiers_description',
                'COUNT(DISTINCT children.code) AS children_count'
            ])
            ->where(['nsi_ktru.id' => $ids])
            ->joinWith([
                'children' => static function (ActiveQuery $q) {
                    $q->andOnCondition(['children.actual' => NsiKtru::ACTUAL]);
                }
            ])
            ->leftJoin('nsi_ktru medical_equipments', 'nsi_ktru.classifiers_code = medical_equipments.nkmi')
            ->andWhere(['nsi_ktru.actual' => NsiKtru::ACTUAL])
            ->joinWith(['units'])
            ->groupBy(['nsi_ktru.id'])
            ->distinct()
            ->orderBy(['children_count' => SORT_DESC, 'nsi_ktru.id' => SORT_ASC]);
    }

    public function findFromOkpd2(Filter $filter): array
    {
        $match = $this->getMatch($filter->query);
        $ids = $this->getIds($match, $filter->limit);
        $models = NsiKtru::find()
            ->select(['nsi_ktru.id', 'nsi_ktru.name', 'nsi_ktru.code', 'nsi_ktru.unit'])
            ->where(['nsi_ktru.id' => $ids])
            ->joinWith(['units'])
            ->andWhere(['is_template' => NsiKtru::NOT_TEMPLATE])
            ->distinct()
            ->all();

        return ViewBuilder::buildFromModels($models, []);
    }

    private function getDataProvider(ActiveQuery $query, int $limit, int $page): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => $limit,
                'pageSizeLimit' => [0, $limit],
                'page' => $page - 1
            ]
        ]);
    }

    private function getNameMatch(string $match): MatchExpression
    {
        return (new MatchExpression())->match(['ktru_name' => $match]);
    }

    private function getCodeMatch(array $codes): MatchExpression
    {
        return (new MatchExpression())->match(['ktru_code' => $codes]);
    }

    private function getIds(MatchExpression $match, $limit = 1000): array
    {
        return (new SphinxQuery())
            ->select(['id'])
            ->from('zakupki_ktru')
            ->match($match)
            ->andWhere(['>', 'code_length', self::MIN_CODE_LENGTH])
            ->limit($limit)
            ->column();
    }

    private function getMatch(?string $query): MatchExpression
    {
        return Str::isCode($query)
            ? $this->getCodeMatch([$query])
            : $this->getNameMatch($query);
    }

    private function getOkpd2CatalogModels(array $data, string $okpd2): array
    {
        $result = [];
        foreach ($data as $item) {
            if (empty($item->okpd) || empty($item->okpd2_code)) {
                continue;
            }
            $result[$item->okpd2_code] = $this->getOkpd2CatalogModel($item, $okpd2);
        }

        return $result;
    }

    private function getOkpd2CatalogModel(NsiKtru $item, string $okpd2): Okpd2Catalog
    {
        if (in_array($okpd2, array_column($item->okpd, 'code'), true)) {
            foreach ($item->okpd as $value) {
                if ($value->code === $okpd2) {
                    return $value;
                }
            }
        }
        return array_shift($item->okpd);
    }

}
