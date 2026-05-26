<?php
/**
 * Authorize.net Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Block\Customer;

use Rootways\Authorizecim\Model\SampleConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

class CardRenderer extends AbstractCardRenderer
{
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === SampleConfigProvider::CODE;
    }

    public function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    public function getExpDate()
    {
        return $this->getTokenDetails()['expirationDate'];
    }

    public function getIconUrl()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['url'];
    }

    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    public function getIconWidth()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }
}
