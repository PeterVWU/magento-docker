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

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class CommonDataBuilder
 */
class CommonDataBuilder implements BuilderInterface
{
    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * CommonDataBuilder constructor.
     * @param \Rootways\Authorizecim\Helper\Data $helper
     */
    public function __construct(
        \Rootways\Authorizecim\Helper\Data $helper
    ) {
        $this->customHelper = $helper;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $orderStoreId = $order->getStoreId();
        $result['merchantAuthentication'] = [
            'name' => $this->customHelper->getApiLoginId($orderStoreId),
            'transactionKey'   => $this->customHelper->getTransactionKey($orderStoreId)
        ];
        $result['storeid'] = $orderStoreId;

        return $result;
    }
}
