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
namespace Rootways\Authorizecim\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Rootways\Authorizecim\Observer\DataAssignObserver;

/**
 * Class OrderOnlyPaymentDetailsHandler
 */
class OrderOnlyPaymentDetailsHandler implements HandlerInterface
{
    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $transId = $this->getTraId($response);
        $payment->setTransactionId($transId);
        $payment->setCcTransId($transId);
        $payment->setLastTransId($transId);
        $payment->setAdditionalInformation('transaction_id', $transId);
        $payment->setAdditionalInformation('rw_authorizecim_cId', $response['customerProfileId']);
        $payment->setAdditionalInformation('rw_authorizecim_pId', $response['customerPaymentProfileId']);
        $payment->setAdditionalInformation('is_save_info_transaction', 1);
    }

    /**
     * @param $response
     * @return mixed|string
     */
    private function getTraId($response)
    {
        $transId = '';
        if (!empty($response['validationDirectResponse'])) {
            $validationResponse = explode(',', $response['validationDirectResponse']);
            $transId = isset($validationResponse[6]) ? $validationResponse[6] : '';
        }

        return $transId;
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction(): bool
    {
        return false;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $payment): bool
    {
        return false;
    }
}
