<?php

declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Model;

use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterfaceFactory;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceSearchResultsInterface;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceSearchResultsInterfaceFactory;
use Vapewholesaleusa\OrderSource\Api\OrderSourceRepositoryInterface;
use Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource as ResourceOrderSource;
use Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource\CollectionFactory as OrderSourceCollectionFactory;

class OrderSourceRepository implements OrderSourceRepositoryInterface
{
    public function __construct(
        private readonly ResourceOrderSource $resource,
        private readonly OrderSourceFactory $orderSourceFactory,
        private readonly OrderSourceCollectionFactory $orderSourceCollectionFactory,
        private readonly OrderSourceSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly JoinProcessorInterface $extensionAttributesJoinProcessor,
        private readonly ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
    }

    /**
     * @throws \Exception
     */
    public function save(OrderSourceInterface $entity): OrderSourceInterface
    {
        $orderSourceData = $this->extensibleDataObjectConverter->toNestedArray(
            $entity,
            [],
            OrderSourceInterface::class
        );

        $orderSourceModel = $this->orderSourceFactory->create()->setData($orderSourceData);

        try {
            $this->resource->save($orderSourceModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the orderSource: %1',
                $exception->getMessage()
            ));
        }
        return $orderSourceModel->getDataModel();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function get(int $id): OrderSourceInterface
    {
        $orderSource = $this->orderSourceFactory->create();
        $this->resource->load($orderSource, $id);
        if (!$orderSource->getId()) {
            throw new NoSuchEntityException(__('OrderSource with id "%1" does not exist.', $id));
        }
        return $orderSource->getDataModel();
    }

    public function getList(SearchCriteriaInterface $criteria): OrderSourceSearchResultsInterface
    {
        $collection = $this->orderSourceCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            OrderSourceInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(OrderSourceInterface $orderSource): bool
    {
        try {
            $orderSourceModel = $this->orderSourceFactory->create();
            $this->resource->load($orderSourceModel, $orderSource->getEntityId());
            $this->resource->delete($orderSourceModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the OrderSource: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->get($id));
    }
}
