<?php
declare(strict_types=1);

namespace Vapewholesaleusa\CustomerLicense\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class NotificationButton extends Field
{

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $url = $this->getUrl('vusacustomercustomerlicense/notification/run');
        return '<button type="button" onclick="setLocation(\'' . $url . '\')">'.__('Run Manually').'</button>';
    }
}
