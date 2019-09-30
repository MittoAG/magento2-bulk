<?php

namespace Mitto\Bulk\Controller\Adminhtml\Csv;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;

/**
 * Class Upload
 * @package Mitto\Bulk\Controller\Adminhtml\Csv
 */
class Upload extends Action
{

    const ADMIN_RESOURCE = 'Mitto_Bulk::send';
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var UploaderFactory
     */
    private $fileUploaderFactory;
    /**
     * @var ReadInterface
     */
    private $_mediaWrite;
    /**
     * @var WriteInterface
     */
    private $_mediaRead;
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Upload constructor.
     * @param Context $context
     * @param Filesystem $filesystem
     * @param UploaderFactory $fileUploaderFactory
     * @param DataPersistorInterface $dataPersistor
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        UploaderFactory $fileUploaderFactory,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->_mediaRead = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->_mediaWrite = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->filesystem = $filesystem;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $target = $this->_mediaWrite->getAbsolutePath('mitto_csv/');
        /** @var Uploader $uploader */
        $uploader = $this->fileUploaderFactory->create(['fileId' => 'csv']);
        $uploader->setAllowedExtensions(['csv']);
        $uploader->setAllowRenameFiles(true);
        try {
            $result = $uploader->save($target);
            if (!$result || !isset($result['file'])) {
                throw new LocalizedException(__('Unable to upload'));
            }
            $uploadedCSV = $this->_mediaRead->openFile('mitto_csv/' . $result['file']);
            $columns = $uploadedCSV->readCsv();
            if (!in_array('phone', $columns)) {
                throw new LocalizedException(__('CSV file must contain phone column'));
            }
            $rowCount = 0;
            while ($row = $uploadedCSV->readCsv()) {
                if (count($row) != count($columns)) {
                    throw new LocalizedException(__('Invalid CSV file'));
                }
                $rowCount++;
            }
            $this->messageManager->addSuccessMessage(
                __('Successfully uploaded CSV with %1 data rows', $rowCount)
            );
            $this->messageManager->addNoticeMessage(
                __('Available template variables: %1', implode(', ', $columns))
            );
            $this->dataPersistor->set('mitto_csv_upload', [
                'file'    => $result['file'],
                'columns' => $columns,
            ]);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $this->_redirect('*/*/index');
        }
        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
