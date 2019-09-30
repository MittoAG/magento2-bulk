<?php

namespace Mitto\Bulk\Controller\Adminhtml\Send;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mitto\Core\Model\Renderer;
use Mitto\Core\Model\Sender;

/**
 * Class Send
 * @package Mitto\Bulk\Controller\Adminhtml\Send
 */
class Send extends Action
{

    const ADMIN_RESOURCE = 'Mitto_Bulk::send';

    protected $resultPageFactory;
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var Sender
     */
    private $sender;
    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Send constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param DataPersistorInterface $dataPersistor
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param StoreManagerInterface $storeManager
     * @param Sender $sender
     * @param Renderer $renderer
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DataPersistorInterface $dataPersistor,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        StoreManagerInterface $storeManager,
        Sender $sender,
        Renderer $renderer
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->dataPersistor = $dataPersistor;
        $this->customerRepository = $customerRepository;
        $this->sender = $sender;
        $this->renderer = $renderer;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $templateId = $this->getRequest()->getParam('template_id');
        $templateContent = $this->getRequest()->getParam('template');
        $useExistingTemplate = $this->getRequest()->getParam('use_existing_template');
        $sender = $this->getRequest()->getParam('sender');
        $this->searchCriteriaBuilder->addFilter(
            'entity_id',
            $this->getRequest()->getParam('selected'),
            'in'
        );
        $customers = $this->customerRepository->getList(
            $this->searchCriteriaBuilder->create()
        );
        $results = [];
        /** @var Customer $customer */
        foreach ($customers->getItems() as $customer) {
            $templateVars['customer'] = $customer;
            try {
                $templateVars['store'] = $this->storeManager->getStore($customer->getStoreId());
            } catch (NoSuchEntityException $e) {
            }
            if ($useExistingTemplate) {
                $message = $this->renderer->renderTemplate(
                    $templateId,
                    $templateVars
                );
            } else {
                $message = $this->renderer->render(
                    $templateContent,
                    $templateVars
                );
            }
            $results[$customer->getId()] = $this->sender->sendToCustomer(
                $customer,
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
        return $this->_redirect('customer/index/index');
    }
}
