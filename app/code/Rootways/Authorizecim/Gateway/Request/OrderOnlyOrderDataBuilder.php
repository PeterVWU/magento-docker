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
 * Class OrderOnlyOrderDataBuilder
 */
class OrderOnlyOrderDataBuilder implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * OrderOnlyOrderDataBuilder constructor.
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

        if (!empty($payment->getAdditionalInformation()['is_save_info_transaction'])) {
            if ($this->customHelper->sendCartItem() == 1) {
                $itemCollection = [];
                foreach ($order->getItems() as $item) {
                    if (!$item->getParentItem()) {
                        $taxable = 0;
                        if (!empty($item->getTaxAmount())) {
                            $taxable = 1;
                        }
                        $itemCollection['lineItem'][] = [
                            "itemId" => $this->customHelper->subStrFun($item->getSku(), 31),
                            "name" => $this->customHelper->subStrFun($item->getName(), 31),
                            "description" => $this->customHelper->subStrFun($item->getName(), 254),
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
                    'name' => $this->customHelper->subStrFun($order->getShippingMethod(), 30),
                    'description' => $this->customHelper->subStrFun($order->getShippingDescription(), 254)
                ];
            }
        }

        return $result;
    }
}
