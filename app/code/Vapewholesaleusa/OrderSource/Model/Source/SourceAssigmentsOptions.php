<?php

namespace Vapewholesaleusa\OrderSource\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Vapewholesaleusa\OrderSource\Engines\ArrangeSourceCollectorEngine;

class SourceAssigmentsOptions implements OptionSourceInterface
{
    /**
     * @var ArrangeSourceCollectorEngine
     */
    private $arrangeSourceCollectorEngine;

    /**
     * SourceAssigmentsOptions constructor.
     * @param ArrangeSourceCollectorEngine $arrangeSourceCollectorEngine
     */
    public function __construct(
        ArrangeSourceCollectorEngine $arrangeSourceCollectorEngine
    ) {
        $this->arrangeSourceCollectorEngine = $arrangeSourceCollectorEngine;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $sourceCodes = $this->arrangeSourceCollectorEngine->getAllSourceCodes();
        $options = [];
        foreach ($sourceCodes as $code) {
            $options[] = [
                'value' => $code,
                'label' => $this->codeToLabel($code)
            ];
        }
        return $options;
    }

    /**
     * @param $code
     * @return string
     */
    private function codeToLabel($code)
    {
        $label = str_replace('_', ' ', $code);
        $label = ucwords($label);
        return $label;
    }
}
