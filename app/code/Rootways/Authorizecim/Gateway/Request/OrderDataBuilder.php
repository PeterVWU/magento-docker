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
 * Class OrderDataBuilder
 */
class OrderDataBuilder implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * OrderDataBuilder constructor.
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
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $result['transactionRequest'] = [
            'order' => [
                'invoiceNumber' => $paymentDO->getOrder()->getOrderIncrementId()
            ]
        ];

        if ($this->customHelper->sendCartItem() == 1) {
            $itemCollection = [];
            foreach ($order->getItems() as $item) {
                if (!$item->getParentItem()) {
                    $taxable = 0;
                    if (!empty($item->getTaxAmount())) {
                        $taxable = 1;
                    }
                    $itemCollection['lineItem'][] = [
                        "itemId" => mb_substr($item->getSku(), 0, 31),
                        "name" => mb_substr($item->getName(), 0, 31),
                        "description" => mb_substr($item->getName(), 0, 254),
                        "quantity" => $item->getQtyOrdered(),
                        "unitPrice" => $item->getPrice(),
                        "taxable" => $taxable
                    ];
                }
            }
            $result['transactionRequest']['lineItems'] = $itemCollection;
        }

        if (!empty($order->getBaseTaxAmount())) {
            $result['transactionRequest']['tax'] = [
                'amount' => $order->getBaseTaxAmount()
            ];
        }

        if (!empty($order->getBaseShippingAmount())) {
            $result['transactionRequest']['shipping'] = [
                'amount' => $order->getBaseShippingAmount(),
                'name' => mb_substr($order->getShippingMethod(), 0, 30),
                'description' => mb_substr($order->getShippingDescription(), 0, 254)
            ];
        }

        return $result;
    }
}
