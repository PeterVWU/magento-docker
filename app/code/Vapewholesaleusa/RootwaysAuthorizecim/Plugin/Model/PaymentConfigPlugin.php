<?php
declare(strict_types=1);

namespace Vapewholesaleusa\RootwaysAuthorizecim\Plugin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Model\Config;

class PaymentConfigPlugin
{
    const int YEARS_RANGE = 10;
    const XML_YEARS_RANGE = 'vusa_rootways/general/years_range';
    public function __construct(
        private readonly DateTime $date,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param Config $subject
     * @param $result
     * @return array
     */
    public function afterGetYears(
        Config $subject,
        $result
    ) {
        $years = [];
        $first = (int)$this->date->date('Y');
        for ($index = 0; $index <= $this->getRangerYear(); $index++) {
            $year = $first + $index;
            $years[$year] = $year;
        }
        return $years;
    }


    /**
     * Get Ranger Year
     *
     * @return int
     */
    private function getRangerYear()
    {
        $configValue = (int)$this->scopeConfig->getValue(self::XML_YEARS_RANGE);
        return $configValue && $configValue >= 1 ? $configValue : self::YEARS_RANGE;
    }
}
