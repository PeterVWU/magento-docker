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
namespace Rootways\Authorizecim\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Rootways\Authorizecim\Helper\Data;

/**
 * Class AddressDataBuilder
 */
class AddressDataBuilder implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->customHelper = $helper;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $billingAddress = $order->getBillingAddress();
        $result['transactionRequest'] = [
            'customer' => [
                'email' => $billingAddress->getEmail()
            ]
        ];
        $result['transactionRequest']['billTo'] = $this->customHelper->getFormattedAddress($billingAddress, 'billTo');
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $result['transactionRequest']['shipTo'] = $this->customHelper->getFormattedAddress($shippingAddress, 'shipTo');
        }
        $result['transactionRequest']['customerIP'] = $this->customHelper->getCustomerIp();

        return $result;
    }
}
