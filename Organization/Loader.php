<?php
declare(strict_types=1);

namespace app\models\Organization;

use app\helpers\Download;
use app\helpers\FtpClient\FtpException;
use app\models\Organization\Jobs\Parser;
use Yii;
use yii\helpers\FileHelper;
use yii\queue\Queue;
use ZipArchive;
use app\models\Organization\ReadModel\OrganizationsFilesHistory as FilesHistoryReadModel;
use app\models\Organization\WriteModel\OrganizationsFilesHistory as FilesHistoryWriteModel;

class Loader extends Download
{

    public $host = 'ftp.zakupki.gov.ru';
    public $login = 'free';
    public $pass = 'free';
    public $directory = '/fcs_nsi/nsiOrganization/';
    /**
     * @var FilesHistoryReadModel
     */
    private FilesHistoryReadModel $filesHistoryReadModel;
    /**
     * @var FilesHistoryWriteModel
     */
    private FilesHistoryWriteModel $filesHistoryWriteModel;


    public function __construct(FilesHistoryReadModel $filesHistoryReadModel, FilesHistoryWriteModel $filesHistoryWriteModel)
    {
        $this->filesHistoryReadModel = $filesHistoryReadModel;
        $this->filesHistoryWriteModel = $filesHistoryWriteModel;
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
        return Yii::getAlias('@app/web/uploads/ftp/organization/');
    }

    /**
     * @return bool|string
     */
    public function getPathExtracted()
    {
        return Yii::getAlias('@app/web/uploads/ftp/organization/extracted/');
    }


    /**
     * @inheritDoc
     * @param Queue $queue
     * @return mixed|void
     * @throws FtpException
     */
    public function execute($queue)
    {
        $this->connect();
        $files = $this->getFiles();
        $last_name = $this->filesHistoryReadModel->getLastName();
        if ($last_name) {
            $files = $this->fileFilter($files, $last_name);
        }
        $filteredFiles = $this->arrayFilter($files, "/(_all_)/");
        $i = 0;
        $path = $this->getPath();
        $path_extracted = $this->getPathExtracted();
        if ($this->createDirectory($path) && $this->createDirectory($path_extracted)) {
            foreach ($filteredFiles as $file) {
                $fileName = basename($file);
                if (!$this->filesHistoryReadModel->checkFileExistence($fileName) && $this->download(
                        $path . $fileName,
                        $file
                    ) && $this->extractFile(
                        $path . $fileName,
                        $path_extracted . $fileName
                    ) && FileHelper::unlink($path . $fileName)) {
                    $this->filesHistoryWriteModel->saveInDb($fileName);
                    if ($this->stopCheck($i++)) {
                        break;
                    }
                }
            }
            if ($i > 0 && $i < 10) {
                Yii::$app->queue->push(Parser::create([]));
            }
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

}