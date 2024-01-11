<?php

declare(strict_types = 1);

namespace app\models\Organization\Jobs;

use app\models\Organization\Handler;
use app\models\Organization\Loader;
use DomainException;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\helpers\FileHelper;
use yii\queue\JobInterface;
use yii\queue\Queue;
use app\models\Organization\WriteModel\OrganizationsFilesHistory as FilesHistoryWriteModel;


class Parser implements JobInterface
{

    /**
     * @var FilesHistoryWriteModel
     */
    private FilesHistoryWriteModel $filesHistoryWriteModel;
    /**
     * @var Handler
     */
    private Handler $handler;

    public function __construct(
        FilesHistoryWriteModel $filesHistoryWriteModel,
        Handler $handler
    ) {
        $this->filesHistoryWriteModel = $filesHistoryWriteModel;
        $this->handler = $handler;
    }

    public static function create(array $params): self
    {
        return Yii::$container->get(static::class, [], $params);
    }

    /**
     * @param Queue $queue which pushed and is handling the job
     * @return void|mixed result of the job execution
     */
    public function execute($queue)
    {
        if (!$item = $this->filesHistoryWriteModel->findUploaded()) {
            Yii::$app->queue->push(Loader::create([]));
            return;
        }
        try {
            $pathExtracted = $this->getPathExtracted();
            $files = glob($pathExtracted . $item->name . '/*.xml');
            foreach ($files as $file) {
                if ($this->handler->handle($file)) {
                    $item->changeStatusSuccess();
                    FileHelper::removeDirectory($pathExtracted . $item->name);
                } else {
                    $item->changeStatusError();
                }
            }
            $this->filesHistoryWriteModel->save($item);
        } catch (DomainException|InvalidArgumentException|ErrorException $e) {
            Yii::warning($e->getMessage());
        }
        Yii::$app->queue->push(self::create([]));
    }

    /**
     * @return bool|string
     */
    public function getPathExtracted()
    {
        return Yii::getAlias('@app/web/uploads/ftp/organization/extracted/');
    }

}