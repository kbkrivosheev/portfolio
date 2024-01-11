<?php

declare(strict_types=1);

namespace app\models\Okpd2\Jobs;

use app\helpers\Download;
use app\helpers\FtpClient\FtpException;
use app\models\Okpd2\Objects\Entities\OkpdFilesHistory;
use app\models\Okpd2\Repository\FilesHistoryRepository;
use Yii;
use yii\helpers\FileHelper;
use yii\queue\Queue;


class Loader extends Download
{

    public $host = 'ftp.zakupki.gov.ru';
    public $login = 'free';
    public $pass = 'free';
    public $directory = '/fcs_nsi/nsiOKPD2/';
    /**
     * @var FilesHistoryRepository
     */
    private FilesHistoryRepository $filesHistoryRepository;


    public function __construct(FilesHistoryRepository $filesHistoryRepository)
    {
        $this->filesHistoryRepository = $filesHistoryRepository;
    }

    public static function create(array $params): self
    {
        return Yii::$container->get(static::class, [], $params);
    }

    /**
     * @return bool|string
     */
    public function getPath()
    {
        return Yii::getAlias('@app/web/uploads/ftp/OKPD2/');
    }

    /**
     * @return bool|string
     */
    public function getPathExtracted()
    {
        return Yii::getAlias('@app/web/uploads/ftp/OKPD2/extracted/');
    }


    /**
     * @inheritDoc
     * @param Queue $queue
     * @return void
     * @throws FtpException
     */
    public function execute($queue): void
    {
        $i = 0;
        $this->connect();
        $files = $this->getFiles();
        $lastName = $this->filesHistoryRepository->getLastName();
        if ($lastName) {
            $files = $this->fileFilter($files, $lastName);
        }
        $path = $this->getPath();
        $pathExtracted = $this->getPathExtracted();
        if (!$this->createDirectory($path) && !$this->createDirectory($pathExtracted)) {
            return;
        }
        foreach ($files as $file) {
            $this->downloadFile($file, $path, $pathExtracted);
            if ($this->stopCheck($i++)) {
                break;
            }
        }
        if (($i > 0 && $i < 10) || $this->filesHistoryRepository->findUploaded()) {
            Yii::$app->queue->push(Parser::create([]));
        }
    }

    /**
     * @param string $file
     * @param string $path
     * @param string $pathExtracted
     * @return void
     */
    private function downloadFile(string $file, string $path, string $pathExtracted): void
    {
        $fileName = basename($file);
        if (!$this->filesHistoryRepository->checkFileExistence($fileName)
            && $this->download(
                $path . $fileName,
                $file
            )
            && $this->extractFile(
                $path . $fileName,
                $pathExtracted . $fileName
            )
            && FileHelper::unlink($path . $fileName)) {
            $this->saveFile($fileName);
        }
    }

    /**
     * @param $i
     * @return bool
     */
    private function stopCheck(int $i): bool
    {
        if ($i === 10) {
            Yii::$app->queue->push(Parser::create([]));
            return true;
        }
        return false;
    }


    /**
     * @param string $fileName
     * @return void
     */
    private function saveFile(string $fileName): void
    {
        $model = new OkpdFilesHistory();
        $data =
            [
                'name' => $fileName,
                'date' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'status' => OkpdFilesHistory::STATUS_UPLOADED
            ];
        $model->setAttributes($data);
        if (!$model->validate()) {
            Yii::warning($model->getErrors());
            return;
        }
        $model->save(false);
    }



}