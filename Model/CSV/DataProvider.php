<?php

namespace Mitto\Bulk\Model\CSV;

use Magento\Customer\Model\ResourceModel\Grid\CollectionFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Class DataProvider
 * @package Mitto\Bulk\Model\CSV
 */
class DataProvider extends AbstractDataProvider
{

    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;
    /**
     * @var CollectionFactory
     */
    private $customerGridCollectionFactory;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param DataPersistorInterface $dataPersistor
     * @param CollectionFactory $customerGridCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        DataPersistorInterface $dataPersistor,
        CollectionFactory $customerGridCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->customerGridCollectionFactory = $customerGridCollectionFactory;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData()
    {

        return [
            'items' => [
                array_merge($this->dataPersistor->get('mitto_csv_upload'), ['id' => null]),
            ],
        ];
    }

    /**
     * @param Filter $filter
     * @return bool|mixed|void
     */
    public function addFilter(Filter $filter)
    {
        return false;
    }
}
