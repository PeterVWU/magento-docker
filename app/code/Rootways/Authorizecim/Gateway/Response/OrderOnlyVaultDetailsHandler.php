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
 * Class OrderOnlyVaultDetailsHandler
 */
class OrderOnlyVaultDetailsHandler implements HandlerInterface
{
    const CUSTOMERPROFILEID = 'CUSTOMERPROFILEID';
    const ACCOUNTTYPE = 'ACCOUNTTYPE';
    const ACCOUNTNUMBER = 'ACCOUNTNUMBER';

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     *
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

        if ($saveCard == '1' &&
            !empty($response['messages']['message']['code']) &&
            $response['messages']['message']['code'] != 'E00039' &&
            !empty($response['customerProfileId']) &&
            !empty($response['customerPaymentProfileId']) &&
            !empty($response['validationDirectResponse'])
        ) {
            // TODO Check here whether customer payment profile is added to cusotmer custom attribute and if not then add it. Or we can always update it
            // Then save token in the vault
            $paymentToken = $this->getVaultPaymentToken($response, $payment);
            if (null !== $paymentToken) {
                $extensionAttributes = $this->getExtensionAttributes($payment);
                $extensionAttributes->setVaultPaymentToken($paymentToken);
            }
        }
    }

    protected function getVaultPaymentToken($response, $payment)
    {
        $token = $response['customerProfileId'] . '_'.time();
        if (empty($token)) {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);

        $expirationDate = $expAt = '2029-01-01 00:00:00';
        if (!empty($payment->getData(DataAssignObserver::CC_EXP_MONTH)) &&
            !empty($payment->getData(DataAssignObserver::CC_EXP_YEAR))
        ) {
            $expMon = sprintf("%02d", $payment->getData(DataAssignObserver::CC_EXP_MONTH));
            $expYr = $payment->getData(DataAssignObserver::CC_EXP_YEAR);
            $dt = \DateTime::createFromFormat('y', $expYr);
            if ($dt) {
                $expYr = $dt->format('Y');
            }

            $expirationDate = $expMon.'/'.$expYr;
            $expAt = date('Y-m-d h:i:s', strtotime($expYr . '-' . $expMon . '-01 00:00:00'));
        }
        $paymentToken->setExpiresAt($expAt);

        $responseCollection =  explode(',', $response['validationDirectResponse']);
        if (!isset($responseCollection[50]) && !isset($responseCollection[51])) {
            return null;
        }
        $ccLast4 = substr($responseCollection[50], -4);
        $ccType = $this->getCcType($responseCollection[51]);

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $ccType,
            'maskedCC' => $ccLast4,
            'expirationDate' => $expirationDate,
            'paymentProfileId' => $response['customerPaymentProfileId']
        ]));

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
