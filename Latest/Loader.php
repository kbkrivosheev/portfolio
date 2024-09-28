<?php

declare(strict_types=1);

namespace app\Features\Parsers\RoszdravnadzorRegistry\MedicalEquipment;

use DateTimeImmutable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Yii;
use yii\web\BadRequestHttpException;

final class Loader
{
    private const URL = 'https://roszdravnadzor.gov.ru/services/misearch';
    private const UPLOADS_DIR = '@app/web/uploads/MedicalProducts';
    private const FILE_NAME = 'registry.xls';
    private const HEADERS = ['Content-Type' => 'application/x-www-form-urlencoded'];

    public function handle(): string
    {
        try {
            $directoryPath = Yii::getAlias(self::UPLOADS_DIR);
            $this->makeFolder($directoryPath);
            $filePath = $directoryPath . DIRECTORY_SEPARATOR . self::FILE_NAME;
            $this->makeFile($filePath, $this->getContent());
        } catch (BadRequestHttpException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        return $filePath;
    }

    private function getContent(): string
    {
        $client = new Client();
        try {
            $response = $client->post(
                self::URL,
                [
                    'query' => [
                        'xls' => 1,
                        'q_status' => 1,
                        'dt_ru_from' => '01.01.2007',
                        'dt_ru_to' => (new DateTimeImmutable())->format('d.m.Y'),
                    ],
                    'headers' => self::HEADERS
                ]

            );
        } catch (GuzzleException | Exception $e) {
            throw new BadRequestHttpException('Ошибка при выполнении запроса: ' . $e->getMessage());
        }
        if ($response->getStatusCode() !== 200 || !$response->getBody()) {
            throw new BadRequestHttpException('Данные не получены');
        }
        return $response->getBody()->getContents();
    }
    private function makeFolder(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException("Не удалось создать папку \"$path\"");
        }
    }
    private function makeFile(string $path, string $content): void
    {
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Не удалось записать данные в файл \"$path\"");
        }
    }

}