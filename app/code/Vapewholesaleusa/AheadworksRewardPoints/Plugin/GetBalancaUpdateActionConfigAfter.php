<?php

namespace Vapewholesaleusa\AheadworksRewardPoints\Plugin;

use Aheadworks\RewardPoints\Model\Config;

/**
 * Class GetBalancaUpdateActionConfigAfter
 */
class GetBalancaUpdateActionConfigAfter
{
    /**
     * @param Config $subject
     * @param $result
     * @param int|null $websiteId
     * @return string
     */
    public function afterGetBalanceUpdateActions(Config $subject, $result, $websiteId = null): string
    {
        return $result ?? '';
    }
}
