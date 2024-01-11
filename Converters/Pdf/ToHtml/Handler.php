<?php

declare(strict_types=1);

namespace app\Features\Converters\Pdf\ToHtml;

use app\commands\PdfController;
use app\Features\Converters\Common\Exceptions\ConversionException;
use app\Features\Converters\Pdf\Job;
use app\Features\Converters\Pdf\ToHtml\Dto\Decision;
use app\Features\Converters\Pdf\ToHtml\Handlers\Convert;
use app\models\DecisionsFas;
use app\Repository\DecisionsFasRepository;
use DomainException;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use Yii;
use yii\helpers\HtmlPurifier;

final class Handler
{
    private const CONCURRENCY = 10;

    private DecisionsFasRepository $decisionsFasRepository;
    private Convert $convert;
    private Client $client;

    public function __construct(DecisionsFasRepository $decisionsFasRepository, Convert $convert)
    {
        $this->decisionsFasRepository = $decisionsFasRepository;
        $this->convert = $convert;
    }

    public function handle(Command $command): void
    {
        $models = $this->decisionsFasRepository->getUnreadPdfFiles($command->limit);
        if (empty($models)) {
            return;
        }
        $this->client = new Client();
        $pool = new Pool($this->client, $this->getRequests($models), [
            'concurrency' => self::CONCURRENCY,
            'fulfilled' => function (Response $response, int $id) use ($models) {
                if ($response->getStatusCode() !== 200 || !$response->getBody()) {
                    Yii::error("DecisionFasId=$id. Response status={$response->getStatusCode()}", 'pdf');
                    return;
                }
                if ($model = $models[$id] ?? null) {
                    $this->processFile($model, $response->getBody()->getContents());
                }
            },
            'rejected' => function (RequestException $e, int $id) {
                if ($e->getResponse() === null) {
                    Yii::error("DecisionFasId=$id. Bad response", 'pdf');
                    return;
                }
                Yii::error("DecisionFasId=$id. {$e->getResponse()->getBody()->getContents()}", 'pdf');
            },
        ]);
        $promise = $pool->promise();
        Yii::$app->queue->push(Job::create(new Command()));
        $promise->wait();

    }

    /**
     * @param DecisionsFas[] $models
     */
    private function getRequests(array $models): Generator
    {
        foreach ($models as $model) {
            yield $model->id => function () use ($model) {
                $request = new Request('GET', $model->url, [
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36'
                ]);
                return $this->client->sendAsync($request);
            };
        }
    }

    /**
     * @throws DomainException
     * @throws RuntimeException
     * @throws ConversionException
     */
    public function processFile(DecisionsFas $decisionsFasModel, string $content): void
    {
        try {
            $decisionData = $this->convert->handle($decisionsFasModel, $content);
            $this->approveDecision($decisionsFasModel, $decisionData);
        } catch (ConversionException $e) {
            $this->cancelDecision($decisionsFasModel);
            throw $e;
        }
    }

    /**
     * @throws DomainException
     */
    private function approveDecision(DecisionsFas $model, Decision $decisionData): void
    {
        $model->decision = strip_tags(HtmlPurifier::process($decisionData->html));
        $model->decision_html = $decisionData->html;
        $model->decision_style = $decisionData->style;
        $model->pdf_image = $decisionData->pdfImage;
        $model->status = DecisionsFas::STATUS_SUCCESS;
        $this->decisionsFasRepository->save($model);
    }

    /**
     * @throws DomainException
     */
    private function cancelDecision(DecisionsFas $model): void
    {
        $model->status = DecisionsFas::STATUS_ERROR;

        $this->decisionsFasRepository->save($model);
    }
}
