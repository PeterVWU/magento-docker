<?php

namespace Vapewholesaleusa\OrderSource\Services;

use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class GetAllSourcesAsOptionService
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $options = [];
        try {
            $sources = $this->sourceRepository->getList($this->searchCriteriaBuilder->create())->getItems();
            $options[0] = 'None';
            foreach ($sources as $source) {
                $options[$source->getSourceCode()] = $source->getName();
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $options;
    }

}
