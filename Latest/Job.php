<?php

declare(strict_types=1);

namespace app\Features\Parsers\RoszdravnadzorRegistry\MedicalEquipment;

use Exception;
use Yii;
use yii\queue\JobInterface;

class Job implements JobInterface
{
    private Loader $loader;
    private Handler $handler;

    public function __construct(Loader $loader, Handler $handler)
    {
        $this->loader = $loader;
        $this->handler = $handler;
    }

    public static function create(array $params): self
    {
        return Yii::$container->get(static::class, [], $params);
    }

    public function execute($queue): void
    {
        try {
            $this->handler->handle($this->loader->handle());
        } catch (Exception $e) {
            Yii::error($e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'MedicalEquipmentsParser');
        }
    }

}