<?php

namespace Mitto\Bulk\Controller\Adminhtml\Send;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\View\Result\PageFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Mitto\Core\Helper\Config;

/**
 * Class Index
 * @package Mitto\Bulk\Controller\Adminhtml\Send
 */
class Index extends Action
{

    const ADMIN_RESOURCE = 'Mitto_Bulk::send';

    protected $resultPageFactory;
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;
    /**
     * @var Config
     */
    private $configHelper;
    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;
    /**
     * @var Csv
     */
    private $csvParser;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DataPersistorInterface $dataPersistor
     * @param Config $configHelper
     * @param UploaderFactory $uploaderFactory
     * @param Csv $csvParser
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataPersistorInterface $dataPersistor,
        Config $configHelper,
        UploaderFactory $uploaderFactory,
        Csv $csvParser
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
        $this->configHelper = $configHelper;
        $this->uploaderFactory = $uploaderFactory;
        $this->csvParser = $csvParser;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'csv'])
                                              ->setAllowedExtensions(['csv']);
            $csvData = $this->csvParser->getData(
                $uploader->validateFile()['tmp_name']
            );
            $headers = array_shift($csvData);
            $data = array_map(function ($row) use ($headers) {
                return array_combine($headers, $row);
            }, $csvData);
        } catch (Exception $e) {
            $data = [];
        }

        $selected = $this->getRequest()->getParam('selected');
        $this->dataPersistor->set('mitto_bulk_send_form', [
            'selected' => $selected,
            'sender'   => $this->configHelper->getSender(),
            'csv'      => json_encode($data),
        ]);
        return $this->resultPageFactory->create();
    }
}
