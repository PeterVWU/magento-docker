<?php

namespace Vapewholesaleusa\OrderSource\Model\Source;

use Exception;
use Magento\Framework\Data\OptionSourceInterface;
use Psr\Log\LoggerInterface;
use Vapewholesaleusa\OrderSource\Services\GetSourcesOfStore;

/**
 *
 */
class Sourcesoption implements OptionSourceInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetSourcesOfStore
     */
    private $getSourcesOfStore;


    /**
     * @param LoggerInterface $logger
     * @param GetSourcesOfStore $getSourcesOfStore
     */
    public function __construct(
        LoggerInterface   $logger,
        GetSourcesOfStore $getSourcesOfStore
    )
    {
        $this->logger = $logger;
        $this->getSourcesOfStore = $getSourcesOfStore;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        try {
            $sources = $this->getSourcesOfStore->execute();

            foreach ($sources as $source) {
                $options[] = [
                    'value' => $source->getSourceCode(),
                    'label' => $source->getSourceCode(),
                ];
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $options;
    }
}
