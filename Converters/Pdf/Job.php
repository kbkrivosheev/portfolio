<?php

declare(strict_types=1);

namespace app\Features\Converters\Pdf;

use app\Features\Converters\Pdf\ToHtml\Command;
use app\Features\Converters\Pdf\ToHtml\Handler;
use app\Infrastructure\Service\TelegramNotifier\TelegramNotifier;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\queue\JobInterface;

class Job implements JobInterface
{
    public Command $command;
    private Handler $handler;
    private TelegramNotifier $telegramNotifier;

    public function __construct(Handler $handler, TelegramNotifier $telegramNotifier)
    {
        $this->handler = $handler;
        $this->telegramNotifier = $telegramNotifier;
    }

    /**
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public static function create(Command $command): self
    {
        return Yii::$container->get(static::class, [], ['command' => $command]);
    }

    public function execute($queue): void
    {
        ini_set('memory_limit', '2048M');
        try {
            $this->handler->handle($this->command);
        } catch (Exception $e) {
            $this->telegramNotifier->queueNotifyException($e);
            Yii::error('Ошибка распознавания файла: ' . $e->getMessage(), 'pdf');
        }
    }
}
