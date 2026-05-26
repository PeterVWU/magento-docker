<?php

namespace Vapewholesaleusa\RootwaysAuthorizecim\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Rootways\Authorizecim\Helper\Data;

/**
 * Override for class CustomerIpDataBuilder of Rootways\Authorizecim\Gateway\Request\CustomerIpDataBuilder
 */
class CustomerIpDataBuilder implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * CustomerIpDataBuilder constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->customHelper = $helper;
    }

    /**
     * Build request by adding IP address of the customer.
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        /** @var Order $order */
        $order = $payment->getOrder();
        $ipFromOrder = $order->getRemoteIp();

        $ipFromHelper = $this->customHelper->getCustomerIp();

        $ipAddress = $this->checkIpAddresses($ipFromOrder, $ipFromHelper);

        $result['transactionRequest'] = [
            'customerIP' => $ipAddress,
            'transactionSettings' => [
                'setting' => [
                    'settingName' => 'duplicateWindow',
                    'settingValue' => \Rootways\Authorizecim\Model\SampleConfigProvider::DUPLICATE_WINDOW
                ]
            ]
        ];

        return $result;
    }

    /**
     * @param $ipFromOrder
     * @param $ipFromHelper
     * @return string
     */
    private function checkIpAddresses($ipFromOrder, $ipFromHelper)
    {
        if (filter_var($ipFromOrder, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ipFromOrder;
        }

        return $ipFromHelper;
    }
}
