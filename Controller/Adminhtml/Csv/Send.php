<?php

namespace Mitto\Bulk\Controller\Adminhtml\Csv;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Mitto\Core\Model\Renderer;
use Mitto\Core\Model\Sender;

/**
 * Class Send
 * @package Mitto\Bulk\Controller\Adminhtml\Csv
 */
class Send extends Action
{

    const ADMIN_RESOURCE = 'Mitto_Bulk::send';
    /**
     * @var ReadInterface
     */
    private $_mediaWrite;
    /**
     * @var WriteInterface
     */
    private $_mediaRead;
    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var Sender
     */
    private $sender;

    /**
     * Send constructor.
     * @param Context $context
     * @param Filesystem $filesystem
     * @param Renderer $renderer
     * @param Sender $sender
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        Renderer $renderer,
        Sender $sender
    ) {
        parent::__construct($context);
        $this->_mediaRead = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->_mediaWrite = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->renderer = $renderer;
        $this->sender = $sender;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $templateId = $request->getParam('template_id');
        $templateContent = $request->getParam('template');
        $useExistingTemplate = $request->getParam('use_existing_template');
        $sender = $request->getParam('sender');
        $general = $request->getParam('general');
        $csvFilename = $general['file'];
        $results = [];
        try {
            $uploadedCSV = $this->_mediaRead->openFile('mitto_csv/' . $csvFilename);
            $columns = $uploadedCSV->readCsv();
            while ($row = $uploadedCSV->readCsv()) {
                $data = array_combine($columns, $row);
                $phone = $data['phone'];
                if ($useExistingTemplate) {
                    $message = $this->renderer->renderTemplate(
                        $templateId,
                        $data
                    );
                } else {
                    $message = $this->renderer->render(
                        $templateContent,
                        $data
                    );
                }
                $results[] = $this->sender->send(
                    $phone,
                    $message,
                    $sender
                );
            }
            $successful = array_filter($results, function ($result) {
                return key_exists('responseCode', $result) && $result['responseCode'] == 0;
            });
            $failed = array_filter($results, function ($result) {
                return !key_exists('responseCode', $result) || $result['responseCode'] != 0;
            });
            if (count($successful) > 0) {
                $this->messageManager->addSuccessMessage(__("Successfully sent %1 messages", count($successful)));
            }
            if (count($failed) > 0) {
                $this->messageManager->addErrorMessage(__("Failed sending %1 messages", count($failed)));
            }
            return $this->_redirect('*/*/index');
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $this->_redirect('*/*/index');
        }
    }
}
