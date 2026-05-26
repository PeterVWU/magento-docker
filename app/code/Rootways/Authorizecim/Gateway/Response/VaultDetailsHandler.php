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
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Rootways\Authorizecim\Observer\DataAssignObserver;

/**
 * Class VaultDetailsHandler
 */
class VaultDetailsHandler implements HandlerInterface
{
    const CUSTOMERPROFILEID = 'CUSTOMERPROFILEID';
    const ACCOUNTTYPE = 'ACCOUNTTYPE';
    const ACCOUNTNUMBER = 'ACCOUNTNUMBER';

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * @var \Rootways\Authorizecim\Model\Request\Api
     */
    protected $rwApi;

    /**
     * @var PaymentTokenFactoryInterface
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param \Rootways\Authorizecim\Helper\Data $helper
     * @param \Rootways\Authorizecim\Model\Request\Api $rwApi
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Json|null $serializer
     */
    public function __construct(
        \Rootways\Authorizecim\Helper\Data $helper,
        \Rootways\Authorizecim\Model\Request\Api $rwApi,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Json $serializer = null
    ) {
        $this->customHelper = $helper;
        $this->rwApi = $rwApi;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $saveCard = $payment->getAdditionalInformation(DataAssignObserver::SAVE_CARD);
        if ($saveCard == '1') {
            $paymentToken = null;
            $cardcollection = $this->customHelper->getSavedCreditCard();

            if (!empty($response['profileResponse']['customerProfileId'])) {
                $paymentToken = $this->getVaultPaymentToken($response, $payment);
            } elseif (count($cardcollection) > 0 || $cardcollection->getFirstItem()->getGatewayToken() != '') {
                $transId = $response['transactionResponse']['transId'];
                $custProId = $this->customHelper->formatedCustomerId($cardcollection->getFirstItem()->getGatewayToken());
                $newPaymentResponse = $this->rwApi->createPaymentProfileFromTransactionRequest($transId, $custProId);
                $paymentToken = $this->getVaultPaymentTokenExisting($response, $newPaymentResponse, $payment);
            } else {
                $order = $paymentDO->getOrder();
                $billingAddress = $order->getBillingAddress();
                $email = $this->customHelper->subStrFun($billingAddress->getEmail(), 254);
                $customerProfileId = $this->rwApi->getCustomerProfileId($email);
                if (!empty($customerProfileId)) {
                    $transId = $response['transactionResponse']['transId'];
                    $newPaymentResponse = $this->rwApi->createPaymentProfileFromTransactionRequest($transId, $customerProfileId);
                    $paymentToken = $this->getVaultPaymentTokenExisting($response, $newPaymentResponse, $payment);
                }
            }
            if (null !== $paymentToken) {
                $extensionAttributes = $this->getExtensionAttributes($payment);
                $extensionAttributes->setVaultPaymentToken($paymentToken);
            }
        }
    }

    protected function getVaultPaymentToken($response, $payment)
    {
        $paymentToken = null;
        if (isset($response['profileResponse']['customerProfileId'])) {
            $token = $response['profileResponse']['customerProfileId'].'_'.time();
            if (empty($token)) {
                return null;
            }

            /** @var PaymentTokenInterface $paymentToken */
            $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
            $paymentToken->setGatewayToken($token);

            $ccLast4 = substr($response['transactionResponse']['accountNumber'], -4);
            $paymentId = '';
            if (isset($response['profileResponse']['customerPaymentProfileIdList']['numericString'])) {
                $paymentId = $response['profileResponse']['customerPaymentProfileIdList']['numericString'];
            }
            $ccType = $this->getCcType($response['transactionResponse']['accountType']);
            $expMon = sprintf("%02d", $payment->getData(DataAssignObserver::CC_EXP_MONTH));
            $expYr = $payment->getData(DataAssignObserver::CC_EXP_YEAR);
            $expirationDate = $expMon.'/'.$expYr;

            $expAt = date('Y-m-d h:i:s', strtotime($expYr . '-' . $expMon . '-01 00:00:00'));
            $paymentToken->setExpiresAt($expAt);

            $paymentToken->setTokenDetails($this->convertDetailsToJSON([
                'type' => $ccType,
                'maskedCC' => $ccLast4,
                'expirationDate' => $expirationDate,
                'paymentProfileId' => $paymentId
            ]));
        }

        return $paymentToken;
    }

    protected function getVaultPaymentTokenExisting($response, $newPaymentResponse, $payment)
    {
        $paymentToken = null;
        if (isset($newPaymentResponse['customerProfileId'])) {
            $token = $newPaymentResponse['customerProfileId'].'_'.time();
            if (empty($token)) {
                return null;
            }

            /** @var PaymentTokenInterface $paymentToken */
            $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
            $paymentToken->setGatewayToken($token);

            $ccLast4 = substr($response['transactionResponse']['accountNumber'], -4);
            $paymentId = '';
            if (isset($newPaymentResponse['customerPaymentProfileIdList']['numericString'])) {
                $paymentId = $newPaymentResponse['customerPaymentProfileIdList']['numericString'];
            }
            $ccType = $this->getCcType($response['transactionResponse']['accountType']);
            $expMon = sprintf("%02d", $payment->getData(DataAssignObserver::CC_EXP_MONTH));
            $expYr = $payment->getData(DataAssignObserver::CC_EXP_YEAR);
            $expirationDate = $expMon.'/'.$expYr;

            $expAt = date('Y-m-d h:i:s', strtotime($expYr . '-' . $expMon . '-01 00:00:00'));
            $paymentToken->setExpiresAt($expAt);

            $paymentToken->setTokenDetails($this->convertDetailsToJSON([
                'type' => $ccType,
                'maskedCC' => $ccLast4,
                'expirationDate' => $expirationDate,
                'paymentProfileId' => $paymentId
            ]));
        }
        return $paymentToken;
    }

    private function convertDetailsToJSON($details)
    {
        $json = $this->serializer->serialize($details);
        return $json ? $json : '{}';
    }

    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    private function getCcType($name)
    {
        if ($name == "AmericanExpress" || $name == "American Express") {
            $ccType = "AE";
        } else {
            $ccType = $this->customHelper->getCcTypeCodeByName($name) == '' ?
                $name :
                $this->customHelper->getCcTypeCodeByName($name);
        }

        return $ccType;
    }
}
