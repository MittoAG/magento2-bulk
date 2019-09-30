<?php

namespace Mitto\Bulk\Controller\Adminhtml\Csv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\ImportExport\Helper\Data;
use Mitto\Core\Helper\Config;

/**
 * Class Index
 * @package Mitto\Bulk\Controller\Adminhtml\Csv
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
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DataPersistorInterface $dataPersistor
     * @param Config $configHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataPersistorInterface $dataPersistor,
        Config $configHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor;
        $this->configHelper = $configHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $this->messageManager->addNotice(
            $this->_objectManager->get(Data::class)->getMaxUploadSizeMessage()
        );
        return $this->resultPageFactory->create();
    }
}
