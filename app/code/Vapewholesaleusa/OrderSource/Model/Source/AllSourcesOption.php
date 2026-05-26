<?php

namespace Vapewholesaleusa\OrderSource\Model\Source;

use Exception;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Psr\Log\LoggerInterface;

class AllSourcesOption implements OptionSourceInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @param LoggerInterface $logger
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        LoggerInterface $logger,
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->logger = $logger;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        try {
            $sources = $this->sourceRepository->getList();
            foreach ($sources->getItems() as $source) {
                $options[] = [
                    'value' => $source->getSourceCode(),
                    'label' => $source->getName(),
                ];
            }
            $options[] = [
                'value' => false,
                'label' => 'Select a Source',
            ];
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $options;
    }
}
