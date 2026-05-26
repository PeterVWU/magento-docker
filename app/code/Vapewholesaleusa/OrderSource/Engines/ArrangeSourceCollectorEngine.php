<?php

namespace Vapewholesaleusa\OrderSource\Engines;

use Vapewholesaleusa\OrderSource\Api\ArrangeSourceInterface;
use Vapewholesaleusa\OrderSource\Api\ArrangeSourceInterfaceFactory;

/**
 * Class ArrangeSourceCollectorEngine
 */
class ArrangeSourceCollectorEngine
{
    /**
     * @var ArrangeSourceInterfaceFactory[]
     */
    private $arrangeSourceEngines;

    /**
     * @param ArrangeSourceInterfaceFactory[] $arrangeSourceEngines
     */
    public function __construct(
        array $arrangeSourceEngines
    ) {
        $this->arrangeSourceEngines = $arrangeSourceEngines;
    }

    /**
     * @param $code
     * @return ArrangeSourceInterface|null
     */
    public function getArrangeSourcesByCode($code = null): ?ArrangeSourceInterface
    {
        if(!$code) {
            return isset(array_values($this->arrangeSourceEngines)[0])
                ? array_values($this->arrangeSourceEngines)[0]->create()
                : null;
        }

        return $this->arrangeSourceEngines[$code] ? $this->arrangeSourceEngines[$code]->create() : null;
    }

    /**
     * @return array
     */
    public function getAllSourceCodes(): array
    {
        return array_keys($this->arrangeSourceEngines);
    }
}
