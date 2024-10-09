<?php

declare(strict_types=1);

namespace app\Features\Parsers\RoszdravnadzorRegistry\MedicalEquipment;

use DateTimeImmutable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use RuntimeException;
use Yii;
use yii\helpers\BaseFileHelper;
use yii\web\BadRequestHttpException;

final class Loader
{
    private const URL = 'https://roszdravnadzor.gov.ru/services/misearch';
    private const UPLOADS_DIR = '@app/web/uploads/MedicalProducts';
    private const FILE_NAME = 'registry.xls';
    private const HEADERS = ['Content-Type' => 'application/x-www-form-urlencoded'];
    private const START_DATE = '01.01.2007';


    /**
     * @throws LoaderException
     */
    public function handle(): string
    {
        try {
            $directoryPath = Yii::getAlias(self::UPLOADS_DIR);
            BaseFileHelper::createDirectory($directoryPath);
            $filePath = $directoryPath . DIRECTORY_SEPARATOR . self::FILE_NAME;
            $this->makeFile($filePath, $this->getContent());
        } catch (Exception $e) {
            throw new LoaderException($e->getMessage());
        }
        return $filePath;
    }

    /**
     * @throws BadRequestHttpException
     */
    private function getContent(): string
    {
        $client = new Client();
        try {
            $response = $client->post(self::URL, [
                RequestOptions::QUERY => [
                    'xls' => 1,
                    'q_status' => 1,
                    'dt_ru_from' => self::START_DATE,
                    'dt_ru_to' => (new DateTimeImmutable())->format('d.m.Y'),
                ],
                RequestOptions::HEADERS => self::HEADERS
            ]);
        } catch (GuzzleException|Exception $e) {
            throw new BadRequestHttpException('Ошибка при выполнении запроса: ' . $e->getMessage());
        }
       return $response->getBody()->getContents();
    }

    /**
     * @throws RuntimeException
     */
    private function makeFile(string $path, string $content): void
    {
        $stream = fopen($path, 'wb');
        if ($stream === false) {
            throw new RuntimeException("Не удалось открыть файл $path для записи");
        }
        $chunkSize = (8 ** 8) / 2; // 8 Mb
        $length = mb_strlen($content);
        for ($offset = 0; $offset < $length; $offset += $chunkSize) {
            fwrite($stream, mb_substr($content, $offset, $chunkSize));
        }
        fclose($stream);
    }
}