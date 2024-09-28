<?php

declare(strict_types=1);

namespace app\modules\api\controllers;


use app\Features\MedEquip\Forms\TariffSearchForm;
use app\Infrastructure\Service\Serializer\SymfonySerializer;
use app\Infrastructure\Service\Validator\SymfonyAsserts;
use app\Infrastructure\Uuid\Uuid;
use app\Features\MedEquip\Command\Export\Excel\Form as ExportExcelForm;
use app\Features\MedEquip\Command\Export\Excel\Handler as ExportExcelHandler;
use app\Features\MedEquip\Command\Import\Excel\Form as ImportExcelForm;
use app\Features\MedEquip\Command\Import\Excel\Handler as ImportExcelHandler;
use app\Features\MedEquip\Command\Search\Form as SearchForm;
use app\Features\MedEquip\Command\Search\Handler as SearchHandler;
use Exception;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\helpers\Json;
use yii\rest\Serializer;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use app\Features\MedEquip\Query\TariffSearch;

final class MedEquipController extends Controller
{
    private SearchHandler $searchHandler;
    private ExportExcelHandler $exportExcelHandler;
    private ImportExcelHandler $importExcelHandler;
    private SymfonySerializer $symfonySerializer;
    private SymfonyAsserts $validator;

    private TariffSearch $tariffSearch;
    private Serializer $serializer;

    public function __construct(
        $id,
        $module,
        SearchHandler $searchHandler,
        ExportExcelHandler $exportExcelHandler,
        ImportExcelHandler $importExcelHandler,
        SymfonySerializer $symfonySerializer,
        TariffSearch $tariffSearch,
        SymfonyAsserts $validator,
        Serializer $serializer,
        $config = []
    )
    {
        $this->searchHandler = $searchHandler;
        $this->exportExcelHandler = $exportExcelHandler;
        $this->importExcelHandler = $importExcelHandler;
        $this->symfonySerializer = $symfonySerializer;
        $this->tariffSearch = $tariffSearch;
        $this->validator = $validator;
        $this->serializer = $serializer;
        parent::__construct($id, $module, $config);
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionSearch(): Response
    {
        $form = new SearchForm();
        try {
            if (!$form->load(Yii::$app->request->post(), '') || !$form->validate()) {
                return $this->badRequest($form->getErrors());
            }
            $dataProvider = new ActiveDataProvider([
                'query' => $this->searchHandler->handle($form),
                'pagination' => [
                    'pageSize' => $form->limit,
                    'page' => $form->page
                ]
            ]);
            $pagination = $dataProvider->getPagination();
            $page = $pagination->getPage();
            $pageCount = $pagination->getPageCount();
            return $this->asJson([
                'status' => true,
                'data' => $dataProvider->getModels(),
                'hasMore' => $page < $pageCount - 1,
                'totalCount' => $dataProvider->getTotalCount()
            ]);
        } catch (Exception $e) {
            Yii::error($e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'MedEquip');
            return $this->badRequest($e->getTrace());
        }
    }


    public function actionExport()
    {
        try {
            $json = Yii::$app->request->post();
            $array = Json::decode($json);
            $form = $this->symfonySerializer->arrayToObject($array, ExportExcelForm::class);
            $this->validator->validate($form);
            return $this->exportExcelHandler->handle($form);
        } catch (Exception $e) {
            $uuid = Uuid::next();
            Yii::error($uuid . '-' . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'medEquip');
            return $this
                ->asJson(['message' => "Ошибка при формировании excel файла. Обратитесь к администрации сайта, указав код ошибки ({$uuid})."])
                ->setStatusCode(400);
        }
    }

    public function actionImport(): Response
    {
        $form = new ImportExcelForm();
        $data = [
            'file' => UploadedFile::getInstanceByName('file')
        ];

        try {
            if ($form->load($data, '') && $form->validate()) {
                $products = $this->importExcelHandler->handle($form);
                return $this->asJson($this->serializer->serialize($products));
            }
            return $this->badRequest($form->getErrors());
        } catch (Exception $e) {
            return $this->badRequest($e->getTrace());
        }
    }

    public function actionTariffSearch(): Response
    {
        $form = new TariffSearchForm();

        try {
            if (!$form->load(Yii::$app->request->get(), '') || !$form->validate()) {
                return $this->badRequest($form->getErrors());
            }
            $query = $this->tariffSearch->search($form);
            $pagination = new Pagination(
                [
                    'totalCount' => $query->count(),
                    'pageSize' => $form->limit,
                    'page' => $form->page,
                    'forcePageParam' => false,
                    'pageSizeParam' => false,
                ]
            );
            $dataProvider = new ActiveDataProvider([
                'query' => $query->limit($pagination->offset)->offset($pagination->limit),
                'pagination' => $pagination
            ]);
            return $this->asJson([
                'status' => true,
                'data' => $dataProvider->getModels(),
                'hasMore' => $form->page < $pagination->getPage() - 1,
                'totalCount' => $dataProvider->getTotalCount()
            ]);

        } catch (Exception $e) {
            Yii::error($e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'MedEquip');
            return $this->badRequest($e->getTrace());
        }
    }

     private function badRequest(array $errors): Response
    {
        return $this->asJson(['status' => false, 'errors' => $errors])->setStatusCode(400);
    }
}