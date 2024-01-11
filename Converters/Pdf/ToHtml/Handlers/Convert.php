<?php

declare(strict_types=1);

namespace app\Features\Converters\Pdf\ToHtml\Handlers;

use app\components\Constants;
use app\Features\Converters\Common\Exceptions\ConversionException;
use app\Features\Converters\Pdf\ToHtml\Dto\Decision;
use app\Features\Converters\Pdf\ToHtml\Helpers\Html;
use app\helpers\Pdf;
use app\models\DecisionsFas;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RuntimeException;
use Yii;

final class Convert
{
    private const UPLOADS_FTP_DIR = '@app/web/uploads/ftp/';
    private const PDF_EXTENSION = 'pdf';

    /**
     * @throws RuntimeException
     * @throws ConversionException|GuzzleException
     */
    public function handle(DecisionsFas $model, string $content): Decision
    {
        $directoryPath = $this->getDirectoryPath();
        $this->makeFolder($directoryPath);
        $pdfFilePath = $this->getFilePath($directoryPath, (string)$model->id, self::PDF_EXTENSION);
        $this->makeFile($pdfFilePath, $content);
        try {
            return $this->getDecisionData($pdfFilePath);
        } catch (Exception $e) {
            throw new ConversionException(
                "Ошибка конвертации: " . $e->getMessage() . ". DecisionFasId=\"" . $model->id . "\"",
                $e->getCode(),
                $e
            );
        } finally {
            $this->delTempFiles($pdfFilePath);
        }
    }

    /**
     * @param string $pdfFilePath
     * @return Decision
     * @throws ConversionException|GuzzleException
     * @throws Exception
     */

    private function getDecisionData(string $pdfFilePath): Decision
    {
        $pdfImage = 0;
        if (Pdf::isImage($pdfFilePath)) {
            $this->convertPdf2Pdfa($pdfFilePath);
            $pdfImage = 1;
        }
        if (empty($html = $this->convertPdf2Html($pdfFilePath))) {
            throw new ConversionException("Не удалось конвертировать pdf в html \"$pdfFilePath\"");
        }
        try {
            $result = new Decision(
                Html::purgeHtml($html),
                Html::getStyleJson($html),
                $pdfImage
            );
        } catch (Exception $e) {
            throw new ConversionException('Ошибка при создании объекта Decision: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * @param string $pdfPath
     * @return void
     * @throws ConversionException
     * @throws GuzzleException
     */

    public function convertPdf2Pdfa(string $pdfPath): void
    {
        try {
            $client = new Client();
            $response =
                $client->request('POST', $_ENV['OCR_MY_PDF_URL'], [
                    'multipart' => [
                        [
                            'name' => 'params',
                            'contents' => '--rotate-pages --deskew --force-ocr --continue-on-soft-render-error -l rus+eng',
                        ],
                        [
                            'name' => 'file',
                            'contents' => fopen($pdfPath, 'r'),
                        ]
                    ]
                ]);
            $content = $response->getBody()->getContents();
            if (empty($content) || file_put_contents($pdfPath, $content) === false) {
                throw new ConversionException('Не удалось сохранить файл pdf ' . $pdfPath);
            }
        } catch (Exception $e) {
            throw new ConversionException($e->getMessage() . ' ' . $pdfPath);
        }
    }

    /**
     * @param string $pdfPath
     * @return string
     * @throws ConversionException
     * @throws GuzzleException
     */
    public function convertPdf2Html(string $pdfPath): string
    {
        try {
            $client = new Client();
            $response = $client->get(
                $_ENV['PDF_2_HTML_URL'],
                [
                    'query' => [
                        'url' => $pdfPath
                    ],
                ]);
            return $response->getBody()->getContents();
        } catch (Exception $e) {
            throw new ConversionException($e->getMessage());
        }
    }

    private function getDirectoryPath(): string
    {
        return Yii::getAlias(self::UPLOADS_FTP_DIR) . Constants::getPathFolderByType(Constants::TYPE_FAS) . 'tempo';
    }

    /**
     * @throws RuntimeException
     */
    private function makeFolder(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException("Не удалось создать папку \"$path\"");
        }
    }

    /**
     * @throws RuntimeException
     */
    private function makeFile(string $path, string $content): void
    {
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Не удалось записать данные в файл \"$path\"");
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function getChangedFilePath(string $filePath, string $newExtension): string
    {
        if (!$filePath || !$newExtension) {
            throw new InvalidArgumentException('Имя файла и расширение должны быть заполнены.');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($extension === $newExtension) {
            return $filePath;
        }

        $directoryPath = pathinfo($filePath, PATHINFO_DIRNAME);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);

        return $this->getFilePath($directoryPath, $fileName, $newExtension);
    }

    private function getFilePath(string $directoryPath, string $fileName, string $extension): string
    {
        return $directoryPath . DIRECTORY_SEPARATOR . $fileName . '.' . $extension;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function delTempFiles(string $filePath): void
    {
        $file = $this->getChangedFilePath($filePath, self::PDF_EXTENSION);
        if (file_exists($file)) {
            unlink($file);
        }
    }

}