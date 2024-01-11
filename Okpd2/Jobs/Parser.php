<?php

declare(strict_types=1);

namespace app\models\Okpd2\Jobs;

use app\models\Okpd2\Repository\FilesHistoryRepository;
use DomainException;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\helpers\FileHelper;
use yii\queue\JobInterface;
use yii\queue\Queue;


class Parser implements JobInterface
{
    private FilesHistoryRepository $filesHistoryRepository;
    private Handler $handler;

    public function __construct(
        FilesHistoryRepository $filesHistoryRepository,
        Handler $handler
    ) {
        $this->filesHistoryRepository = $filesHistoryRepository;
        $this->handler = $handler;
    }

    public static function create(array $params): self
    {
        return Yii::$container->get(static::class, [], $params);
    }

    /**
     * @param Queue $queue which pushed and is handling the job
     * @return void result of the job execution
     */
    public function execute($queue)
    {
        if (!$item = $this->filesHistoryRepository->findUploaded()) {
            Yii::$app->queue->push(Loader::create([]));
            return;
        }
        if (!$pathExtracted = $this->getPathExtracted()) {
            return;
        }
        try {
            $files = glob($pathExtracted . $item->name . '/*.xml');
            foreach ($files as $file) {
                if ($this->handler->handle($file)) {
                    $item->changeStatusSuccess();
                    FileHelper::removeDirectory($pathExtracted . $item->name);
                } else {
                    $item->changeStatusError();
                }
            }
            $this->filesHistoryRepository->save($item);
        } catch (DomainException|InvalidArgumentException|ErrorException|\Exception $e) {
            Yii::warning($e->getMessage());
            return;
        }
        Yii::$app->queue->push(self::create([]));
    }

    /**
     * @return string|null
     */
    public function getPathExtracted(): ?string
    {
        try {
            return Yii::getAlias('@app/web/uploads/ftp/OKPD2/extracted/');
        } catch (DomainException|InvalidArgumentException|\Exception $e) {
            Yii::warning($e->getMessage());
            return null;
        }
    }

}