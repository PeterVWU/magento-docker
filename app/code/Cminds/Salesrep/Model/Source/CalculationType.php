<?php

namespace Cminds\Salesrep\Model\Source;

/**
 * Class CalculationType
 *
 * @package Cminds\Salesrep\Model\Source
 */
class CalculationType
{
    const SUBTOTAL_CALCULATION_TYPE = 0;  // Item BasePriceInclTax

    const MARGIN_CALCULATION_TYPE = 1;    // Item (BasePriceInclTax - ProductCost)

    const BASE_PRICE_CALCULATION_TYPE = 2;  // Item BasePrice

    const CALCULATION_TYPE_OPTION_KEY = 'salesrep_rep_commission_calculation_type';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SUBTOTAL_CALCULATION_TYPE,
                'label' => __('Subtotal (Base Price Incl. Tax)')
            ],
            [
                'value' => self::MARGIN_CALCULATION_TYPE,
                'label' => __('Margin')
            ],
            [
                'value' => self::BASE_PRICE_CALCULATION_TYPE,
                'label' => __('Subtotal (Base Price)')
            ],
        ];
    }
}
