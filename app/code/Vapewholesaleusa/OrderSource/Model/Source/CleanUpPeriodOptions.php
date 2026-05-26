<?php

namespace Vapewholesaleusa\OrderSource\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CleanUpPeriodOptions implements OptionSourceInterface
{
    const OFF = 'off';
    const DAILY = '1';
    const WEEKLY = '7';
    const MONTHLY = '30';
    const YEARLY = '365';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::OFF,
                'label' => 'Never'
            ],
            [
                'value' => self::DAILY,
                'label' => 'Older Than 1 Day'
            ],
            [
                'value' => self::WEEKLY,
                'label' => 'Older Than 1 Week'
            ],
            [
                'value' => self::MONTHLY,
                'label' => 'Older Than 1 Month'
            ],
            [
                'value' => self::YEARLY,
                'label' => 'Older Than 1 Year'
            ]
        ];
    }

}
