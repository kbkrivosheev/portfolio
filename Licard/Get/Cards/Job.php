<?php

namespace app\models\sources\Licard\Get\Cards;

use app\models\CronLogs;
use app\models\sources\Licard\Get\Parser;
use app\Repository\SourcesRepository;


use Yii;
use yii\queue\JobInterface;

class Job implements JobInterface
{
    public $sourceId;

    /**
     * @var SourcesRepository
     */
    private $repository;
    /**
     * @var Handler
     */
    private $handler;
    /**
     * @var Parser
     */
    private $parser;


    public function __construct(SourcesRepository $repository, Handler $handler, Parser $parser)
    {
        $this->repository = $repository;
        $this->handler = $handler;
        $this->parser = $parser;
    }

    public static function create(array $params): self
    {
        return Yii::$container->get(static::class, [], $params);
    }

    /**
     * @inheritDoc
     */
    public function execute($queue)
    {
        $source = $this->repository->getById($this->sourceId);
        if (!$source || $source->type !== $source::LICARD) {
            CronLogs::end($this->sourceId, 0, 'Отчет пуст', 'Пустой источник');
        }
        $cronId = CronLogs::start(CronLogs::CRON_LICARD_API_PARSE_CARDS, $source->id);
        $this->parser->cronId = $cronId;
        $data = $this->parser->parseCards($source->contract_id);
        $drivers = $this->parser->parseDrivers($source->contract_id);
        if (!$data) {
            CronLogs::end($cronId, 0, 'Отчет пуст', 'Не удалось получить данные из источника');
            return;
        }
        if ($count = $this->handler->handle($data, $source->id, $drivers)) {
            CronLogs::end($cronId, $count, null, 'Задача выполнена успешно');
        }
    }

}