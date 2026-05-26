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
class CustomerDataBuilder implements BuilderInterface
{
    /**
     * @var Data
     */
    private $customHelper;

    /**
     * CustomerDataBuilder constructor.
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
        if ($this->customHelper->getCustomerId() != '') {
            $result['transactionRequest']['customer']['id'] = $this->customHelper->subStrFun($this->customHelper->getCustomerId(), 19);
        }
        $result['transactionRequest']['customer']['email'] = $this->customHelper->subStrFun($billingAddress->getEmail(), 254);

        return $result;
    }
}
